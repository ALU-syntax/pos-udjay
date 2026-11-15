<?php

namespace Tests\Feature\B2b;

use App\Models\B2b\Contract;
use App\Models\B2b\Customer;
use App\Models\B2b\Order;
use Database\Seeders\B2bMenuSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class B2bHttpTest extends B2bTestCase
{
    private const ALL = [
        'read b2b', 'read b2b/customers', 'create b2b/customers', 'update b2b/customers', 'delete b2b/customers',
        'read b2b/contracts', 'create b2b/contracts', 'update b2b/contracts', 'delete b2b/contracts', 'activate b2b/contracts', 'terminate b2b/contracts',
        'read b2b/cashier', 'create b2b/cashier', 'read b2b/orders', 'complete b2b/orders', 'cancel b2b/orders', 'read b2b/invoices',
    ];

    public function test_menu_seeder_registers_menus_and_permissions_idempotently(): void
    {
        $this->seed(B2bMenuSeeder::class);
        $this->seed(B2bMenuSeeder::class);

        $this->assertDatabaseHas('menus', ['url' => 'b2b', 'category' => 'B2B']);
        foreach (['b2b/cashier', 'b2b/orders', 'b2b/invoices', 'b2b/customers', 'b2b/contracts'] as $url) {
            $this->assertSame(1, \App\Models\Menu::where('url', $url)->count());
        }
        $this->assertDatabaseHas('permissions', ['name' => 'activate b2b/contracts']);
        $this->assertDatabaseHas('permissions', ['name' => 'cancel b2b/orders']);
    }

    public function test_pages_require_permission(): void
    {
        $user = $this->userFor($this->outlet());

        foreach (['b2b/customers', 'b2b/contracts', 'b2b/cashier', 'b2b/orders', 'b2b/invoices'] as $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }
    }

    public function test_index_pages_and_datatables_render(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet, self::ALL);
        $customer = $this->customer();
        $this->contract($customer);
        $variant = $this->variant($outlet);
        app(\App\Services\B2b\OrderService::class)->place($user, [
            'outlet_id' => $outlet->id, 'b2b_customer_id' => $customer->id, 'delivery_method' => 'pickup', 'due_date' => today()->addDays(7)->toDateString(),
            'items' => [['variant_id' => $variant->id, 'qty' => 1]],
        ]);

        foreach (['b2b/customers', 'b2b/contracts', 'b2b/orders', 'b2b/invoices'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
            // Yajra menyimpan request pertama di singleton; di production tiap request memakai container baru.
            $this->app->forgetInstance('datatables.request');
            $this->actingAs($user)->getJson(route($route, ['draw' => 1, 'start' => 0, 'length' => 10]), ['X-Requested-With' => 'XMLHttpRequest'])
                ->assertOk()
                ->assertJsonPath('draw', 1);
        }
        $this->actingAs($user)->get(route('b2b/cashier'))->assertOk()->assertSee($outlet->name);
        $this->actingAs($user)->get(route('b2b/customers/create'))->assertOk();
        $this->actingAs($user)->get(route('b2b/contracts/create', ['customer_id' => $customer->id]))->assertOk();
    }

    public function test_customer_store_creates_code_contacts_addresses_and_single_flags(): void
    {
        $user = $this->userFor(null, ['create b2b/customers']);

        $response = $this->actingAs($user)->postJson(route('b2b/customers/store'), [
            'type' => 'pt',
            'name' => '  PT Maju Jaya  ',
            'email' => 'FINANCE@MAJU.CO.ID',
            'is_active' => 1,
            'contacts' => [
                ['name' => 'Budi', 'is_primary' => 1, 'receive_reminder' => 1],
                ['name' => 'Sari', 'is_primary' => 1],
            ],
            'addresses' => [
                ['label' => 'Kantor', 'address' => 'Jl. Sudirman 1', 'city' => 'Jakarta'],
                ['label' => 'Gudang', 'address' => 'Jl. Gudang 2', 'is_default_shipping' => 1],
            ],
        ])->assertOk();

        $customer = Customer::with(['contacts', 'addresses'])->findOrFail($response->json('data.id'));
        $this->assertSame('PT Maju Jaya', $customer->name);
        $this->assertSame($user->id, $customer->created_by);
        $this->assertNull($customer->modified_by);
        $this->assertSame('pt', $customer->type);
        $this->assertSame('PT', $customer->typeLabel());
        $this->assertSame('finance@maju.co.id', $customer->email);
        $this->assertMatchesRegularExpression('/^CUS-B2B-\d{4,}$/', $customer->code);
        $this->assertSame([true, false], $customer->contacts->sortBy('id')->pluck('is_primary')->all());
        $this->assertTrue($customer->addresses->firstWhere('label', 'Kantor')->is_default_billing);
        $this->assertTrue($customer->addresses->firstWhere('label', 'Gudang')->is_default_shipping);
    }

    public function test_audit_columns_are_filled_by_the_system_not_by_the_form(): void
    {
        $creator = $this->userFor(null, ['create b2b/customers']);
        $editor = $this->userFor(null, ['update b2b/customers']);

        $id = $this->actingAs($creator)->postJson(route('b2b/customers/store'), [
            'type' => 'pt', 'name' => 'PT Audit', 'created_by' => 999999, 'modified_by' => 999999,
        ])->assertOk()->json('data.id');
        $customer = Customer::findOrFail($id);
        $this->assertSame($creator->id, $customer->created_by);
        $this->assertNull($customer->modified_by);

        $this->actingAs($editor)->postJson(route('b2b/customers/toggle', $id))->assertOk();
        $customer->refresh();
        $this->assertFalse($customer->is_active);
        $this->assertSame($creator->id, $customer->created_by);
        $this->assertSame($editor->id, $customer->modified_by);
    }

    public function test_customer_type_is_required_and_limited_to_known_types(): void
    {
        $user = $this->userFor(null, ['create b2b/customers']);

        $this->actingAs($user)->postJson(route('b2b/customers/store'), ['name' => 'Tanpa Jenis'])
            ->assertUnprocessable()->assertJsonValidationErrors('type');
        $this->actingAs($user)->postJson(route('b2b/customers/store'), ['type' => 'ud', 'name' => 'UD Lain'])
            ->assertUnprocessable()->assertJsonValidationErrors('type');

        $id = $this->actingAs($user)->postJson(route('b2b/customers/store'), ['type' => 'individual', 'name' => 'Budi Santoso'])
            ->assertOk()->json('data.id');
        $this->assertSame('Perorangan', Customer::findOrFail($id)->typeLabel());
    }

    public function test_reminder_is_off_by_default_and_saved_when_fully_configured(): void
    {
        $user = $this->userFor(null, ['create b2b/customers']);

        $plain = $this->actingAs($user)->postJson(route('b2b/customers/store'), ['type' => 'cv', 'name' => 'CV Tanpa Reminder'])->assertOk();
        $customer = Customer::findOrFail($plain->json('data.id'));
        $this->assertFalse($customer->reminder_enabled);
        $this->assertSame('Tidak dikirim', $customer->reminderSummary());

        $response = $this->actingAs($user)->postJson(route('b2b/customers/store'), [
            'type' => 'pt',
            'name' => 'PT Rajin Diingatkan',
            'reminder_enabled' => 1,
            'reminder_channels' => ['email', 'whatsapp'],
            'reminder_days' => [-3, 0, -3],
            'reminder_overdue_daily' => 1,
            'contacts' => [
                ['name' => 'Finance', 'email' => 'finance@rajin.test', 'receive_reminder' => 1],
                ['name' => 'Owner', 'phone' => '0812', 'receive_reminder' => 1],
                ['name' => 'Gudang', 'phone' => '0813'],
            ],
        ])->assertOk();

        $customer = Customer::with('contacts')->findOrFail($response->json('data.id'));
        $this->assertTrue($customer->reminder_enabled);
        $this->assertSame(['email', 'whatsapp'], $customer->reminder_channels);
        $this->assertSame([-3, 0], $customer->reminder_days);
        $this->assertTrue($customer->reminder_overdue_daily);
        $this->assertSame(['Finance', 'Owner'], $customer->contacts->where('receive_reminder', true)->pluck('name')->values()->all());
        $this->assertSame('Email + WhatsApp · H-3, Hari H, harian setelah jatuh tempo', $customer->reminderSummary());
    }

    public function test_enabled_reminder_must_have_channel_schedule_and_reachable_recipient(): void
    {
        $user = $this->userFor(null, ['create b2b/customers']);
        $store = fn (array $data) => $this->actingAs($user)->postJson(route('b2b/customers/store'), array_merge(['type' => 'pt', 'name' => 'PT Uji', 'reminder_enabled' => 1], $data));

        $store([])->assertUnprocessable()->assertJsonValidationErrors(['reminder_channels', 'reminder_days', 'reminder_recipients']);
        $store(['reminder_channels' => ['fax'], 'reminder_days' => [5]])
            ->assertUnprocessable()->assertJsonValidationErrors(['reminder_channels.0', 'reminder_days.0']);
        $store([
            'reminder_channels' => ['email'], 'reminder_days' => [-1],
            'contacts' => [['name' => 'Tanpa Email', 'phone' => '0812', 'receive_reminder' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors('reminder_recipients');
        $store([
            'reminder_channels' => ['whatsapp'], 'reminder_overdue_daily' => 1,
            'contacts' => [['name' => 'Tanpa Nomor', 'email' => 'a@b.test', 'receive_reminder' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors('reminder_recipients');

        // Reminder mati: pengaturan kosong tidak divalidasi.
        $store(['reminder_enabled' => 0])->assertOk();
    }

    public function test_customer_update_syncs_children_and_validates_nested_fields(): void
    {
        $user = $this->userFor(null, ['update b2b/customers']);
        $customer = $this->customer();
        $keep = $customer->contacts()->create(['name' => 'Lama']);
        $drop = $customer->contacts()->create(['name' => 'Dihapus']);

        $this->actingAs($user)->putJson(route('b2b/customers/update', $customer->id), [
            'type' => 'pt', 'name' => 'PT Baru',
            'contacts' => [['id' => $keep->id, 'name' => 'Lama Diubah'], ['name' => 'Baru']],
        ])->assertOk();

        $this->assertSame('Lama Diubah', $keep->fresh()->name);
        $this->assertSame($user->id, $customer->fresh()->modified_by);
        $this->assertSoftDeleted('b2b_customer_contacts', ['id' => $drop->id]);
        $this->assertSame(2, $customer->contacts()->count());

        $this->actingAs($user)->putJson(route('b2b/customers/update', $customer->id), [
            'type' => 'pt', 'name' => 'PT Baru', 'addresses' => [['label' => 'X']],
        ])->assertUnprocessable()->assertJsonValidationErrors('addresses.0.address');
    }

    public function test_customer_with_orders_cannot_be_deleted(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet, ['delete b2b/customers']);
        $customer = $this->customer();
        $variant = $this->variant($outlet);
        app(\App\Services\B2b\OrderService::class)->place($user, [
            'outlet_id' => $outlet->id, 'b2b_customer_id' => $customer->id, 'delivery_method' => 'pickup', 'due_date' => today()->addDays(7)->toDateString(),
            'items' => [['variant_id' => $variant->id, 'qty' => 1]],
        ]);

        $this->actingAs($user)->deleteJson(route('b2b/customers/destroy', $customer->id))->assertUnprocessable();
        $this->assertNotSoftDeleted('b2b_customers', ['id' => $customer->id]);
    }

    public function test_contract_store_normalizes_rules_and_stores_attachment(): void
    {
        Storage::fake('local');
        $outlet = $this->outlet();
        $variant = $this->variant($outlet);
        $category = $this->category();
        $user = $this->userFor(null, ['create b2b/contracts', 'activate b2b/contracts']);
        $customer = $this->customer();

        $response = $this->actingAs($user)->post(route('b2b/contracts/store'), [
            'b2b_customer_id' => $customer->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addYear()->toDateString(),
            'activate' => 1,
            'attachment' => UploadedFile::fake()->create('kontrak.pdf', 100, 'application/pdf'),
            'rules' => [
                ['scope' => 'category', 'category_id' => $category->id, 'product_id' => $variant->product_id, 'discount_type' => 'percent', 'discount_value' => 10, 'min_qty' => 1],
                ['scope' => 'variant', 'variant_id' => $variant->id, 'outlet_id' => '', 'discount_type' => 'fixed_price', 'discount_value' => 7000, 'min_qty' => 5],
            ],
        ], ['Accept' => 'application/json'])->assertOk();

        $contract = Contract::with('rules')->findOrFail($response->json('data.id'));
        $this->assertSame(Contract::STATUS_ACTIVE, $contract->status);
        Storage::disk('local')->assertExists($contract->attachment_path);
        $categoryRule = $contract->rules->firstWhere('scope', 'category');
        $this->assertNull($categoryRule->product_id);
        $variantRule = $contract->rules->firstWhere('scope', 'variant');
        $this->assertSame($outlet->id, $variantRule->outlet_id);
        $this->assertSame($variant->product_id, $variantRule->product_id);
    }

    public function test_contract_store_rejects_invalid_and_duplicate_rules(): void
    {
        $user = $this->userFor(null, ['create b2b/contracts']);
        $customer = $this->customer();
        $base = ['b2b_customer_id' => $customer->id, 'start_date' => today()->toDateString(), 'end_date' => today()->addMonth()->toDateString()];

        $this->actingAs($user)->postJson(route('b2b/contracts/store'), $base + ['rules' => [
            ['scope' => 'all', 'discount_type' => 'percent', 'discount_value' => 150, 'min_qty' => 1],
        ]])->assertUnprocessable()->assertJsonValidationErrors('rules.0.discount_value');

        $this->actingAs($user)->postJson(route('b2b/contracts/store'), $base + ['rules' => [
            ['scope' => 'product', 'discount_type' => 'percent', 'discount_value' => 5, 'min_qty' => 1],
        ]])->assertUnprocessable()->assertJsonValidationErrors('rules.0.product_id');

        $this->actingAs($user)->postJson(route('b2b/contracts/store'), $base + ['rules' => [
            ['scope' => 'all', 'discount_type' => 'percent', 'discount_value' => 5, 'min_qty' => 1],
            ['scope' => 'all', 'discount_type' => 'nominal', 'discount_value' => 500, 'min_qty' => 1],
        ]])->assertUnprocessable()->assertJsonValidationErrors('rules.1.min_qty');

        $this->actingAs($user)->postJson(route('b2b/contracts/store'), $base)
            ->assertUnprocessable()->assertJsonValidationErrors('rules');
    }

    public function test_activating_on_store_requires_activate_permission(): void
    {
        $user = $this->userFor(null, ['create b2b/contracts']);

        $this->actingAs($user)->postJson(route('b2b/contracts/store'), [
            'b2b_customer_id' => $this->customer()->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addMonth()->toDateString(),
            'activate' => 1,
            'rules' => [['scope' => 'all', 'discount_type' => 'percent', 'discount_value' => 5, 'min_qty' => 1]],
        ])->assertForbidden();
    }

    public function test_terminate_requires_reason(): void
    {
        $user = $this->userFor(null, ['terminate b2b/contracts']);
        $contract = $this->contract($this->customer());

        $this->actingAs($user)->postJson(route('b2b/contracts/terminate', $contract->id), [])
            ->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->actingAs($user)->postJson(route('b2b/contracts/terminate', $contract->id), ['reason' => 'Selesai kerja sama'])
            ->assertOk();
        $this->assertSame(Contract::STATUS_TERMINATED, $contract->fresh()->status);
    }

    public function test_cashier_quote_and_store_flow(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet, ['read b2b/cashier', 'create b2b/cashier']);
        $variant = $this->variant($outlet, 20000, 50);
        $customer = $this->customer();
        $this->contract($customer, [['scope' => 'variant', 'variant_id' => $variant->id, 'discount_type' => 'nominal', 'discount_value' => 2500]]);

        $this->actingAs($user)->getJson(route('b2b/cashier/products', ['outlet_id' => $outlet->id]))
            ->assertOk()->assertJsonFragment(['id' => $variant->id, 'price' => 20000, 'stock' => 50]);
        $this->actingAs($user)->getJson(route('b2b/cashier/customer', $customer->id))
            ->assertOk()->assertJsonPath('data.contract.rules_count', 1);

        $payload = ['outlet_id' => $outlet->id, 'b2b_customer_id' => $customer->id, 'items' => [['variant_id' => $variant->id, 'qty' => 4]]];
        $this->actingAs($user)->postJson(route('b2b/cashier/quote'), $payload)
            ->assertOk()
            ->assertJsonPath('data.items.0.final_price', 17500)
            ->assertJsonPath('data.grand_total', 70000)
            ->assertJsonPath('data.discount_total', 10000);

        $this->actingAs($user)->postJson(route('b2b/cashier/store'), $payload + ['delivery_method' => 'pickup'])
            ->assertUnprocessable()->assertJsonValidationErrors('due_date');
        $this->actingAs($user)->postJson(route('b2b/cashier/store'), $payload + ['delivery_method' => 'pickup', 'due_date' => today()->subDay()->toDateString()])
            ->assertUnprocessable()->assertJsonValidationErrors('due_date');

        $response = $this->actingAs($user)->postJson(route('b2b/cashier/store'), $payload + ['delivery_method' => 'pickup', 'due_date' => today()->addDays(10)->toDateString()])
            ->assertOk();
        $order = Order::findOrFail($response->json('data.id'));
        $this->assertSame(70000, $order->grand_total);
        $this->assertSame(today()->addDays(10)->toDateString(), $order->invoice->due_date->toDateString());
        $this->assertSame(46, (int) $variant->fresh()->stok);
    }

    public function test_cashier_rejects_outlet_the_user_cannot_access_and_duplicate_lines(): void
    {
        $outlet = $this->outlet('mine');
        $other = $this->outlet('other');
        $user = $this->userFor($outlet, ['read b2b/cashier', 'create b2b/cashier']);
        $variant = $this->variant($other);
        $customer = $this->customer();

        $this->actingAs($user)->getJson(route('b2b/cashier/products', ['outlet_id' => $other->id]))->assertForbidden();
        $this->actingAs($user)->postJson(route('b2b/cashier/store'), [
            'outlet_id' => $other->id, 'b2b_customer_id' => $customer->id, 'delivery_method' => 'pickup', 'due_date' => today()->addDays(7)->toDateString(),
            'items' => [['variant_id' => $variant->id, 'qty' => 1]],
        ])->assertUnprocessable()->assertJsonValidationErrors('outlet_id');

        $own = $this->variant($outlet);
        $this->actingAs($user)->postJson(route('b2b/cashier/store'), [
            'outlet_id' => $outlet->id, 'b2b_customer_id' => $customer->id, 'delivery_method' => 'pickup', 'due_date' => today()->addDays(7)->toDateString(),
            'items' => [['variant_id' => $own->id, 'qty' => 1], ['variant_id' => $own->id, 'qty' => 2]],
        ])->assertUnprocessable()->assertJsonValidationErrors('items.0.variant_id');
    }

    public function test_quote_only_user_cannot_place_orders(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet, ['read b2b/cashier']);
        $variant = $this->variant($outlet);
        $payload = ['outlet_id' => $outlet->id, 'b2b_customer_id' => $this->customer()->id, 'items' => [['variant_id' => $variant->id, 'qty' => 1]]];

        $this->actingAs($user)->postJson(route('b2b/cashier/quote'), $payload)->assertOk();
        $this->actingAs($user)->postJson(route('b2b/cashier/store'), $payload + ['delivery_method' => 'pickup', 'due_date' => today()->toDateString()])->assertForbidden();
    }

    public function test_orders_and_invoices_are_scoped_to_user_outlets(): void
    {
        $outlet = $this->outlet('A');
        $other = $this->outlet('B');
        $owner = $this->userFor($other, self::ALL);
        $variant = $this->variant($other);
        $order = app(\App\Services\B2b\OrderService::class)->place($owner, [
            'outlet_id' => $other->id, 'b2b_customer_id' => $this->customer()->id, 'delivery_method' => 'pickup', 'due_date' => today()->addDays(7)->toDateString(),
            'items' => [['variant_id' => $variant->id, 'qty' => 1]],
        ]);
        $stranger = $this->userFor($outlet, self::ALL);

        $this->actingAs($stranger)->get(route('b2b/orders/show', $order->id))->assertNotFound();
        $this->actingAs($stranger)->get(route('b2b/invoices/print', $order->invoice->id))->assertNotFound();
        $this->actingAs($stranger)->postJson(route('b2b/orders/cancel', $order->id), ['reason' => 'x'])->assertNotFound();

        $this->actingAs($owner)->get(route('b2b/orders/show', $order->id))->assertOk()->assertSee($order->order_number);
        $this->actingAs($owner)->get(route('b2b/invoices/print', $order->invoice->id))->assertOk()->assertSee($order->invoice->invoice_number);
    }

    public function test_order_cancel_and_complete_endpoints(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet, ['complete b2b/orders', 'cancel b2b/orders']);
        $variant = $this->variant($outlet, 10000, 10);
        $place = fn () => app(\App\Services\B2b\OrderService::class)->place($user, [
            'outlet_id' => $outlet->id, 'b2b_customer_id' => $this->customer()->id, 'delivery_method' => 'pickup', 'due_date' => today()->addDays(7)->toDateString(),
            'items' => [['variant_id' => $variant->id, 'qty' => 3]],
        ]);
        $toCancel = $place();
        $toComplete = $place();

        $this->actingAs($user)->postJson(route('b2b/orders/cancel', $toCancel->id), [])->assertUnprocessable();
        $this->actingAs($user)->postJson(route('b2b/orders/cancel', $toCancel->id), ['reason' => 'Salah customer'])->assertOk();
        $this->actingAs($user)->postJson(route('b2b/orders/complete', $toComplete->id))->assertOk();

        $this->assertSame(Order::STATUS_CANCELLED, $toCancel->fresh()->status);
        $this->assertSame(Order::STATUS_COMPLETED, $toComplete->fresh()->status);
        $this->assertSame(7, (int) $variant->fresh()->stok);
    }

    public function test_detail_pages_render(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet, self::ALL);
        $customer = $this->customer();
        $customer->addresses()->create(['label' => 'Kantor', 'address' => 'Jl. A', 'is_default_billing' => true]);
        $contract = $this->contract($customer, [['scope' => 'all', 'discount_value' => 5]]);
        $draft = $this->contract($customer, [['scope' => 'all', 'discount_value' => 6]], [
            'status' => Contract::STATUS_DRAFT, 'start_date' => today()->addMonths(2), 'end_date' => today()->addMonths(3),
        ]);

        $this->actingAs($user)->get(route('b2b/customers/show', $customer->id))->assertOk()->assertSee($contract->contract_number);
        $this->actingAs($user)->get(route('b2b/customers/edit', $customer->id))->assertOk()->assertSee('Kantor');
        $this->actingAs($user)->get(route('b2b/contracts/show', $contract->id))->assertOk()->assertSee('Diskon 5%');
        $this->actingAs($user)->get(route('b2b/contracts/edit', $draft->id))->assertOk();
        $this->actingAs($user)->get(route('b2b/contracts/edit', $contract->id))->assertForbidden();
        $this->actingAs($user)->get(route('b2b/contracts/create', ['copy_from' => $contract->id]))->assertOk()->assertSee('Disalin dari');
    }
}

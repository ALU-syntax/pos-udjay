<?php

namespace Tests\Feature\B2b;

use App\Models\B2b\ActivityLog;
use App\Models\B2b\Contract;
use App\Models\B2b\Customer;
use App\Models\B2b\CustomerAccount;
use App\Models\B2b\Order;
use Database\Seeders\B2bMenuSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class B2bHttpTest extends B2bTestCase
{
    private const ALL = [
        'read b2b', 'read b2b/customers', 'create b2b/customers', 'update b2b/customers', 'delete b2b/customers',
        'read b2b/contracts', 'create b2b/contracts', 'update b2b/contracts', 'delete b2b/contracts', 'terminate b2b/contracts',
        'read b2b/cashier', 'create b2b/cashier', 'read b2b/orders', 'complete b2b/orders', 'cancel b2b/orders', 'read b2b/invoices', 'pay b2b/invoices',
    ];

    public function test_menu_seeder_registers_menus_and_permissions_idempotently(): void
    {
        $this->seed(B2bMenuSeeder::class);
        $this->seed(B2bMenuSeeder::class);

        $this->assertDatabaseHas('menus', ['url' => 'b2b', 'category' => 'B2B']);
        foreach (['b2b/cashier', 'b2b/orders', 'b2b/invoices', 'b2b/customers', 'b2b/contracts'] as $url) {
            $this->assertSame(1, \App\Models\Menu::where('url', $url)->count());
        }
        $this->assertDatabaseHas('permissions', ['name' => 'terminate b2b/contracts']);
        $this->assertDatabaseHas('permissions', ['name' => 'cancel b2b/orders']);
        $this->assertDatabaseHas('permissions', ['name' => 'cancel-completed b2b/orders']);
        $this->assertDatabaseHas('permissions', ['name' => 'pay b2b/invoices']);
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

    public function test_customer_store_creates_code_and_saves_contact_and_address(): void
    {
        $user = $this->userFor(null, ['create b2b/customers']);
        $this->seedWilayah();

        $response = $this->actingAs($user)->postJson(route('b2b/customers/store'), [
            'type' => 'pt',
            'name' => '  PT Maju Jaya  ',
            'email' => 'FINANCE@MAJU.CO.ID',
            'phone' => '0812345',
            'contact_name' => 'Budi',
            'address' => 'Jl. Sudirman 1',
            'province_code' => '32',
            'regency_code' => '32.01',
            'district_code' => '32.01.01',
            'village_code' => '32.01.01.1001',
            'postal_code' => '16914',
            'is_active' => 1,
        ])->assertOk();

        $customer = Customer::findOrFail($response->json('data.id'));
        $this->assertSame('PT Maju Jaya', $customer->name);
        $this->assertSame($user->id, $customer->created_by);
        $this->assertNull($customer->modified_by);
        $this->assertSame('pt', $customer->type);
        $this->assertSame('PT', $customer->typeLabel());
        $this->assertSame('finance@maju.co.id', $customer->email);
        $this->assertMatchesRegularExpression('/^CUS-B2B-\d{4,}$/', $customer->code);
        $this->assertSame('Budi', $customer->contact_name);
        $this->assertSame('32.01.01.1001', $customer->village_code);
        $this->assertSame('Jl. Sudirman 1, Pondok Rajeg, Kec. Cibinong, Kabupaten Bogor, Jawa Barat 16914', $customer->fullAddress());
    }

    public function test_customer_accounts_are_normalized_and_synced_with_the_form(): void
    {
        $user = $this->userFor(null, ['create b2b/customers', 'update b2b/customers', 'read b2b/customers']);

        $id = $this->actingAs($user)->postJson(route('b2b/customers/store'), [
            'type' => 'pt', 'name' => 'PT Rekening',
            'accounts' => [
                ['provider' => 'bca', 'account_number' => '123-456 7890', 'account_name' => ' PT Rekening ', 'notes' => ''],
                ['provider' => '', 'account_number' => '', 'account_name' => '', 'notes' => ''], // baris kosong diabaikan
                ['provider' => 'gopay', 'account_number' => '+62 812-3456-7890', 'account_name' => 'Budi'],
            ],
        ])->assertOk()->json('data.id');

        $customer = Customer::findOrFail($id);
        $this->assertSame(
            [['bank', 'bca', '1234567890', 'PT Rekening', null], ['ewallet', 'gopay', '081234567890', 'Budi', null]],
            $customer->accounts->map(fn ($account) => [$account->type, $account->provider, $account->account_number, $account->account_name, $account->notes])->all()
        );
        $bcaId = $customer->accounts->first()->id;
        $this->assertSame(['BCA 1234567890 a.n. PT Rekening', 'GoPay 081234567890 a.n. Budi'], ActivityLog::query()
            ->where('action', 'customer.created')->where('subject_id', $id)->first()->changes['accounts']);

        // Edit: BCA tetap (nama diubah, id sama), GoPay dihapus, Mandiri ditambah.
        $this->actingAs($user)->putJson(route('b2b/customers/update', $id), [
            'type' => 'pt', 'name' => 'PT Rekening',
            'accounts' => [
                ['provider' => 'bca', 'account_number' => '1234567890', 'account_name' => 'PT Rekening Baru', 'notes' => 'Rekening utama'],
                ['provider' => 'mandiri', 'account_number' => '9876543210', 'account_name' => 'PT Rekening'],
            ],
        ])->assertOk();
        $accounts = $customer->accounts()->get();
        $this->assertSame(['bca', 'mandiri'], $accounts->pluck('provider')->all());
        $this->assertSame($bcaId, $accounts->first()->id);
        $this->assertSame(['PT Rekening Baru', 'Rekening utama'], [$accounts->first()->account_name, $accounts->first()->notes]);
        $log = ActivityLog::query()->where('action', 'customer.updated')->where('subject_id', $id)->latest('id')->first()->changes;
        $this->assertSame(['BCA 1234567890 a.n. PT Rekening', 'GoPay 081234567890 a.n. Budi'], $log['before']['accounts']);
        $this->assertSame(['BCA 1234567890 a.n. PT Rekening Baru', 'Mandiri 9876543210 a.n. PT Rekening'], $log['after']['accounts']);

        // Detail customer menampilkan rekening (nomor dikelompokkan per 4 digit).
        $this->actingAs($user)->get(route('b2b/customers/show', $id))->assertOk()
            ->assertSeeInOrder(['Rekening Pengirim', 'BCA', '1234 5678 90', 'a.n. PT Rekening Baru', 'Rekening utama', 'Mandiri', '9876 5432 10']);

        // Form tanpa baris rekening = semua rekening dihapus.
        $this->actingAs($user)->putJson(route('b2b/customers/update', $id), ['type' => 'pt', 'name' => 'PT Rekening'])->assertOk();
        $this->assertSame(0, $customer->accounts()->count());
        $this->actingAs($user)->get(route('b2b/customers/show', $id))->assertOk()->assertSee('Belum ada rekening terdaftar');

        $this->actingAs($user)->get(route('b2b/customers/edit', $id))->assertOk()->assertSee('Rekening &amp; E-wallet Pengirim', false);
    }

    public function test_customer_account_rules_and_one_owner_per_account(): void
    {
        $user = $this->userFor(null, ['create b2b/customers', 'update b2b/customers', 'delete b2b/customers']);
        $owner = $this->customer(['name' => 'PT Pemilik Rekening']);
        $owner->accounts()->create(['type' => 'bank', 'provider' => 'bca', 'account_number' => '1111222233', 'account_name' => 'PT Pemilik']);
        $store = fn (array $accounts) => $this->actingAs($user)->postJson(route('b2b/customers/store'), ['type' => 'pt', 'name' => 'PT Baru', 'accounts' => $accounts]);
        $row = fn (string $provider, string $number) => ['provider' => $provider, 'account_number' => $number, 'account_name' => 'PT Baru'];

        // Hanya dari daftar baku, nomor angka, nama wajib.
        $store([['provider' => 'bank-lain', 'account_number' => '12', 'account_name' => '']])->assertUnprocessable()
            ->assertJsonValidationErrors(['accounts.0.provider', 'accounts.0.account_number', 'accounts.0.account_name']);
        // E-wallet harus nomor HP.
        $store([$row('ovo', '1234567890')])->assertUnprocessable()->assertJsonValidationErrors(['accounts.0.account_number']);
        // Dobel di form yang sama.
        $store([$row('bni', '5555666677'), $row('bni', '5555-6666-77')])->assertUnprocessable()
            ->assertJsonValidationErrors(['accounts.1.account_number' => 'baris 1']);
        // Maksimal per customer.
        $many = array_map(fn ($i) => $row('bri', '70000000'.$i), range(10, 10 + CustomerAccount::MAX_PER_CUSTOMER));
        $store($many)->assertUnprocessable()->assertJsonValidationErrors(['accounts']);

        // Rekening milik customer lain ditolak dan menyebut pemiliknya; nomor sama di bank lain boleh.
        $store([$row('bca', '1111-2222-33')])->assertUnprocessable()
            ->assertJsonValidationErrors(['accounts.0.account_number' => 'PT Pemilik Rekening ('.$owner->code.')']);
        $this->assertDatabaseMissing('b2b_customers', ['name' => 'PT Baru']);
        $store([$row('mandiri', '1111222233')])->assertOk();

        // Pemilik sendiri boleh menyimpan ulang rekeningnya.
        $this->actingAs($user)->putJson(route('b2b/customers/update', $owner->id), [
            'type' => 'pt', 'name' => $owner->name, 'accounts' => [['provider' => 'bca', 'account_number' => '1111222233', 'account_name' => 'PT Pemilik']],
        ])->assertOk();

        // Customer dihapus → rekeningnya dilepas dan bisa didaftarkan ke customer lain.
        $this->actingAs($user)->deleteJson(route('b2b/customers/destroy', $owner->id))->assertOk();
        $this->assertDatabaseMissing('b2b_customer_accounts', ['b2b_customer_id' => $owner->id]);
        $this->actingAs($user)->postJson(route('b2b/customers/store'), ['type' => 'pt', 'name' => 'PT Penerus', 'accounts' => [$row('bca', '1111222233')]])->assertOk();
    }

    public function test_customer_region_must_follow_the_hierarchy(): void
    {
        $user = $this->userFor(null, ['create b2b/customers']);
        $this->seedWilayah();
        $store = fn (array $region) => $this->actingAs($user)->postJson(route('b2b/customers/store'), ['type' => 'pt', 'name' => 'PT Wilayah'] + $region);

        // Kota/kabupaten dari provinsi lain.
        $store(['province_code' => '32', 'regency_code' => '31.71'])->assertUnprocessable()->assertJsonValidationErrors('regency_code');
        // Kelurahan dari kecamatan lain (Pondok Rajeg ada di Cibinong, bukan Gunungputri).
        $store(['province_code' => '32', 'regency_code' => '32.01', 'district_code' => '32.01.02', 'village_code' => '32.01.01.1001'])
            ->assertUnprocessable()->assertJsonValidationErrors('village_code');
        // Tingkat di atasnya wajib diisi.
        $store(['district_code' => '32.01.01'])->assertUnprocessable()->assertJsonValidationErrors(['province_code', 'regency_code']);
        // Kode yang tidak ada.
        $store(['province_code' => '99'])->assertUnprocessable()->assertJsonValidationErrors('province_code');
        // Boleh berhenti di tingkat mana pun.
        $store(['province_code' => '32', 'regency_code' => '32.01'])->assertOk();
    }

    public function test_region_selector_returns_children_of_a_parent(): void
    {
        $this->seedWilayah();
        $url = fn (array $query) => route('b2b/selectors/regions', $query);

        $this->actingAs($this->userFor())->getJson($url(['level' => 'districts', 'parent' => '32.01']))->assertForbidden();

        $user = $this->userFor(null, ['update b2b/customers']);
        $data = $this->actingAs($user)->getJson($url(['level' => 'districts', 'parent' => '32.01']))->assertOk()->json('data');
        $this->assertContains(['id' => '32.01.01', 'text' => 'Cibinong'], $data);
        $this->assertContains(['id' => '32.01.02', 'text' => 'Gunungputri'], $data);
        $this->assertNotContains('31.71.01', array_column($data, 'id'));
        $this->assertArrayNotHasKey('postal_code', $data[0]);

        // Kelurahan/desa membawa kode pos untuk mengisi kode pos otomatis.
        $villages = $this->actingAs($user)->getJson($url(['level' => 'villages', 'parent' => '32.01.01']))->assertOk()->json('data');
        $this->assertContains(['id' => '32.01.01.1001', 'text' => 'Pondok Rajeg', 'postal_code' => '16914'], $villages);

        $this->actingAs($user)->getJson($url(['level' => 'provinces', 'parent' => '32']))->assertUnprocessable();
    }

    public function test_customer_edit_form_preselects_saved_region(): void
    {
        $this->seedWilayah();
        $user = $this->userFor(null, ['update b2b/customers']);
        $customer = $this->customer(['province_code' => '32', 'regency_code' => '32.01', 'district_code' => '32.01.01', 'village_code' => '32.01.01.1001']);

        $html = $this->actingAs($user)->get(route('b2b/customers/edit', $customer->id))
            ->assertOk()
            ->assertSee('Gunungputri')
            ->getContent();
        $this->assertMatchesRegularExpression('/value="32\.01\.01\.1001"\s+data-postal-code="16914"\s+selected>Pondok Rajeg</', $html);
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
            'email' => 'finance@rajin.test',
            'phone' => '0812',
        ])->assertOk();

        $customer = Customer::findOrFail($response->json('data.id'));
        $this->assertTrue($customer->reminder_enabled);
        $this->assertSame(['email', 'whatsapp'], $customer->reminder_channels);
        $this->assertSame([-3, 0], $customer->reminder_days);
        $this->assertTrue($customer->reminder_overdue_daily);
        $this->assertSame('Email + WhatsApp · H-3, Hari H, harian setelah jatuh tempo', $customer->reminderSummary());
    }

    public function test_enabled_reminder_must_have_channel_schedule_and_reachable_destination(): void
    {
        $user = $this->userFor(null, ['create b2b/customers']);
        $store = fn (array $data) => $this->actingAs($user)->postJson(route('b2b/customers/store'), array_merge(['type' => 'pt', 'name' => 'PT Uji', 'reminder_enabled' => 1], $data));

        $store([])->assertUnprocessable()->assertJsonValidationErrors(['reminder_channels', 'reminder_days']);
        $store(['reminder_channels' => ['fax'], 'reminder_days' => [5]])
            ->assertUnprocessable()->assertJsonValidationErrors(['reminder_channels.0', 'reminder_days.0']);
        $store(['reminder_channels' => ['email'], 'reminder_days' => [-1], 'phone' => '0812'])
            ->assertUnprocessable()->assertJsonValidationErrors('email')->assertJsonMissingValidationErrors('phone');
        $store(['reminder_channels' => ['whatsapp'], 'reminder_overdue_daily' => 1, 'email' => 'a@b.test'])
            ->assertUnprocessable()->assertJsonValidationErrors('phone')->assertJsonMissingValidationErrors('email');
        $store(['reminder_channels' => ['email', 'whatsapp'], 'reminder_days' => [0], 'email' => 'a@b.test', 'phone' => '0812'])->assertOk();

        // Reminder mati: pengaturan kosong tidak divalidasi.
        $store(['reminder_enabled' => 0])->assertOk();
    }

    public function test_customer_update_saves_contact_and_address(): void
    {
        $user = $this->userFor(null, ['update b2b/customers']);
        $customer = $this->customer(['contact_name' => 'Lama', 'address' => 'Jl. Lama']);
        $this->seedWilayah();

        $this->actingAs($user)->putJson(route('b2b/customers/update', $customer->id), [
            'type' => 'pt', 'name' => 'PT Baru', 'contact_name' => 'Baru', 'address' => 'Jl. Baru 2', 'province_code' => '32',
        ])->assertOk();

        $customer->refresh();
        $this->assertSame('Baru', $customer->contact_name);
        $this->assertSame('Jl. Baru 2, Jawa Barat', $customer->fullAddress());
        $this->assertSame($user->id, $customer->modified_by);

        $this->actingAs($user)->putJson(route('b2b/customers/update', $customer->id), [
            'type' => 'pt', 'name' => 'PT Baru', 'postal_code' => '12345678901',
        ])->assertUnprocessable()->assertJsonValidationErrors('postal_code');
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

    public function test_contract_store_saves_item_prices_and_attachment(): void
    {
        Storage::fake('local');
        $outlet = $this->outlet();
        $otherOutlet = $this->outlet('B');
        $variant = $this->variant($outlet, 40000);
        $otherVariant = $this->variant($otherOutlet, 40000);
        $user = $this->userFor(null, ['create b2b/contracts']);
        $customer = $this->customer();

        $response = $this->actingAs($user)->post(route('b2b/contracts/store'), [
            'b2b_customer_id' => $customer->id,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addYear()->toDateString(),
            'attachment' => UploadedFile::fake()->create('kontrak.pdf', 100, 'application/pdf'),
            'items' => [
                ['variant_id' => $variant->id, 'min_qty' => 1, 'price' => 38000],
                ['variant_id' => $variant->id, 'min_qty' => 10, 'price' => 36000],
                ['variant_id' => $otherVariant->id, 'min_qty' => 1, 'price' => 42000],
            ],
        ], ['Accept' => 'application/json'])->assertOk();

        $contract = Contract::with('items')->findOrFail($response->json('data.id'));
        $this->assertSame(Contract::STATUS_ACTIVE, $contract->status);
        Storage::disk('local')->assertExists($contract->attachment_path);
        $this->assertCount(3, $contract->items);
        $this->assertSame(42000, $contract->items->firstWhere('variant_id', $otherVariant->id)->price);
    }

    public function test_contract_update_can_remove_saved_attachment(): void
    {
        Storage::fake('local');
        $variant = $this->variant($this->outlet());
        $user = $this->userFor(null, ['update b2b/contracts']);
        $contract = $this->contract($this->customer(), [['variant_id' => $variant->id, 'price' => 9000]]);
        Storage::disk('local')->put('b2b/contracts/'.$contract->id.'/lama.pdf', 'isi');
        $contract->forceFill(['attachment_path' => 'b2b/contracts/'.$contract->id.'/lama.pdf', 'attachment_name' => 'lama.pdf'])->save();
        $payload = [
            'start_date' => $contract->start_date->toDateString(), 'end_date' => $contract->end_date->toDateString(),
            'items' => [['id' => $contract->items->first()->id, 'variant_id' => $variant->id, 'min_qty' => 1, 'price' => 9000]],
        ];

        // File baru menang atas permintaan hapus: file lama diganti, bukan dikosongkan.
        $this->actingAs($user)->put(route('b2b/contracts/update', $contract->id), $payload + [
            'remove_attachment' => '1', 'attachment' => UploadedFile::fake()->create('baru.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertOk();
        $contract->refresh();
        $this->assertSame('baru.pdf', $contract->attachment_name);
        Storage::disk('local')->assertMissing('b2b/contracts/'.$contract->id.'/lama.pdf');

        $newPath = $contract->attachment_path;
        $this->actingAs($user)->putJson(route('b2b/contracts/update', $contract->id), $payload + ['remove_attachment' => '1'])->assertOk();
        $contract->refresh();
        $this->assertNull($contract->attachment_path);
        $this->assertNull($contract->attachment_name);
        Storage::disk('local')->assertMissing($newPath);
    }

    public function test_contract_store_rejects_missing_invalid_and_duplicate_items(): void
    {
        $user = $this->userFor(null, ['create b2b/contracts', 'update b2b/contracts']);
        $customer = $this->customer();
        $variant = $this->variant($this->outlet());
        $base = ['b2b_customer_id' => $customer->id, 'start_date' => today()->toDateString(), 'end_date' => today()->addMonth()->toDateString()];

        $this->actingAs($user)->postJson(route('b2b/contracts/store'), $base)
            ->assertUnprocessable()->assertJsonValidationErrors('items');
        $this->actingAs($user)->postJson(route('b2b/contracts/store'), $base + ['items' => [
            ['variant_id' => $variant->id, 'min_qty' => 0, 'price' => -1],
        ]])->assertUnprocessable()->assertJsonValidationErrors(['items.0.min_qty', 'items.0.price']);
        $this->actingAs($user)->postJson(route('b2b/contracts/store'), $base + ['items' => [
            ['variant_id' => $variant->id, 'min_qty' => 5, 'price' => 9000],
            ['variant_id' => $variant->id, 'min_qty' => 5, 'price' => 8000],
        ]])->assertUnprocessable()->assertJsonValidationErrors('items.1.min_qty');
        $this->actingAs($user)->postJson(route('b2b/contracts/store'), array_merge($base, ['end_date' => today()->subDay()->toDateString()]) + ['items' => [
            ['variant_id' => $variant->id, 'min_qty' => 1, 'price' => 9000],
        ]])->assertUnprocessable()->assertJsonValidationErrors('end_date');

        // Customer kontrak tidak bisa diganti lewat edit.
        $contract = $this->contract($customer, [['variant_id' => $variant->id, 'price' => 9000]]);
        $this->actingAs($user)->putJson(route('b2b/contracts/update', $contract->id), [
            'b2b_customer_id' => $this->customer()->id, 'start_date' => today()->toDateString(), 'end_date' => today()->addMonth()->toDateString(),
            'items' => [['variant_id' => $variant->id, 'min_qty' => 1, 'price' => 9000]],
        ])->assertUnprocessable()->assertJsonValidationErrors('b2b_customer_id');
    }

    public function test_renewing_contract_skips_deleted_variants_and_new_contract_rejects_them(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor(null, ['create b2b/contracts', 'update b2b/contracts']);
        $customer = $this->customer();
        $kept = $this->variant($outlet, name: 'Kopi Tetap');
        $deletedVariant = $this->variant($outlet, name: 'Teh Terhapus');
        $deletedProduct = $this->variant($outlet, name: 'Roti Terhapus');
        $contract = $this->contract($customer, [
            ['variant_id' => $kept->id, 'price' => 9000],
            ['variant_id' => $deletedVariant->id, 'price' => 8000],
            ['variant_id' => $deletedVariant->id, 'min_qty' => 10, 'price' => 7500],
            ['variant_id' => $deletedProduct->id, 'price' => 7000],
        ]);
        $deletedVariant->delete();
        $deletedProduct->product->delete();

        // Dua produk (tiga baris harga) tidak ikut disalin.
        $this->actingAs($user)->get(route('b2b/contracts/create', ['copy_from' => $contract->id]))->assertOk()
            ->assertSee('2 produk tidak disalin')->assertSee('Kopi Tetap')
            ->assertDontSee('Teh Terhapus')->assertDontSee('Roti Terhapus');

        $period = ['start_date' => today()->addMonth()->toDateString(), 'end_date' => today()->addMonths(2)->toDateString()];
        $this->actingAs($user)->postJson(route('b2b/contracts/store'), $period + [
            'b2b_customer_id' => $customer->id,
            'items' => [
                ['variant_id' => $kept->id, 'min_qty' => 1, 'price' => 9000],
                ['variant_id' => $deletedVariant->id, 'min_qty' => 1, 'price' => 8000],
                ['variant_id' => $deletedProduct->id, 'min_qty' => 1, 'price' => 7000],
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors(['items.1.variant_id', 'items.2.variant_id'])
            ->assertJsonMissingValidationErrors('items.0.variant_id');

        // Kontrak lama yang sudah berisi produk terhapus tetap bisa diedit tanpa membuang barisnya.
        $this->actingAs($user)->putJson(route('b2b/contracts/update', $contract->id), [
            'start_date' => $contract->start_date->toDateString(), 'end_date' => $contract->end_date->toDateString(),
            'items' => $contract->items->map(fn ($item) => [
                'id' => $item->id, 'variant_id' => $item->variant_id, 'min_qty' => $item->min_qty, 'price' => $item->price - 100,
            ])->all(),
        ])->assertOk();
        $this->assertSame(8900, $contract->items()->where('variant_id', $kept->id)->value('price'));
    }

    public function test_renewed_contract_links_to_successor_instead_of_renew_button(): void
    {
        $user = $this->userFor(null, ['read b2b/contracts', 'create b2b/contracts']);
        $renewed = $this->customer();
        $old = $this->contract($renewed);
        $next = $this->contract($renewed, [], ['start_date' => today()->addDays(21), 'end_date' => today()->addDays(60)]);
        // Kontrak dihentikan lalu diganti kontrak baru yang mulai sebelum tanggal berakhir kontrak lama.
        $replaced = $this->customer();
        $stopped = $this->contract($replaced, [], ['status' => Contract::STATUS_TERMINATED, 'start_date' => today()->subDays(30), 'end_date' => today()->addDays(30)]);
        $replacement = $this->contract($replaced, [], ['start_date' => today()->subDays(5), 'end_date' => today()->addDays(60)]);
        $alone = $this->contract($this->customer());
        $renewUrl = fn (Contract $contract) => route('b2b/contracts/create', ['copy_from' => $contract->id]);

        foreach ([[$old, $next], [$stopped, $replacement], [$next, null], [$alone, null]] as [$contract, $successor]) {
            $page = $this->actingAs($user)->get(route('b2b/contracts/show', $contract->id))->assertOk();
            $successor
                ? $page->assertSee('Lanjutan: '.$successor->contract_number)->assertDontSee($renewUrl($contract))
                : $page->assertSee($renewUrl($contract))->assertDontSee('Lanjutan:');

            $this->app->forgetInstance('datatables.request');
            $rows = collect($this->actingAs($user)->getJson(
                route('b2b/contracts', ['draw' => 1, 'start' => 0, 'length' => 10, 'customer_id' => $contract->b2b_customer_id]),
                ['X-Requested-With' => 'XMLHttpRequest'],
            )->assertOk()->json('data'))->keyBy('DT_RowId');
            $action = $rows[$contract->id]['action'];
            $successor
                ? $this->assertStringContainsString(route('b2b/contracts/show', $successor->id), $action)
                : $this->assertStringContainsString($renewUrl($contract), $action);
            $this->assertSame(! $successor, str_contains($action, $renewUrl($contract)));
        }
    }

    public function test_renewed_contract_records_and_shows_source_contract(): void
    {
        $user = $this->userFor(null, ['read b2b/contracts', 'create b2b/contracts', 'update b2b/contracts']);
        $customer = $this->customer();
        $variant = $this->variant($this->outlet());
        $old = $this->contract($customer, [['variant_id' => $variant->id, 'price' => 9000]], [
            'start_date' => today()->subMonths(3), 'end_date' => today()->subDay(),
        ]);
        $items = [['variant_id' => $variant->id, 'min_qty' => 1, 'price' => 9500]];
        $store = fn (array $data) => $this->actingAs($user)->postJson(route('b2b/contracts/store'), $data + [
            'start_date' => today()->toDateString(), 'end_date' => today()->addMonth()->toDateString(), 'items' => $items,
        ])->assertOk()->json('data.id');
        $sourceInLog = fn (int $id) => ActivityLog::query()
            ->where('subject_type', (new Contract)->getMorphClass())->where('subject_id', $id)
            ->where('action', 'contract.created')->value('changes')['copied_from'] ?? null;

        $this->actingAs($user)->get(route('b2b/contracts/create', ['copy_from' => $old->id]))->assertOk()
            ->assertSee('name="copied_from" value="'.$old->id.'"', false);

        $renewedId = $store(['b2b_customer_id' => $customer->id, 'copied_from' => $old->id]);
        $this->assertSame(['id' => $old->id, 'contract_number' => $old->contract_number], $sourceInLog($renewedId));
        $this->actingAs($user)->get(route('b2b/contracts/show', $renewedId))->assertOk()
            ->assertSee('Diperpanjang dari')->assertSee(route('b2b/contracts/show', $old->id))->assertDontSee('sudah dihapus');

        // Kontrak asal yang sudah dihapus tetap tampil nomornya, tanpa tautan.
        $old->delete();
        $this->actingAs($user)->get(route('b2b/contracts/show', $renewedId))->assertOk()
            ->assertSee($old->contract_number)->assertSee('sudah dihapus')->assertDontSee(route('b2b/contracts/show', $old->id));

        // Customer diganti di form = bukan perpanjangan, jadi asal tidak dicatat.
        $otherId = $store(['b2b_customer_id' => $this->customer()->id, 'copied_from' => $this->contract($customer, [], [
            'start_date' => today()->addMonths(2), 'end_date' => today()->addMonths(3),
        ])->id]);
        $this->assertNull($sourceInLog($otherId));
        $this->actingAs($user)->get(route('b2b/contracts/show', $otherId))->assertOk()->assertDontSee('Diperpanjang dari');

        $this->actingAs($user)->putJson(route('b2b/contracts/update', $renewedId), [
            'copied_from' => $old->id, 'start_date' => today()->toDateString(), 'end_date' => today()->addMonth()->toDateString(), 'items' => $items,
        ])->assertUnprocessable()->assertJsonValidationErrors('copied_from');
    }

    public function test_new_contract_form_offers_copying_from_customer_last_contract(): void
    {
        $user = $this->userFor(null, ['create b2b/contracts', 'update b2b/contracts']);
        $outlet = $this->outlet();
        [$coffee, $tea] = [$this->variant($outlet), $this->variant($outlet, name: 'Teh')];
        $customer = $this->customer();
        $older = $this->contract($customer, [['variant_id' => $coffee->id, 'price' => 9000]], [
            'start_date' => today()->subYear(), 'end_date' => today()->subMonths(6),
        ]);
        // Kontrak terakhir = tanggal mulai paling baru, walau sudah dihentikan.
        $last = $this->contract($customer, [
            ['variant_id' => $coffee->id, 'price' => 9000],
            ['variant_id' => $coffee->id, 'min_qty' => 10, 'price' => 8500],
            ['variant_id' => $tea->id, 'price' => 7000],
        ], ['status' => Contract::STATUS_TERMINATED, 'start_date' => today()->subMonths(5), 'end_date' => today()->addMonth()]);
        $newcomer = $this->customer();
        $copyParam = fn (Contract $contract) => 'copy_from='.$contract->id;
        $offers = fn ($response) => json_decode(str($response->getContent())->after('const lastContracts = ')->before(";\n")->toString(), true);

        $page = $this->actingAs($user)->get(route('b2b/contracts/create', ['customer_id' => $customer->id]))->assertOk()
            ->assertSee('id="last-contract"', false);
        $offer = $offers($page)[$customer->id];
        $this->assertSame([$last->contract_number, 'Dihentikan', 2], [$offer['number'], $offer['status'], $offer['products']]);
        $this->assertStringContainsString($copyParam($last), $offer['copy_url']);
        $this->assertStringNotContainsString($copyParam($older), $page->getContent());
        $this->assertArrayNotHasKey($newcomer->id, $offers($page));

        // Tidak ditawarkan saat sudah mode Perpanjang atau saat edit.
        $this->actingAs($user)->get(route('b2b/contracts/create', ['copy_from' => $last->id]))->assertOk()->assertDontSee('id="last-contract"', false);
        $this->actingAs($user)->get(route('b2b/contracts/edit', $older->id))->assertOk()->assertDontSee('id="last-contract"', false);
    }

    public function test_variant_selector_lists_every_variant_of_one_outlet(): void
    {
        $outlet = $this->outlet();
        $variant = $this->variant($outlet, 25000, 10, $this->category('Minuman'), 'Teh Tarik');
        $this->variant($this->outlet('B'), 30000, 10, null, 'Outlet Lain');

        $this->actingAs($this->userFor())->getJson(route('b2b/selectors/variants', ['outlet_id' => $outlet->id]))->assertForbidden();

        $user = $this->userFor(null, ['create b2b/contracts']);
        $this->actingAs($user)->getJson(route('b2b/selectors/variants'))->assertUnprocessable()->assertJsonValidationErrors('outlet_id');
        $data = $this->actingAs($user)->getJson(route('b2b/selectors/variants', ['outlet_id' => $outlet->id]))->assertOk()->json('data');

        $this->assertSame([$variant->id], array_column($data, 'id'));
        $this->assertSame('Teh Tarik', $data[0]['product']);
        $this->assertSame(25000, $data[0]['price']);
        $this->assertStringStartsWith('Minuman', $data[0]['category']);
    }

    public function test_running_contract_cannot_be_deleted_via_http(): void
    {
        $user = $this->userFor(null, ['delete b2b/contracts']);
        $running = $this->contract($this->customer());
        $ended = $this->contract($this->customer(), [], ['start_date' => today()->subMonths(3), 'end_date' => today()->subMonths(2)]);

        $this->actingAs($user)->deleteJson(route('b2b/contracts/destroy', $running->id))
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->actingAs($user)->deleteJson(route('b2b/contracts/destroy', $ended->id))->assertOk();
        $this->assertSoftDeleted('b2b_contracts', ['id' => $ended->id]);
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
        $this->contract($customer, [
            ['variant_id' => $variant->id, 'price' => 17500],
            ['variant_id' => $variant->id, 'min_qty' => 10, 'price' => 16000],
        ]);

        $this->actingAs($user)->getJson(route('b2b/cashier/products', ['outlet_id' => $outlet->id]))
            ->assertOk()->assertJsonFragment(['id' => $variant->id, 'price' => 20000, 'stock' => 50]);
        $this->actingAs($user)->getJson(route('b2b/cashier/customer', $customer->id))
            ->assertOk()->assertJsonPath('data.contract.items_count', 1);

        $payload = ['outlet_id' => $outlet->id, 'b2b_customer_id' => $customer->id, 'items' => [['variant_id' => $variant->id, 'qty' => 4]]];
        $this->actingAs($user)->postJson(route('b2b/cashier/quote'), $payload)
            ->assertOk()
            ->assertJsonPath('data.items.0.final_price', 17500)
            ->assertJsonPath('data.items.0.base_price', 20000)
            ->assertJsonPath('data.items.0.contract_price', true)
            ->assertJsonPath('data.items.0.min_qty', 1)
            ->assertJsonPath('data.grand_total', 70000);

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

    public function test_cashier_immediate_payment_flow(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet, ['read b2b/cashier', 'create b2b/cashier']);
        $variant = $this->variant($outlet, 20000, 50);
        [$edc, $methods] = $this->paymentCategory('EDC', ['QRIS']);
        $payload = ['outlet_id' => $outlet->id, 'b2b_customer_id' => $this->customer()->id, 'delivery_method' => 'pickup',
            'items' => [['variant_id' => $variant->id, 'qty' => 2]], 'payment_type' => Order::PAYMENT_IMMEDIATE];

        $this->actingAs($user)->get(route('b2b/cashier'))->assertOk()->assertSee($edc->name)->assertSee('Bayar langsung');

        // Bayar langsung wajib memilih metode, tapi tidak butuh jatuh tempo.
        $this->actingAs($user)->postJson(route('b2b/cashier/store'), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('payment')->assertJsonMissingValidationErrors('due_date');
        $this->actingAs($user)->postJson(route('b2b/cashier/store'), $payload + ['payment' => ['category_payment_id' => $edc->id]])
            ->assertUnprocessable()->assertJsonValidationErrors('payment.payment_id');
        $this->actingAs($user)->postJson(route('b2b/cashier/store'), array_merge($payload, ['payment_type' => 'barter', 'due_date' => today()->addDays(7)->toDateString()]))
            ->assertUnprocessable()->assertJsonValidationErrors('payment_type');

        $response = $this->actingAs($user)->postJson(route('b2b/cashier/store'), $payload + [
            'due_date' => today()->addDays(30)->toDateString(), // diabaikan
            'payment' => ['category_payment_id' => $edc->id, 'payment_id' => $methods['QRIS']->id, 'reference' => 'APR-1'],
        ])->assertOk()->assertJsonPath('data.payment_type', Order::PAYMENT_IMMEDIATE);

        $this->assertStringContainsString('lunas', $response->json('message'));
        $this->assertMatchesRegularExpression('#^PAY/B2B/#', $response->json('data.payment_number'));
        $order = Order::findOrFail($response->json('data.id'));
        $this->assertSame(today()->toDateString(), $order->due_date->toDateString());
        $this->assertSame('paid', $order->invoice->status);
        $this->assertSame(40000, $order->invoice->paid_amount);
        $this->assertSame('APR-1', $order->invoice->allocations->first()->payment->reference);
    }

    public function test_paid_order_shows_payment_and_cancels_with_refund(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet, ['read b2b/orders', 'cancel-completed b2b/orders', 'read b2b/invoices']);
        $variant = $this->variant($outlet, 15000, 20);
        [$edc, $edcMethods] = $this->paymentCategory('EDC', ['QRIS']);
        [$cash] = $this->paymentCategory('Cash');
        $order = app(\App\Services\B2b\OrderService::class)->place($user, [
            'outlet_id' => $outlet->id, 'b2b_customer_id' => $this->customer()->id, 'delivery_method' => 'pickup',
            'items' => [['variant_id' => $variant->id, 'qty' => 2]], 'payment_type' => Order::PAYMENT_IMMEDIATE,
            'payment' => ['category_payment_id' => $edc->id, 'payment_id' => $edcMethods['QRIS']->id, 'reference' => 'APR-77'],
        ]);
        $payment = $order->invoice->allocations->first()->payment;

        $this->actingAs($user)->get(route('b2b/orders/show', $order->id))->assertOk()
            ->assertSee('Bayar langsung')->assertSee($payment->payment_number)->assertSee($payment->method_name)
            ->assertSee('Ref. APR-77')->assertSee('Batalkan Pesanan yang Sudah Dibayar')->assertSee('Pembayaran diterima');
        $this->actingAs($user)->get(route('b2b/invoices/print', $order->invoice->id))->assertOk()
            ->assertSee('PAID')->assertSee($payment->payment_number)->assertDontSee('Payment must be made within');

        // Tanpa data refund ditolak; metode refund wajib.
        $this->actingAs($user)->postJson(route('b2b/orders/cancel', $order->id), ['reason' => 'Acara batal'])
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->actingAs($user)->postJson(route('b2b/orders/cancel', $order->id), ['reason' => 'Acara batal', 'refund' => ['reference' => 'x']])
            ->assertUnprocessable()->assertJsonValidationErrors('refund.category_payment_id');

        $this->actingAs($user)->postJson(route('b2b/orders/cancel', $order->id), [
            'reason' => 'Acara batal', 'refund' => ['category_payment_id' => $cash->id, 'payment_id' => null, 'reference' => null],
        ])->assertOk()->assertJsonFragment(['message' => 'Pesanan dibatalkan. Pengembalian dana Rp 30.000 tercatat, stok dikembalikan.']);

        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame(20, (int) $variant->fresh()->stok);
        $this->actingAs($user)->get(route('b2b/orders/show', $order->id))->assertOk()
            ->assertSee('Pengembalian dana')->assertSee('Alasan: Acara batal')->assertSee('Pembayaran dibatalkan, dana dikembalikan')
            ->assertDontSee('Batalkan Pesanan yang Sudah Dibayar');
    }

    public function test_credit_invoice_payment_can_be_recorded_with_proof_and_reversed(): void
    {
        Storage::fake('local');
        $outlet = $this->outlet();
        $reader = $this->userFor($outlet, ['read b2b/invoices', 'read b2b/orders']);
        $user = $this->userFor($outlet, ['read b2b/invoices', 'read b2b/orders', 'pay b2b/invoices']);
        $variant = $this->variant($outlet, 25000, 20);
        [$transfer, $transferMethods] = $this->paymentCategory('Transfer', ['BCA']);
        $order = app(\App\Services\B2b\OrderService::class)->place($user, [
            'outlet_id' => $outlet->id, 'b2b_customer_id' => $this->customer()->id, 'delivery_method' => 'pickup',
            'due_date' => today()->addDays(14)->toDateString(), 'items' => [['variant_id' => $variant->id, 'qty' => 4]],
        ]);
        $invoice = $order->invoice;
        $payload = ['amount' => 40000, 'paid_date' => today()->toDateString(), 'category_payment_id' => $transfer->id,
            'payment_id' => $transferMethods['BCA']->id, 'reference' => 'TRF-1', 'notes' => 'Termin 1'];

        // Daftar invoice menampilkan tombol pelunasan hanya untuk yang berhak.
        $this->actingAs($user)->getJson(route('b2b/invoices', ['draw' => 1, 'start' => 0, 'length' => 10, 'status' => 'open']), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertSee('data-pay-invoice', false);
        $this->app->forgetInstance('datatables.request');
        $this->actingAs($reader)->getJson(route('b2b/invoices', ['draw' => 1, 'start' => 0, 'length' => 10, 'status' => 'open']), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertDontSee('data-pay-invoice', false);
        $this->actingAs($user)->get(route('b2b/orders/show', $order->id))->assertOk()->assertSee('Catat pelunasan');

        $this->actingAs($reader)->postJson(route('b2b/invoices/pay', $invoice->id), $payload)->assertForbidden();
        $this->actingAs($user)->postJson(route('b2b/invoices/pay', $invoice->id), array_merge($payload, ['amount' => 100001]))
            ->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->actingAs($user)->postJson(route('b2b/invoices/pay', $invoice->id), array_merge($payload, ['payment_id' => null]))
            ->assertUnprocessable()->assertJsonValidationErrors('payment_id');

        $this->actingAs($user)->post(route('b2b/invoices/pay', $invoice->id), $payload + [
            'attachment' => UploadedFile::fake()->create('bukti.pdf', 50, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.status', 'partial')->assertJsonPath('data.outstanding', 60000);

        $payment = \App\Models\B2b\Payment::query()->where('b2b_customer_id', $order->b2b_customer_id)->sole();
        $this->assertNull($payment->outlet_id);
        $this->assertSame('bukti.pdf', $payment->attachment_name);
        Storage::disk('local')->assertExists($payment->attachment_path);
        $this->actingAs($reader)->get(route('b2b/invoices/payments/attachment', $payment->id))->assertOk()->assertDownload('bukti.pdf');
        $this->actingAs($user)->get(route('b2b/orders/show', $order->id))->assertOk()
            ->assertSee($payment->payment_number)->assertSee('Diterima di rekening pusat')->assertSee('Termin 1')->assertSee('Bukti');

        // Batalkan pembayaran salah catat: wajib alasan, sisa tagihan kembali penuh.
        $this->actingAs($user)->postJson(route('b2b/invoices/payments/reverse', $payment->id), [])
            ->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->actingAs($user)->postJson(route('b2b/invoices/payments/reverse', $payment->id), ['reason' => 'Salah nominal'])->assertOk();
        $this->assertSame('unpaid', $invoice->fresh()->status);
        $this->assertSame(0, $invoice->fresh()->paid_amount);
        $this->actingAs($user)->get(route('b2b/orders/show', $order->id))->assertOk()
            ->assertSee('Pembayaran dibatalkan (salah catat)')->assertSee('Alasan: Salah nominal');

        // Lunasi penuh.
        $this->actingAs($user)->postJson(route('b2b/invoices/pay', $invoice->id), array_merge($payload, ['amount' => 100000]))
            ->assertOk()->assertJsonPath('data.status', 'paid');
        $this->actingAs($user)->postJson(route('b2b/invoices/pay', $invoice->id), array_merge($payload, ['amount' => 1]))
            ->assertUnprocessable()->assertJsonValidationErrors('amount');
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
        $this->seedWilayah();
        $customer = $this->customer(['address' => 'Jl. Kantor', 'province_code' => '32', 'regency_code' => '32.01']);
        // Pesanan diantar kurir masih dikonfirmasi; diambil sendiri langsung selesai.
        $place = fn (string $delivery) => app(\App\Services\B2b\OrderService::class)->place($user, [
            'outlet_id' => $outlet->id, 'b2b_customer_id' => $customer->id, 'delivery_method' => $delivery, 'due_date' => today()->addDays(7)->toDateString(),
            'items' => [['variant_id' => $variant->id, 'qty' => 3]],
        ]);
        $toCancel = $place('internal');
        $toComplete = $place('internal');

        $this->actingAs($user)->postJson(route('b2b/orders/cancel', $toCancel->id), [])->assertUnprocessable();
        $this->actingAs($user)->postJson(route('b2b/orders/cancel', $toCancel->id), ['reason' => 'Salah customer'])->assertOk();
        $this->actingAs($user)->postJson(route('b2b/orders/complete', $toComplete->id))->assertOk();

        $this->assertSame(Order::STATUS_CANCELLED, $toCancel->fresh()->status);
        $this->assertSame(Order::STATUS_COMPLETED, $toComplete->fresh()->status);
        $this->assertSame(7, (int) $variant->fresh()->stok);
    }

    public function test_cancelling_completed_order_needs_separate_permission(): void
    {
        $outlet = $this->outlet();
        $variant = $this->variant($outlet, 10000, 10);
        $cashier = $this->userFor($outlet, ['read b2b/orders', 'cancel b2b/orders']);
        $supervisor = $this->userFor($outlet, ['read b2b/orders', 'cancel-completed b2b/orders']);
        // Diambil sendiri = langsung selesai.
        $order = app(\App\Services\B2b\OrderService::class)->place($cashier, [
            'outlet_id' => $outlet->id, 'b2b_customer_id' => $this->customer()->id, 'delivery_method' => 'pickup', 'due_date' => today()->addDays(7)->toDateString(),
            'items' => [['variant_id' => $variant->id, 'qty' => 3]],
        ]);
        $this->assertSame(Order::STATUS_COMPLETED, $order->status);

        $this->actingAs($cashier)->get(route('b2b/orders/show', $order->id))->assertOk()->assertDontSee('Alasan pembatalan');
        $this->actingAs($cashier)->postJson(route('b2b/orders/cancel', $order->id), ['reason' => 'Salah qty'])->assertForbidden();
        $this->assertSame(Order::STATUS_COMPLETED, $order->fresh()->status);

        $this->actingAs($supervisor)->get(route('b2b/orders/show', $order->id))->assertOk()->assertSee('Alasan pembatalan');
        $this->actingAs($supervisor)->postJson(route('b2b/orders/cancel', $order->id), ['reason' => 'Salah qty'])->assertOk();
        $this->assertSame(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertSame(10, (int) $variant->fresh()->stok);
    }

    public function test_detail_pages_render(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet, self::ALL);
        $customer = $this->customer(['address' => 'Jl. Kantor A']);
        $variant = $this->variant($outlet, 40000, 100, null, 'Kopi Kontrak');
        $contract = $this->contract($customer, [
            ['variant_id' => $variant->id, 'price' => 38000],
            ['variant_id' => $variant->id, 'min_qty' => 10, 'price' => 36000],
        ]);
        $terminated = $this->contract($customer, [['variant_id' => $variant->id, 'price' => 39000]], [
            'status' => Contract::STATUS_TERMINATED, 'start_date' => today()->addMonths(2), 'end_date' => today()->addMonths(3),
        ]);

        $this->actingAs($user)->get(route('b2b/customers/show', $customer->id))->assertOk()->assertSee($contract->contract_number);
        $this->actingAs($user)->get(route('b2b/customers/edit', $customer->id))->assertOk()->assertSee('Jl. Kantor A');
        $this->actingAs($user)->get(route('b2b/contracts/show', $contract->id))->assertOk()
            ->assertSee('Kopi Kontrak')->assertSee('Rp 38.000')->assertSee('Rp 36.000')->assertSee('sedang berjalan');
        // Harga yang sudah ada dikirim sebagai data awal tabel pilih produk (dirender JavaScript).
        $this->actingAs($user)->get(route('b2b/contracts/edit', $contract->id))->assertOk()
            ->assertSee('Kopi Kontrak')->assertSee('"min_qty":10', false)->assertSee('"price":36000', false);
        $this->actingAs($user)->get(route('b2b/contracts/edit', $terminated->id))->assertForbidden();
        $this->actingAs($user)->get(route('b2b/contracts/create'))->assertOk();
        $this->actingAs($user)->get(route('b2b/contracts/create', ['copy_from' => $contract->id]))->assertOk()
            ->assertSee('Disalin dari')->assertSee('Kopi Kontrak');
    }

    public function test_customer_selector_and_customer_filter_on_orders_and_invoices(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet, ['read b2b/orders', 'read b2b/invoices']);
        $variant = $this->variant($outlet);
        $buyer = $this->customer(['name' => 'PT Filter Satu']);
        $other = $this->customer(['name' => 'PT Filter Dua']);
        $inactive = $this->customer(['name' => 'PT Filter Nonaktif', 'is_active' => false]);
        $place = fn (Customer $customer) => app(\App\Services\B2b\OrderService::class)->place($user, [
            'outlet_id' => $outlet->id, 'b2b_customer_id' => $customer->id, 'delivery_method' => 'pickup', 'due_date' => today()->addDays(7)->toDateString(),
            'items' => [['variant_id' => $variant->id, 'qty' => 1]],
        ]);
        $mine = $place($buyer);
        $theirs = $place($other);

        // Pencarian customer untuk filter: termasuk customer nonaktif, butuh akses Pesanan atau Invoice.
        $this->actingAs($user)->getJson(route('b2b/selectors/customers', ['q' => 'PT Filter']))->assertOk()
            ->assertJsonFragment(['id' => $inactive->id])->assertJsonFragment(['id' => $buyer->id]);
        $this->actingAs($this->userFor($outlet, ['read b2b/customers']))->getJson(route('b2b/selectors/customers'))->assertForbidden();

        $ajax = ['X-Requested-With' => 'XMLHttpRequest'];
        $rows = $this->actingAs($user)->getJson(route('b2b/orders', ['draw' => 1, 'start' => 0, 'length' => 10, 'customer_id' => $buyer->id]), $ajax)
            ->assertOk()->json('data.*.DT_RowId');
        $this->assertEquals([$mine->id], $rows);
        $this->app->forgetInstance('datatables.request');
        $rows = $this->actingAs($user)->getJson(route('b2b/invoices', ['draw' => 1, 'start' => 0, 'length' => 10, 'status' => '', 'customer_id' => $other->id]), $ajax)
            ->assertOk()->json('data.*.DT_RowId');
        $this->assertEquals([$theirs->invoice->id], $rows);
    }

    public function test_credit_invoice_print_follows_template_and_profile_config(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet, ['read b2b/invoices']);
        $variant = $this->variant($outlet, 270000);
        $order = app(\App\Services\B2b\OrderService::class)->place($user, [
            'outlet_id' => $outlet->id, 'b2b_customer_id' => $this->customer(['name' => 'Raksa Koffie'])->id, 'delivery_method' => 'pickup',
            'due_date' => today()->addDays(7)->toDateString(), 'items' => [['variant_id' => $variant->id, 'qty' => 5]],
        ]);
        \App\Models\Config::setValue('b2b.invoice.brand_name', 'Brand Uji', null, 'string');

        $this->actingAs($user)->get(route('b2b/invoices/print', $order->invoice->id))->assertOk()
            ->assertSee('Brand Uji')->assertSee('Invoice #'.$order->invoice->invoice_number)->assertSee('Raksa Koffie')
            ->assertSee('Account No: 5735570401 (PT Budhidharma Adil Manfaat)')
            ->assertSee('Payment must be made within 7 calendar days')
            ->assertSee('Order No:')->assertSee($order->order_number)
            ->assertSee('in the transfer description', false)
            ->assertSee('Rp. 1.350.000')->assertSee('@budiman.roastery')->assertDontSee('PAID');
    }

    public function test_receipt_only_for_paid_invoice_and_follows_template(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet, ['read b2b/orders', 'read b2b/invoices']);
        $variant = $this->variant($outlet, 1250000, 20);
        [$cash, $cashMethods] = $this->paymentCategory('Cash', ['Tunai']);
        $customer = $this->customer(['name' => 'Raksa Koffie', 'contact_name' => 'Khairina Nurhafizah']);
        $paid = app(\App\Services\B2b\OrderService::class)->place($user, [
            'outlet_id' => $outlet->id, 'b2b_customer_id' => $customer->id, 'delivery_method' => 'pickup',
            'items' => [['variant_id' => $variant->id, 'qty' => 2]], 'payment_type' => Order::PAYMENT_IMMEDIATE,
            'payment' => ['category_payment_id' => $cash->id, 'payment_id' => $cashMethods['Tunai']->id],
        ]);
        $unpaid = app(\App\Services\B2b\OrderService::class)->place($user, [
            'outlet_id' => $outlet->id, 'b2b_customer_id' => $customer->id, 'delivery_method' => 'pickup',
            'due_date' => today()->addDays(7)->toDateString(), 'items' => [['variant_id' => $variant->id, 'qty' => 1]],
        ]);
        $receiptNumber = str_replace('/INV/', '/KWI/', $paid->invoice->invoice_number);
        \App\Models\Config::setValue('b2b.invoice.phone', '+62 812-3456-7890', null, 'string');

        $this->actingAs($user)->get(route('b2b/invoices/receipt', $paid->invoice->id))->assertOk()
            ->assertSee('+62 812-3456-7890')->assertSee('@budiman.roastery')->assertSee('Tunai')
            ->assertSee('KWITANSI')->assertSee($receiptNumber)->assertSee('Raksa Koffie')->assertSee('Khairina Nurhafizah')
            ->assertSee('Rp 2.500.000')->assertSee('Dua Juta Lima Ratus Ribu Rupiah')
            ->assertSee('Pembayaran Invoice '.$paid->invoice->invoice_number.' – Pesanan '.$paid->order_number)
            ->assertSee('LUNAS')->assertSee('Budiman Roastery')->assertSee('budimanroastery@gmail.com');
        $this->actingAs($user)->get(route('b2b/invoices/receipt', $unpaid->invoice->id))->assertNotFound();

        // Tombol kwitansi hanya untuk invoice lunas.
        $this->actingAs($user)->get(route('b2b/orders/show', $paid->id))->assertSee(route('b2b/invoices/receipt', $paid->invoice->id));
        $this->actingAs($user)->get(route('b2b/orders/show', $unpaid->id))->assertDontSee(route('b2b/invoices/receipt', $unpaid->invoice->id));

        $this->actingAs($this->userFor($this->outlet(), ['read b2b/invoices']))->get(route('b2b/invoices/receipt', $paid->invoice->id))->assertNotFound();
    }

    public function test_terbilang_rupiah(): void
    {
        $cases = [0 => 'Nol', 11 => 'Sebelas', 15 => 'Lima Belas', 100 => 'Seratus', 1000 => 'Seribu', 1_100 => 'Seribu Seratus',
            21_500 => 'Dua Puluh Satu Ribu Lima Ratus', 2_500_000 => 'Dua Juta Lima Ratus Ribu', 1_000_000 => 'Satu Juta',
            1_350_250_000 => 'Satu Miliar Tiga Ratus Lima Puluh Juta Dua Ratus Lima Puluh Ribu'];
        foreach ($cases as $amount => $words) {
            $this->assertSame($words.' Rupiah', \App\Support\B2b\Terbilang::rupiah($amount));
        }
    }

    public function test_invoice_excel_export_uses_template_and_real_numbers(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet, ['read b2b/orders']);
        $variant = $this->variant($outlet, 270000, 100, null, 'Modern Blend');
        $variant->forceFill(['name' => '1 kg'])->save();
        $order = app(\App\Services\B2b\OrderService::class)->place($user, [
            'outlet_id' => $outlet->id, 'b2b_customer_id' => $this->customer(['name' => 'Calle'])->id, 'delivery_method' => 'pickup',
            'due_date' => today()->addDays(7)->toDateString(), 'items' => [['variant_id' => $variant->id, 'qty' => 2]],
        ]);
        $invoice = $order->invoice;

        $response = $this->actingAs($user)->get(route('b2b/invoices/excel', $invoice->id))->assertOk()
            ->assertDownload(\App\Exports\B2b\InvoiceSheet::filename($invoice));
        $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($response->getFile()->getPathname())->getActiveSheet();

        $this->assertSame('Invoice', $sheet->getCell('E1')->getValue());
        $this->assertSame($invoice->invoice_number, $sheet->getCell('F2')->getValue());
        $this->assertSame('Calle', $sheet->getCell('E9')->getValue());
        $this->assertSame(['Modern Blend', 2, '1 kg', 270000], [
            $sheet->getCell('B11')->getValue(), $sheet->getCell('C11')->getValue(), $sheet->getCell('D11')->getValue(), $sheet->getCell('E11')->getValue(),
        ]);
        // Total dan sisa tagihan berupa angka (rumus), bukan teks.
        $this->assertEquals(540000, $sheet->getCell('F11')->getCalculatedValue());
        $this->assertEquals(540000, $sheet->getCell('F15')->getCalculatedValue());
        $this->assertEquals(540000, $sheet->getCell('F17')->getCalculatedValue());

        $this->actingAs($this->userFor($outlet))->get(route('b2b/invoices/excel', $invoice->id))->assertForbidden();
    }

    public function test_invoice_recap_export_follows_filters_and_links_invoice_sheets(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet, ['read b2b/invoices']);
        $variant = $this->variant($outlet, 100000);
        $buyer = $this->customer(['name' => 'PT Rekap Satu']);
        $other = $this->customer(['name' => 'PT Rekap Dua']);
        $place = fn ($customer, int $qty) => app(\App\Services\B2b\OrderService::class)->place($user, [
            'outlet_id' => $outlet->id, 'b2b_customer_id' => $customer->id, 'delivery_method' => 'pickup',
            'due_date' => today()->addDays(7)->toDateString(), 'items' => [['variant_id' => $variant->id, 'qty' => $qty]],
        ]);
        $first = $place($buyer, 1)->invoice;
        $second = $place($buyer, 3)->invoice;
        $place($other, 2);

        $response = $this->actingAs($user)->get(route('b2b/invoices/export', ['status' => 'open', 'customer_id' => $buyer->id]))->assertOk();
        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($response->getFile()->getPathname());

        $titles = array_map(fn ($sheet) => $sheet->getTitle(), $book->getAllSheets());
        $this->assertSame(['Rekap', \App\Exports\B2b\InvoiceSheetWriter::sheetTitle($first), \App\Exports\B2b\InvoiceSheetWriter::sheetTitle($second)], $titles);

        $recap = $book->getSheetByName('Rekap');
        $this->assertStringContainsString('Customer: PT Rekap Satu', $recap->getCell('A2')->getValue());
        $this->assertSame($first->invoice_number, $recap->getCell('B6')->getValue());
        $this->assertSame("sheet://'".\App\Exports\B2b\InvoiceSheetWriter::sheetTitle($first)."'!A1", $recap->getCell('B6')->getHyperlink()->getUrl());
        $this->assertSame($second->invoice_number, $recap->getCell('B7')->getValue());
        $this->assertSame('Total', $recap->getCell('A8')->getValue());
        $this->assertEquals(400000, $recap->getCell('K8')->getCalculatedValue());
        $this->assertEquals(400000, $recap->getCell('M8')->getCalculatedValue());

        // Tidak ada hasil: ditolak dengan pesan, bukan file kosong.
        $this->actingAs($user)->get(route('b2b/invoices/export', ['status' => 'paid', 'customer_id' => $buyer->id]))->assertStatus(422);
    }

    public function test_customer_statement_lists_unpaid_invoices_in_user_outlets(): void
    {
        $outlet = $this->outlet();
        $otherOutlet = $this->outlet('Lain');
        $user = $this->userFor($outlet, ['read b2b/invoices', 'read b2b/customers', 'pay b2b/invoices']);
        $cashier = $this->userFor($otherOutlet);
        $customer = $this->customer(['name' => 'PT Rekap Tagihan', 'contact_name' => 'Sari']);
        $variant = $this->variant($outlet, 100000);
        [$cash] = $this->paymentCategory('Cash');
        $place = fn (int $qty, string $due, $by = null, $at = null, $item = null) => app(\App\Services\B2b\OrderService::class)->place($by ?? $user, [
            'outlet_id' => ($at ?? $outlet)->id, 'b2b_customer_id' => $customer->id, 'delivery_method' => 'pickup',
            'due_date' => $due, 'items' => [['variant_id' => ($item ?? $variant)->id, 'qty' => $qty]],
        ])->invoice;

        $late = $place(2, today()->addDays(1)->toDateString());
        $late->forceFill(['issue_date' => today()->subDays(20), 'due_date' => today()->subDays(5)])->save();
        $partial = $place(3, today()->addDays(10)->toDateString());
        app(\App\Services\B2b\PaymentService::class)->record($user, $partial, [
            'amount' => 100000, 'paid_date' => today()->toDateString(), 'category_payment_id' => $cash->id,
        ]);
        $paid = $place(1, today()->addDays(3)->toDateString());
        app(\App\Services\B2b\PaymentService::class)->record($user, $paid, [
            'amount' => 100000, 'paid_date' => today()->toDateString(), 'category_payment_id' => $cash->id,
        ]);
        // Invoice di outlet yang tidak bisa diakses user tidak ikut.
        $hidden = $place(5, today()->addDays(3)->toDateString(), $cashier, $otherOutlet, $this->variant($otherOutlet, 100000));

        $this->actingAs($user)->get(route('b2b/invoices/statement', $customer->id))->assertOk()
            ->assertSee('Statement of Account')->assertSee('PT Rekap Tagihan')->assertSee('Attn. Sari')->assertSee('Unpaid Invoices:</strong> 2', false)
            ->assertSeeInOrder([$late->invoice_number, '200.000', '5 days', $partial->invoice_number, '300.000', '100.000', '200.000'])
            ->assertSee('Overdue (1 invoice)')->assertSee('Rp. 500.000') // total 200.000 + 300.000
            ->assertSeeInOrder(['TOTAL BALANCE DUE', 'Rp. 400.000'])
            ->assertDontSee($paid->invoice_number)->assertDontSee($hidden->invoice_number);

        $this->actingAs($user)->get(route('b2b/customers/show', $customer->id))->assertOk()->assertSee(route('b2b/invoices/statement', $customer->id));
        $this->actingAs($this->userFor($outlet, ['read b2b/customers']))->get(route('b2b/customers/show', $customer->id))
            ->assertOk()->assertDontSee(route('b2b/invoices/statement', $customer->id));
        $this->actingAs($this->userFor($outlet, ['read b2b/customers']))->get(route('b2b/invoices/statement', $customer->id))->assertForbidden();

        // PDF: unduhan dengan nama file rekap; tanpa akses Invoice ditolak.
        $pdf = $this->actingAs($user)->get(route('b2b/invoices/statement/pdf', $customer->id))->assertOk()
            ->assertDownload('Statement of Account - PT Rekap Tagihan - '.today()->format('Y-m-d').'.pdf');
        $this->assertSame('application/pdf', $pdf->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->actingAs($this->userFor($outlet, ['read b2b/customers']))->get(route('b2b/invoices/statement/pdf', $customer->id))->assertForbidden();
        $this->actingAs($user)->get(route('b2b/invoices/statement', $customer->id))->assertSee(route('b2b/invoices/statement/pdf', $customer->id));

        // Daftar Invoice punya tombol Rekap Tagihan (muncul lewat JS saat tepat satu customer dipilih).
        $this->actingAs($user)->get(route('b2b/invoices'))->assertOk()->assertSee('id="statement-link"', false);

        // Tanpa invoice belum lunas: tetap bisa dicetak dengan keterangan.
        $this->actingAs($user)->get(route('b2b/invoices/statement', $this->customer()->id))->assertOk()->assertSee('There are no unpaid invoices');
    }

    public function test_invoice_list_and_export_filter_many_customers(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet, ['read b2b/invoices']);
        $variant = $this->variant($outlet, 100000);
        $alpha = $this->customer(['name' => 'PT Multi Alpha']);
        $beta = $this->customer(['name' => 'PT Multi Beta']);
        $gamma = $this->customer(['name' => 'PT Multi Gamma']);
        $place = fn ($customer) => app(\App\Services\B2b\OrderService::class)->place($user, [
            'outlet_id' => $outlet->id, 'b2b_customer_id' => $customer->id, 'delivery_method' => 'pickup',
            'due_date' => today()->addDays(7)->toDateString(), 'items' => [['variant_id' => $variant->id, 'qty' => 1]],
        ])->invoice;
        $forAlpha = $place($alpha);
        $forBeta = $place($beta);
        $place($gamma);

        // Daftar: hanya invoice milik customer yang dipilih; id tidak valid diabaikan.
        $ajax = ['X-Requested-With' => 'XMLHttpRequest'];
        $rows = $this->actingAs($user)->getJson(route('b2b/invoices', [
            'draw' => 1, 'start' => 0, 'length' => 10, 'status' => '', 'customer_ids' => [$alpha->id, $beta->id, 'x', ''],
        ]), $ajax)->assertOk()->json('data.*.DT_RowId');
        $this->assertEqualsCanonicalizing([$forAlpha->id, $forBeta->id], $rows);

        // Export: isi file dan keterangan filter mengikuti semua customer terpilih.
        $response = $this->actingAs($user)->get(route('b2b/invoices/export', ['status' => 'open', 'customer_ids' => [$beta->id, $alpha->id]]))->assertOk();
        $recap = \PhpOffice\PhpSpreadsheet\IOFactory::load($response->getFile()->getPathname())->getSheetByName('Rekap');
        $this->assertStringContainsString('Customer: PT Multi Alpha, PT Multi Beta', $recap->getCell('A2')->getValue());
        $this->assertEqualsCanonicalizing([$forAlpha->invoice_number, $forBeta->invoice_number], [$recap->getCell('B6')->getValue(), $recap->getCell('B7')->getValue()]);
        $this->assertSame('Total', $recap->getCell('A8')->getValue());
    }
}

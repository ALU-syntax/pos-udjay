<?php

namespace Tests\Feature\OrderTable;

use App\Models\OrderTable\PaymentMethod;

class PaymentMethodTest extends OrderTableTestCase
{
    public function test_qris_and_cashier_methods_use_server_controlled_mappings(): void
    {
        config(['order-table.pay_at_cashier_enabled' => true]);
        $outlet = $this->outlet('Payments');
        $user = $this->userFor($outlet, ['create order-table/payment-methods']);

        $qrisResponse = $this->actingAs($user)->postJson(route('order-table/payment-methods/store'), [
            'outlet_id' => $outlet->id,
            'code' => 'qris',
            'label' => ' QRIS ',
            'qris_expiry_minutes' => 15,
            'payment_due_minutes' => null,
            'enabled' => true,
            'sort_order' => 1,
        ])->assertOk();
        $qris = PaymentMethod::findOrFail($qrisResponse->json('data.id'));

        $this->assertSame(2, $qris->payment_id);
        $this->assertSame(2, $qris->category_payment_id);
        $this->assertSame('QRIS', $qris->nama_tipe_pembayaran);
        $this->assertNull($qris->payment_due_minutes);

        $cashResponse = $this->actingAs($user)->postJson(route('order-table/payment-methods/store'), [
            'outlet_id' => $outlet->id,
            'code' => 'pay_at_cashier',
            'label' => 'Cashier',
            'qris_expiry_minutes' => null,
            'payment_due_minutes' => 60,
            'enabled' => true,
            'sort_order' => 2,
        ])->assertOk();
        $cash = PaymentMethod::findOrFail($cashResponse->json('data.id'));

        $this->assertNull($cash->payment_id);
        $this->assertSame(1, $cash->category_payment_id);
        $this->assertSame('Cash', $cash->nama_tipe_pembayaran);
        $this->assertNull($cash->qris_expiry_minutes);
        $this->assertDatabaseHas('ot_audit_logs', ['action' => 'payment-method.created', 'subject_id' => $qris->id]);
        $this->assertDatabaseHas('ot_audit_logs', ['action' => 'payment-method.created', 'subject_id' => $cash->id]);
    }

    public function test_cashier_method_cannot_be_enabled_without_environment_approval(): void
    {
        config(['order-table.pay_at_cashier_enabled' => false]);
        $outlet = $this->outlet('Cashier Disabled');
        $user = $this->userFor($outlet, ['create order-table/payment-methods', 'update order-table/payment-methods']);

        $this->actingAs($user)->postJson(route('order-table/payment-methods/store'), [
            'outlet_id' => $outlet->id,
            'code' => 'pay_at_cashier',
            'label' => 'Bayar di Kasir',
            'qris_expiry_minutes' => null,
            'payment_due_minutes' => 60,
            'enabled' => true,
            'sort_order' => 2,
        ])->assertUnprocessable()->assertJsonValidationErrors('enabled');

        $method = $this->paymentMethod($outlet, ['enabled' => false]);
        $this->actingAs($user)
            ->postJson(route('order-table/payment-methods/toggle', $method->id))
            ->assertUnprocessable()->assertJsonValidationErrors('enabled');

        $this->assertFalse($method->fresh()->enabled);
    }

    public function test_duplicate_method_code_is_rejected_for_an_outlet(): void
    {
        config(['order-table.pay_at_cashier_enabled' => true]);
        $outlet = $this->outlet('Payment Duplicate');
        $user = $this->userFor($outlet, ['create order-table/payment-methods']);
        $this->paymentMethod($outlet);

        $this->actingAs($user)->postJson(route('order-table/payment-methods/store'), [
            'outlet_id' => $outlet->id,
            'code' => 'pay_at_cashier',
            'label' => 'Duplicate',
            'qris_expiry_minutes' => null,
            'payment_due_minutes' => 30,
            'enabled' => true,
            'sort_order' => 2,
        ])->assertUnprocessable()->assertJsonValidationErrors('code');
    }

    public function test_toggle_disables_method_and_is_audited(): void
    {
        $outlet = $this->outlet('Payment Toggle');
        $user = $this->userFor($outlet, ['update order-table/payment-methods']);
        $method = $this->paymentMethod($outlet);

        $this->actingAs($user)
            ->postJson(route('order-table/payment-methods/toggle', $method->id))
            ->assertOk();

        $this->assertFalse($method->fresh()->enabled);
        $this->assertDatabaseHas('ot_audit_logs', [
            'user_id' => $user->id,
            'action' => 'payment-method.disabled',
            'subject_id' => $method->id,
        ]);
    }

    public function test_action_links_use_the_method_id_when_sorted_by_outlet(): void
    {
        $outlet = $this->outlet('Payment Sort By Outlet');
        $user = $this->userFor($outlet, ['read order-table/payment-methods', 'update order-table/payment-methods']);
        $method = $this->paymentMethod($outlet);

        // Bug ini hanya terlihat bila id metode berbeda dari id outlet, dan auto increment bisa kebetulan sama.
        if ($method->id === $outlet->id) {
            $method->delete();
            $method = $this->paymentMethod($outlet);
        }

        $row = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('order-table/payment-methods', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'order' => [['column' => 0, 'dir' => 'asc']],
                'columns' => [
                    ['data' => 'outlet_name', 'name' => 'outlet.name', 'searchable' => 'true', 'orderable' => 'true'],
                    ['data' => 'action', 'name' => 'action', 'searchable' => 'false', 'orderable' => 'false'],
                ],
                'search' => ['value' => '', 'regex' => 'false'],
            ]))
            ->assertOk()
            ->json('data.0');

        $this->assertSame($method->id, (int) $row['DT_RowId']);
        $this->assertStringContainsString(route('order-table/payment-methods/edit', $method->id), $row['action']);
        $this->assertStringContainsString(route('order-table/payment-methods/toggle', $method->id), $row['action']);
    }

    public function test_search_matches_method_label_and_code(): void
    {
        $outlet = $this->outlet('Payment Search');
        $user = $this->userFor($outlet, ['read order-table/payment-methods']);
        $method = $this->paymentMethod($outlet, ['code' => 'qris', 'label' => 'Scan Barcode']);

        foreach (['Scan Barcode', 'qris'] as $keyword) {
            // Yajra menyimpan request pertama; tanpa ini kata kunci kedua tidak terpakai
            $this->app->forgetInstance('datatables.request');

            $row = $this->actingAs($user)
                ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
                ->get(route('order-table/payment-methods', [
                    'draw' => 1,
                    'start' => 0,
                    'length' => 10,
                    'outlet_id' => $outlet->id,
                    'columns' => [
                        ['data' => 'label', 'name' => 'label', 'searchable' => 'true', 'orderable' => 'true'],
                    ],
                    'search' => ['value' => $keyword, 'regex' => 'false'],
                ]))
                ->assertOk()
                ->json('data.0');

            $this->assertSame($method->id, (int) $row['DT_RowId'], "Kata kunci: {$keyword}");
        }
    }

    public function test_default_order_groups_by_outlet_then_sort_order(): void
    {
        $outlet = $this->outlet('Payment Order');
        $user = $this->userFor($outlet, ['read order-table/payment-methods']);
        $second = $this->paymentMethod($outlet, ['code' => 'pay_at_cashier', 'sort_order' => 2]);
        $first = $this->paymentMethod($outlet, ['code' => 'qris', 'label' => 'QRIS', 'sort_order' => 1]);

        // Indeks kolom di bawah harus sama dengan orders() pada PaymentMethodDataTable::html()
        $columns = (new \App\DataTables\OrderTable\PaymentMethodDataTable)->getColumns();
        $this->assertSame('outlet_name', $columns[0]->data);
        $this->assertSame('sort_order', $columns[5]->data);

        // Membuat DataTable di atas ikut menyimpan request Yajra; tanpa ini respons berikutnya HTML, bukan JSON
        $this->app->forgetInstance('datatables.request');

        $rows = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('order-table/payment-methods', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'outlet_id' => $outlet->id,
                'order' => [['column' => 0, 'dir' => 'asc'], ['column' => 1, 'dir' => 'asc']],
                'columns' => [
                    ['data' => 'outlet_name', 'name' => 'outlet.name', 'searchable' => 'true', 'orderable' => 'true'],
                    ['data' => 'sort_order', 'name' => 'sort_order', 'searchable' => 'false', 'orderable' => 'true'],
                ],
                'search' => ['value' => '', 'regex' => 'false'],
            ]))
            ->assertOk()
            ->json('data');

        $this->assertSame([$first->id, $second->id], array_map(fn ($row) => (int) $row['DT_RowId'], $rows));
    }

    public function test_form_modal_renders_for_create_and_edit(): void
    {
        $outlet = $this->outlet('Payment Form');
        $user = $this->userFor($outlet, ['create order-table/payment-methods', 'update order-table/payment-methods']);
        $method = $this->paymentMethod($outlet);

        $this->actingAs($user)->get(route('order-table/payment-methods/create'))
            ->assertOk()->assertSee('Tambah Metode Pembayaran')->assertSee('ob-modal');

        $this->actingAs($user)->get(route('order-table/payment-methods/edit', $method->id))
            ->assertOk()->assertSee('Edit Metode Pembayaran')->assertSee('ob-modal');
    }

    public function test_cashier_warning_only_shows_when_environment_disallows_it(): void
    {
        $outlet = $this->outlet('Payment Cashier Warning');
        $user = $this->userFor($outlet, ['create order-table/payment-methods']);

        config(['order-table.pay_at_cashier_enabled' => false]);
        $this->actingAs($user)->get(route('order-table/payment-methods/create'))
            ->assertOk()->assertSee('cashier-mapping-warning')->assertSee('belum diizinkan di environment ini');

        config(['order-table.pay_at_cashier_enabled' => true]);
        $this->actingAs($user)->get(route('order-table/payment-methods/create'))
            ->assertOk()->assertDontSee('cashier-mapping-warning')->assertDontSee('belum diizinkan di environment ini');
    }

    public function test_client_cannot_supply_server_mapping_fields(): void
    {
        $outlet = $this->outlet('Payment Mapping');
        $user = $this->userFor($outlet, ['create order-table/payment-methods']);

        $this->actingAs($user)->postJson(route('order-table/payment-methods/store'), [
            'outlet_id' => $outlet->id,
            'code' => 'qris',
            'label' => 'Tampered QRIS',
            'payment_id' => 999,
            'category_payment_id' => 999,
            'nama_tipe_pembayaran' => 'Tampered',
            'qris_expiry_minutes' => 15,
            'payment_due_minutes' => null,
            'enabled' => true,
            'sort_order' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'payment_id',
            'category_payment_id',
            'nama_tipe_pembayaran',
        ]);

        $this->assertDatabaseMissing('ot_payment_methods', ['outlet_id' => $outlet->id]);
    }
}

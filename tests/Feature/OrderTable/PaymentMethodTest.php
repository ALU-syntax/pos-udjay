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

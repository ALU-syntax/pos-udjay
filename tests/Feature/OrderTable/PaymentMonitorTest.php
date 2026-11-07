<?php

namespace Tests\Feature\OrderTable;

use Illuminate\Support\Facades\Route;

class PaymentMonitorTest extends OrderTableTestCase
{
    public function test_payment_monitor_requires_read_permission(): void
    {
        $outlet = $this->outlet('Payment Permission');
        $user = $this->userFor($outlet);

        $this->actingAs($user)->get(route('order-table/payments'))->assertForbidden();
    }

    public function test_payment_monitor_scopes_outlet_and_masks_gateway_reference(): void
    {
        $own = $this->outlet('Payment Own');
        $other = $this->outlet('Payment Other');
        $user = $this->userFor($own, ['read order-table/payments']);
        $ownOrder = $this->order($own, ['order_no' => 'PAY-OWN']);
        $otherOrder = $this->order($other, ['order_no' => 'PAY-OTHER']);
        $ownPayment = $this->orderPayment($ownOrder, ['gateway_ref' => 'GW-SECRET-1234567890']);
        $otherPayment = $this->orderPayment($otherOrder, ['gateway_ref' => 'GW-SECRET-9999999999']);

        $response = $this->actingAs($user)->getJson(route('order-table/payments', [
            'draw' => 1, 'start' => 0, 'length' => 25,
        ]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();

        $ids = array_map('intval', array_column($response->json('data'), 'id'));
        $this->assertContains($ownPayment->id, $ids);
        $this->assertNotContains($otherPayment->id, $ids);
        $row = collect($response->json('data'))->firstWhere('id', $ownPayment->id);
        $this->assertSame('GW-...890', $row['gateway_ref']);
        $this->assertStringNotContainsString('SECRET', json_encode($row, JSON_THROW_ON_ERROR));
    }

    public function test_ajax_rows_do_not_expose_callback_payload_or_qr_string(): void
    {
        $outlet = $this->outlet('Payment Callback');
        $user = $this->userFor($outlet, ['read order-table/payments']);
        $payment = $this->orderPayment($this->order($outlet), [
            'gateway_ref' => 'GW-CALLBACK-123456',
            'qr_string' => '00020101021226QRSECRET',
            'raw_callback' => ['signature' => 'SIGNATURE-SECRET', 'amount' => 22000],
        ]);

        $row = collect($this->actingAs($user)->getJson(route('order-table/payments', [
            'draw' => 1, 'start' => 0, 'length' => 25,
        ]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'))
            ->first(fn ($row) => (int) $row['DT_RowId'] === $payment->id);

        $json = json_encode($row, JSON_THROW_ON_ERROR);
        $this->assertArrayNotHasKey('raw_callback', $row);
        $this->assertArrayNotHasKey('qr_string', $row);
        $this->assertStringNotContainsString('SIGNATURE-SECRET', $json);
        $this->assertStringNotContainsString('QRSECRET', $json);
    }

    public function test_rows_use_indonesian_labels_with_status_detail(): void
    {
        $outlet = $this->outlet('Payment Labels');
        $user = $this->userFor($outlet, ['read order-table/payments']);
        $order = $this->order($outlet, ['order_no' => 'PAY-LABEL', 'payment_mode' => 'pay_at_cashier']);
        $paid = $this->orderPayment($order, [
            'method' => 'cashier',
            'gateway_ref' => null,
            'status' => 'paid',
            'paid_at' => '2026-11-07 14:05:00',
        ]);
        $pending = $this->orderPayment($this->order($outlet), [
            'gateway_ref' => 'GW-PENDING-123456',
            'expires_at' => '2026-11-07 14:20:00',
        ]);

        $rows = collect($this->actingAs($user)->getJson(route('order-table/payments', [
            'draw' => 1, 'start' => 0, 'length' => 25,
        ]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'))
            ->keyBy(fn ($row) => (int) $row['DT_RowId']);

        $this->assertSame('Bayar di Kasir', $rows[$paid->id]['method']);
        $this->assertStringContainsString('Lunas', $rows[$paid->id]['status']);
        $this->assertStringContainsString('Dibayar 07 Nov 2026, 14:05', $rows[$paid->id]['status']);
        $this->assertStringContainsString('PAY-LABEL', $rows[$paid->id]['order_no']);

        $this->assertSame('QRIS', $rows[$pending->id]['method']);
        $this->assertStringContainsString('Menunggu', $rows[$pending->id]['status']);
        $this->assertStringContainsString('Batas 07 Nov 2026, 14:20', $rows[$pending->id]['status']);
    }

    public function test_index_page_renders_filters_with_labels(): void
    {
        $outlet = $this->outlet('Payment Index Page');
        $user = $this->userFor($outlet, ['read order-table/payments']);

        $this->actingAs($user)->get(route('order-table/payments'))
            ->assertOk()
            ->assertSee('ob-page')
            ->assertSee('<option value="cashier">Bayar di Kasir</option>', false)
            ->assertSee('<option value="paid">Lunas</option>', false)
            ->assertDontSee('<option value="gateway">', false)
            ->assertDontSee('<option value="unpaid">', false)
            ->assertSee('window.initDateRangeFilter', false);
    }

    public function test_payment_monitor_has_no_write_routes(): void
    {
        foreach (['store', 'update', 'destroy', 'create', 'edit'] as $action) {
            $this->assertFalse(Route::has('order-table/payments/'.$action));
        }
    }
}

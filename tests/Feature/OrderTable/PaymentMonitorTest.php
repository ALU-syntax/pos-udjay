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

    public function test_payment_monitor_has_no_write_routes(): void
    {
        foreach (['store', 'update', 'destroy', 'create', 'edit'] as $action) {
            $this->assertFalse(Route::has('order-table/payments/'.$action));
        }
    }
}

<?php

namespace Tests\Feature\OrderTable;

use App\Models\OrderTable\Order;
use App\Models\OrderTable\TableSession;
use App\Models\OrderTable\Voucher;
use App\Models\OrderTable\VoucherRedemption;
use Illuminate\Support\Facades\Route;

class VoucherRedemptionTest extends OrderTableTestCase
{
    public function test_redemption_index_requires_read_permission(): void
    {
        $outlet = $this->outlet('Redemption Permission');
        $user = $this->userFor($outlet);

        $this->actingAs($user)->get(route('order-table/voucher-redemptions'))->assertForbidden();
    }

    public function test_ajax_data_only_contains_authorized_order_outlets_and_masks_device_and_ip(): void
    {
        $own = $this->outlet('Redemption Own');
        $other = $this->outlet('Redemption Other');
        $user = $this->userFor($own, ['read order-table/voucher-redemptions']);
        $ownRedemption = $this->redemption($own, 'OWN-ORDER', '12345678-secret-9999', '192.168.10.25');
        $otherRedemption = $this->redemption($other, 'OTHER-ORDER', '87654321-secret-1111', '10.20.30.40');

        $response = $this->actingAs($user)->getJson(route('order-table/voucher-redemptions', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();

        $ids = array_map('intval', array_column($response->json('data'), 'id'));
        $this->assertContains($ownRedemption->id, $ids);
        $this->assertNotContains($otherRedemption->id, $ids);
        $row = collect($response->json('data'))->firstWhere('id', $ownRedemption->id)
            ?? collect($response->json('data'))->firstWhere('id', (string) $ownRedemption->id);
        $this->assertSame('12345678...9999', $row['device_id']);
        $this->assertSame('192...25', $row['ip_address']);
        $this->assertStringNotContainsString('secret', json_encode($row, JSON_THROW_ON_ERROR));
    }

    public function test_redemptions_expose_no_write_routes(): void
    {
        foreach (['store', 'update', 'destroy', 'create', 'edit'] as $action) {
            $this->assertFalse(Route::has('order-table/voucher-redemptions/'.$action));
        }
    }

    private function redemption($outlet, string $orderNo, string $deviceId, string $ip): VoucherRedemption
    {
        $table = $this->diningTable($outlet, ['code' => substr($orderNo, 0, 10), 'name' => $orderNo.' Table']);
        $session = TableSession::create([
            'device_id' => $deviceId,
            'table_id' => $table->id,
            'outlet_id' => $outlet->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);
        $voucher = Voucher::create([
            'outlet_id' => $outlet->id,
            'code' => 'REDEEM-'.$orderNo,
            'type' => 'fixed',
            'value' => 1000,
            'min_spend' => 0,
            'scope' => 'all',
            'status' => true,
        ]);
        $order = Order::create([
            'order_no' => $orderNo,
            'outlet_id' => $outlet->id,
            'session_id' => $session->id,
            'device_id' => $deviceId,
            'table_id' => $table->id,
            'status' => 'placed',
            'payment_mode' => 'qris',
            'payment_status' => 'unpaid',
            'subtotal' => 10000,
            'voucher_discount' => 1000,
            'tax_total' => 0,
            'grand_total' => 9000,
            'voucher_id' => $voucher->id,
            'geofence_flag' => 'unknown',
        ]);

        return VoucherRedemption::forceCreate([
            'voucher_id' => $voucher->id,
            'order_id' => $order->id,
            'session_id' => $session->id,
            'device_id' => $deviceId,
            'discount_amount' => 1000,
            'ip_address' => $ip,
            'user_agent' => 'OrderTable Test Browser',
        ]);
    }
}

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
        $this->assertStringContainsString('12345678...9999', $row['device_id']);
        $this->assertStringContainsString('192...25', $row['ip_address']);
        $this->assertStringNotContainsString('secret', json_encode($row, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('192.168.10.25', json_encode($row, JSON_THROW_ON_ERROR));
    }

    public function test_order_number_filter_keeps_column_ordering(): void
    {
        $outlet = $this->outlet('Redemption Sort');
        $user = $this->userFor($outlet, ['read order-table/voucher-redemptions']);
        $older = $this->redemption($outlet, 'SORT-OLD', '11111111-aaaa-bbbb-cccc-000000000001', '10.0.0.1');
        $newer = $this->redemption($outlet, 'SORT-NEW', '11111111-aaaa-bbbb-cccc-000000000002', '10.0.0.2');
        $this->redemption($outlet, 'OTHER-NO', '11111111-aaaa-bbbb-cccc-000000000003', '10.0.0.3');
        $older->forceFill(['created_at' => now()->subDay()])->save();

        $rows = $this->actingAs($user)->getJson(route('order-table/voucher-redemptions', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'order_no' => 'SORT-',
            'order' => [['column' => 0, 'dir' => 'desc']],
            'columns' => [['data' => 'created_at', 'name' => 'created_at', 'searchable' => 'true', 'orderable' => 'true']],
        ]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data');

        $this->assertSame([$newer->id, $older->id], array_map(fn ($row) => (int) $row['DT_RowId'], $rows));
    }

    public function test_order_filter_is_not_sent_as_the_datatables_order_param(): void
    {
        // data.order adalah parameter pengurutan DataTables; filter dengan nama itu menimpanya
        $script = (new \App\DataTables\OrderTable\VoucherRedemptionDataTable)->html()->generateScripts()->toHtml();

        $this->assertStringNotContainsString('data.order =', $script);
        $this->assertStringContainsString('data.order_no =', $script);
    }

    public function test_user_agent_is_escaped(): void
    {
        $outlet = $this->outlet('Redemption Escape');
        $user = $this->userFor($outlet, ['read order-table/voucher-redemptions']);
        $redemption = $this->redemption($outlet, 'ESC-ORDER', '22222222-aaaa-bbbb-cccc-000000000001', '10.0.0.9');
        $redemption->forceFill(['user_agent' => '<script>alert(1)</script>'])->save();

        $row = collect($this->actingAs($user)->getJson(route('order-table/voucher-redemptions', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'))
            ->first(fn ($row) => (int) $row['DT_RowId'] === $redemption->id);

        $this->assertStringNotContainsString('<script>', $row['ip_address']);
        $this->assertStringContainsString('&lt;script&gt;', $row['ip_address']);
    }

    public function test_index_page_renders(): void
    {
        $outlet = $this->outlet('Redemption Page');
        $user = $this->userFor($outlet, ['read order-table/voucher-redemptions']);

        $this->actingAs($user)->get(route('order-table/voucher-redemptions'))
            ->assertOk()->assertSee('Monitor Penukaran Voucher')->assertSee('ob-page')
            ->assertSee('id="date-range-filter"', false)
            ->assertSee('id="date-from-filter"', false)->assertSee('id="date-to-filter"', false);
    }

    public function test_date_range_filter_is_inclusive_on_both_ends(): void
    {
        $outlet = $this->outlet('Redemption Date Range');
        $user = $this->userFor($outlet, ['read order-table/voucher-redemptions']);
        $before = $this->redemption($outlet, 'DATE-BEFORE', '33333333-aaaa-bbbb-cccc-000000000001', '10.0.1.1');
        $start = $this->redemption($outlet, 'DATE-START', '33333333-aaaa-bbbb-cccc-000000000002', '10.0.1.2');
        $end = $this->redemption($outlet, 'DATE-END', '33333333-aaaa-bbbb-cccc-000000000003', '10.0.1.3');
        $after = $this->redemption($outlet, 'DATE-AFTER', '33333333-aaaa-bbbb-cccc-000000000004', '10.0.1.4');
        $before->forceFill(['created_at' => '2026-10-31 23:59:59'])->save();
        $start->forceFill(['created_at' => '2026-11-01 00:00:00'])->save();
        $end->forceFill(['created_at' => '2026-11-03 23:59:59'])->save();
        $after->forceFill(['created_at' => '2026-11-04 00:00:00'])->save();

        $ids = array_map(fn ($row) => (int) $row['DT_RowId'], $this->actingAs($user)->getJson(route('order-table/voucher-redemptions', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'outlet_id' => $outlet->id,
            'date_from' => '2026-11-01',
            'date_to' => '2026-11-03',
        ]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'));

        sort($ids);
        $this->assertSame([$start->id, $end->id], $ids);
    }

    public function test_redemptions_expose_no_write_routes(): void
    {
        foreach (['store', 'update', 'destroy', 'create', 'edit'] as $action) {
            $this->assertFalse(Route::has('order-table/voucher-redemptions/'.$action));
        }
    }

    public function test_cashier_redemptions_are_listed_for_their_own_outlet_without_order_or_device(): void
    {
        $own = $this->outlet('Redemption Kasir Own');
        $other = $this->outlet('Redemption Kasir Other');
        $user = $this->userFor($own, ['read order-table/voucher-redemptions']);
        $pos = fn ($outlet, string $reference) => VoucherRedemption::forceCreate([
            'voucher_id' => Voucher::create(['outlet_id' => $outlet->id, 'code' => 'KASIR-'.$reference, 'type' => 'fixed',
                'value' => 1000, 'min_spend' => 0, 'scope' => 'all', 'status' => true])->id,
            'source' => 'pos', 'outlet_id' => $outlet->id, 'pos_reference' => $reference,
            'discount_amount' => 1000, 'user_agent' => 'kasir:Budi',
        ]);
        $ownRow = $pos($own, 'REF-OWN-0001');
        $otherRow = $pos($other, 'REF-OTHER-0001');
        $fetch = fn (array $filter = []) => collect($this->actingAs($user)->getJson(route('order-table/voucher-redemptions', [
            'draw' => 1, 'start' => 0, 'length' => 25,
        ] + $filter), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'));

        $rows = $fetch();
        $ids = $rows->map(fn ($row) => (int) $row['DT_RowId'])->all();
        $this->assertContains($ownRow->id, $ids);
        $this->assertNotContains($otherRow->id, $ids);
        $row = $rows->first(fn ($row) => (int) $row['DT_RowId'] === $ownRow->id);
        $this->assertStringContainsString('Transaksi kasir', $row['order_table']);
        $this->assertStringContainsString('REF-OWN-0001', $row['order_table']);
        $this->assertStringContainsString('Kasir', $row['device_id']);
        $this->assertStringContainsString('Redemption Kasir Own', $row['outlet_name']);
        $this->assertStringContainsString('kasir:Budi', $row['ip_address']);

        // Filter nomor order ikut mencari referensi transaksi kasir; filter outlet tetap berlaku.
        $this->assertSame([$ownRow->id], $fetch(['order_no' => 'REF-OWN'])->map(fn ($row) => (int) $row['DT_RowId'])->all());
        $this->assertContains($ownRow->id, $fetch(['outlet_id' => $own->id])->map(fn ($row) => (int) $row['DT_RowId'])->all());
        $this->assertSame([], $fetch(['order_no' => 'REF-OTHER'])->all());
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

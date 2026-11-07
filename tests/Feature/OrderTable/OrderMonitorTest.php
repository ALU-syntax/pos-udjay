<?php

namespace Tests\Feature\OrderTable;

use App\Models\OrderTable\OrderItem;
use Illuminate\Support\Facades\Route;

class OrderMonitorTest extends OrderTableTestCase
{
    public function test_order_monitor_requires_read_permission(): void
    {
        $outlet = $this->outlet('Order Permission');
        $user = $this->userFor($outlet);

        $this->actingAs($user)->get(route('order-table/orders'))->assertForbidden();
    }

    public function test_order_monitor_only_lists_authorized_outlet_orders(): void
    {
        $own = $this->outlet('Order Own');
        $other = $this->outlet('Order Other');
        $user = $this->userFor($own, ['read order-table/orders']);
        $ownOrder = $this->order($own, ['order_no' => 'OWN-ORDER']);
        $otherOrder = $this->order($other, ['order_no' => 'OTHER-ORDER']);

        $response = $this->actingAs($user)->getJson(route('order-table/orders', [
            'draw' => 1, 'start' => 0, 'length' => 25,
        ]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();

        $ids = array_map('intval', array_column($response->json('data'), 'id'));
        $this->assertContains($ownOrder->id, $ids);
        $this->assertNotContains($otherOrder->id, $ids);
    }

    public function test_order_detail_is_read_only_and_scoped_to_outlet(): void
    {
        $own = $this->outlet('Order Detail Own');
        $other = $this->outlet('Order Detail Other');
        $user = $this->userFor($own, ['read order-table/orders']);
        $order = $this->order($own, ['order_no' => 'DETAIL-OWN']);
        OrderItem::forceCreate([
            'order_id' => $order->id,
            'product_id' => 1,
            'product_name' => 'Kopi Susu',
            'unit_price' => 18000,
            'qty' => 2,
            'modifier_total' => 0,
            'line_total' => 36000,
            'exclude_tax' => false,
            'status' => 'pending',
        ]);
        $otherOrder = $this->order($other, ['order_no' => 'DETAIL-OTHER']);

        $this->actingAs($user)->get(route('order-table/orders/show', $order->id))
            ->assertOk()
            ->assertSee('Kopi Susu')
            ->assertSee('DETAIL-OWN');
        $this->actingAs($user)->get(route('order-table/orders/show', $otherOrder->id))
            ->assertNotFound();
    }

    public function test_rows_use_indonesian_labels_and_escape_customer_input(): void
    {
        $outlet = $this->outlet('Order Labels');
        $user = $this->userFor($outlet, ['read order-table/orders']);
        $order = $this->order($outlet, [
            'order_no' => 'LABEL-ORDER',
            'atas_nama' => '<b>Budi</b>',
            'status' => 'preparing',
            'payment_mode' => 'pay_at_cashier',
            'payment_status' => 'paid',
        ]);

        $row = collect($this->actingAs($user)->getJson(route('order-table/orders', [
            'draw' => 1, 'start' => 0, 'length' => 25,
        ]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'))
            ->first(fn ($row) => (int) $row['DT_RowId'] === $order->id);

        $this->assertStringContainsString('Disiapkan', $row['status']);
        $this->assertStringContainsString('Lunas', $row['payment_status']);
        $this->assertStringContainsString('Bayar di Kasir', $row['payment_status']);
        $this->assertStringContainsString('Menunggu', $row['pos_bridge_status']);
        $this->assertStringContainsString('&lt;b&gt;Budi&lt;/b&gt;', $row['order_no']);
        $this->assertStringNotContainsString('<b>Budi</b>', $row['order_no']);
        $this->assertStringNotContainsString('secret', $row['device_id']);
    }

    public function test_table_search_matches_order_number_and_customer_name(): void
    {
        $outlet = $this->outlet('Order Search');
        $user = $this->userFor($outlet, ['read order-table/orders']);
        $order = $this->order($outlet, ['order_no' => 'SEARCH-ORDER', 'atas_nama' => 'Sinta Wulandari']);
        $this->order($outlet, ['order_no' => 'OTHER-SEARCH', 'atas_nama' => 'Rudi']);

        foreach (['SEARCH-ORDER', 'Wulandari'] as $keyword) {
            // Yajra menyimpan request pertama; tanpa ini kata kunci kedua tidak terpakai
            $this->app->forgetInstance('datatables.request');

            $ids = array_map(fn ($row) => (int) $row['DT_RowId'], $this->actingAs($user)->getJson(route('order-table/orders', [
                'draw' => 1, 'start' => 0, 'length' => 25,
                'outlet_id' => $outlet->id,
                'columns' => [['data' => 'order_no', 'name' => 'order_no', 'searchable' => 'true', 'orderable' => 'true']],
                'search' => ['value' => $keyword, 'regex' => 'false'],
            ]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'));

            $this->assertSame([$order->id], $ids, "Kata kunci: {$keyword}");
        }
    }

    public function test_index_page_renders_filters_with_labels(): void
    {
        $outlet = $this->outlet('Order Index Page');
        $user = $this->userFor($outlet, ['read order-table/orders']);

        $this->actingAs($user)->get(route('order-table/orders'))
            ->assertOk()
            ->assertSee('ob-page')
            ->assertSee('<option value="placed">Dipesan</option>', false)
            ->assertSee('<option value="pay_at_cashier">Bayar di Kasir</option>', false)
            ->assertSee('id="date-range-filter"', false)
            ->assertSee('window.initDateRangeFilter', false);
    }

    public function test_order_detail_shows_labels_and_escapes_notes(): void
    {
        $outlet = $this->outlet('Order Detail Labels');
        $user = $this->userFor($outlet, ['read order-table/orders']);
        $order = $this->order($outlet, [
            'order_no' => 'DETAIL-LABEL',
            'status' => 'served',
            'payment_status' => 'paid',
            'geofence_flag' => 'outside',
            'notes' => '<script>alert(1)</script>',
        ]);
        \App\Models\OrderTable\OrderStatusLog::forceCreate([
            'order_id' => $order->id,
            'from_status' => 'preparing',
            'to_status' => 'served',
            'actor' => 'kasir',
        ]);

        $this->actingAs($user)->get(route('order-table/orders/show', $order->id))
            ->assertOk()
            ->assertSee('Disajikan')
            ->assertSee('Lunas')
            ->assertSee('Di luar area')
            ->assertSee('oleh Kasir')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_serve_and_cancel_are_blocked_while_node_integration_is_disabled(): void
    {
        config(['order-table.node.enabled' => false]);
        $outlet = $this->outlet('Order Action');
        $user = $this->userFor($outlet, ['serve order-table/orders', 'cancel order-table/orders']);
        $order = $this->order($outlet);

        $this->actingAs($user)->postJson(route('order-table/orders/serve', $order->id))
            ->assertOk()->assertJsonPath('status', 'warning');
        $this->actingAs($user)->postJson(route('order-table/orders/cancel', $order->id), ['reason' => 'Salah pesan'])
            ->assertOk()->assertJsonPath('status', 'warning');

        $this->assertSame('placed', $order->fresh()->status);
    }

    public function test_order_monitor_has_no_delete_or_status_update_route(): void
    {
        foreach (['destroy', 'update', 'store', 'edit'] as $action) {
            $this->assertFalse(Route::has('order-table/orders/'.$action));
        }
    }
}

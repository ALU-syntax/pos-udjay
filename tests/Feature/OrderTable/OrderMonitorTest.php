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

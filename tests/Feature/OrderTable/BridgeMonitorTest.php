<?php

namespace Tests\Feature\OrderTable;

use Illuminate\Support\Facades\Route;

class BridgeMonitorTest extends OrderTableTestCase
{
    public function test_bridge_monitor_requires_read_permission(): void
    {
        $outlet = $this->outlet('Bridge Permission');
        $user = $this->userFor($outlet);

        $this->actingAs($user)->get(route('order-table/bridge'))->assertForbidden();
    }

    public function test_bridge_monitor_only_lists_paid_orders_from_authorized_outlets(): void
    {
        $own = $this->outlet('Bridge Own');
        $other = $this->outlet('Bridge Other');
        $user = $this->userFor($own, ['read order-table/bridge']);
        $ownPaid = $this->order($own, ['order_no' => 'BRIDGE-OWN', 'payment_status' => 'paid', 'pos_bridge_status' => 'failed']);
        $this->order($own, ['order_no' => 'BRIDGE-UNPAID', 'payment_status' => 'unpaid']);
        $otherPaid = $this->order($other, ['order_no' => 'BRIDGE-OTHER', 'payment_status' => 'paid']);

        $response = $this->actingAs($user)->getJson(route('order-table/bridge', [
            'draw' => 1, 'start' => 0, 'length' => 25,
        ]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();

        $ids = array_map('intval', array_column($response->json('data'), 'id'));
        $this->assertContains($ownPaid->id, $ids);
        $this->assertNotContains($otherPaid->id, $ids);
        $this->assertCount(1, $ids);
    }

    public function test_rebridge_is_blocked_while_node_disabled_and_writes_audit_only_when_sent(): void
    {
        config(['order-table.node.enabled' => false]);
        $outlet = $this->outlet('Bridge Retry');
        $user = $this->userFor($outlet, ['retry order-table/bridge']);
        $order = $this->order($outlet, ['payment_status' => 'paid', 'pos_bridge_status' => 'failed']);

        $this->actingAs($user)->postJson(route('order-table/bridge/rebridge', $order->id))
            ->assertOk()->assertJsonPath('status', 'warning');

        $this->assertSame('failed', $order->fresh()->pos_bridge_status);
        $this->assertDatabaseMissing('ot_audit_logs', ['action' => 'bridge.retry-requested', 'subject_id' => $order->id]);
    }

    public function test_bridge_monitor_has_no_direct_write_routes(): void
    {
        foreach (['store', 'update', 'destroy', 'create', 'edit'] as $action) {
            $this->assertFalse(Route::has('order-table/bridge/'.$action));
        }
    }
}

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

    public function test_rows_use_labels_and_rebridge_is_only_offered_for_failed_bridges(): void
    {
        config([
            'order-table.node.enabled' => true,
            'order-table.node.base_url' => 'http://node.test',
            'order-table.node.internal_api_key' => 'test-key',
        ]);
        $outlet = $this->outlet('Bridge Labels');
        $user = $this->userFor($outlet, ['read order-table/bridge', 'retry order-table/bridge']);
        $failed = $this->order($outlet, ['order_no' => 'BRIDGE-FAILED', 'atas_nama' => 'Dewi', 'payment_status' => 'paid', 'pos_bridge_status' => 'failed']);
        $linked = $this->order($outlet, ['payment_status' => 'paid', 'pos_bridge_status' => 'linked', 'pos_transaction_id' => 987]);
        $pending = $this->order($outlet, ['payment_status' => 'paid', 'pos_bridge_status' => 'pending']);

        $rows = collect($this->actingAs($user)->getJson(route('order-table/bridge', [
            'draw' => 1, 'start' => 0, 'length' => 25,
        ]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'))
            ->keyBy(fn ($row) => (int) $row['DT_RowId']);

        $this->assertStringContainsString('Gagal', $rows[$failed->id]['pos_bridge_status']);
        $this->assertStringContainsString('Belum tercatat di POS', $rows[$failed->id]['pos_bridge_status']);
        $this->assertStringContainsString('Dewi', $rows[$failed->id]['order_no']);
        $this->assertStringContainsString(route('order-table/bridge/rebridge', $failed->id), $rows[$failed->id]['action']);

        $this->assertStringContainsString('Tersambung', $rows[$linked->id]['pos_bridge_status']);
        $this->assertStringContainsString('POS Trx #987', $rows[$linked->id]['pos_bridge_status']);
        $this->assertStringNotContainsString('rebridge', $rows[$linked->id]['action']);

        $this->assertStringContainsString('Menunggu diproses', $rows[$pending->id]['pos_bridge_status']);
        $this->assertStringNotContainsString('rebridge', $rows[$pending->id]['action']);
    }

    public function test_rebridge_button_is_disabled_when_node_client_is_not_configured(): void
    {
        // enabled=true tetapi tanpa URL/API key: client menolak request, jadi tombol harus nonaktif
        config(['order-table.node.enabled' => true, 'order-table.node.base_url' => null, 'order-table.node.internal_api_key' => null]);
        $outlet = $this->outlet('Bridge Disabled Button');
        $user = $this->userFor($outlet, ['read order-table/bridge', 'retry order-table/bridge']);
        $failed = $this->order($outlet, ['payment_status' => 'paid', 'pos_bridge_status' => 'failed']);

        $row = collect($this->actingAs($user)->getJson(route('order-table/bridge', [
            'draw' => 1, 'start' => 0, 'length' => 25,
        ]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'))
            ->first(fn ($row) => (int) $row['DT_RowId'] === $failed->id);

        $this->assertStringContainsString('disabled', $row['action']);
        $this->assertStringNotContainsString('action-rebridge', $row['action']);
    }

    public function test_index_page_renders_filters_with_labels(): void
    {
        $outlet = $this->outlet('Bridge Index Page');
        $user = $this->userFor($outlet, ['read order-table/bridge']);

        $this->actingAs($user)->get(route('order-table/bridge'))
            ->assertOk()
            ->assertSee('ob-page')
            ->assertSee('<option value="linked">Tersambung</option>', false)
            ->assertSee('Tanggal Order')
            ->assertSee('window.initDateRangeFilter', false);
    }

    public function test_bridge_monitor_has_no_direct_write_routes(): void
    {
        foreach (['store', 'update', 'destroy', 'create', 'edit'] as $action) {
            $this->assertFalse(Route::has('order-table/bridge/'.$action));
        }
    }
}

<?php

namespace Tests\Feature\OrderTable;

use Illuminate\Support\Facades\Route;

class SessionMonitorTest extends OrderTableTestCase
{
    public function test_session_monitor_requires_read_permission(): void
    {
        $outlet = $this->outlet('Session Permission');
        $user = $this->userFor($outlet);

        $this->actingAs($user)->get(route('order-table/sessions'))->assertForbidden();
    }

    public function test_session_monitor_masks_device_and_scopes_outlet(): void
    {
        $own = $this->outlet('Session Own');
        $other = $this->outlet('Session Other');
        $user = $this->userFor($own, ['read order-table/sessions']);
        $ownSession = $this->tableSession($own, ['device_id' => '12345678-secret-9999']);
        $otherSession = $this->tableSession($other, ['device_id' => '87654321-secret-1111']);

        $response = $this->actingAs($user)->getJson(route('order-table/sessions', [
            'draw' => 1, 'start' => 0, 'length' => 25,
        ]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();

        $ids = array_map('intval', array_column($response->json('data'), 'id'));
        $this->assertContains($ownSession->id, $ids);
        $this->assertNotContains($otherSession->id, $ids);
        $row = collect($response->json('data'))->firstWhere('id', $ownSession->id);
        $this->assertSame('12345678...9999', $row['device_id']);
        $this->assertStringNotContainsString('secret', json_encode($row, JSON_THROW_ON_ERROR));
    }

    public function test_rows_use_indonesian_labels_and_only_open_sessions_can_be_closed(): void
    {
        $outlet = $this->outlet('Session Labels');
        $user = $this->userFor($outlet, ['read order-table/sessions', 'close order-table/sessions']);
        $open = $this->tableSession($outlet, ['opened_at' => now()->subMinutes(135)]);
        $closed = $this->tableSession($outlet, [
            'status' => 'closed',
            'opened_at' => now()->subHours(3),
            'closed_at' => now()->subHours(2),
            'close_reason' => 'eod',
            'closed_by' => 'system',
        ]);

        $rows = collect($this->actingAs($user)->getJson(route('order-table/sessions', [
            'draw' => 1, 'start' => 0, 'length' => 25,
        ]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'))
            ->keyBy(fn ($row) => (int) $row['DT_RowId']);

        $this->assertStringContainsString('Aktif', $rows[$open->id]['status']);
        $this->assertStringContainsString('Berjalan 2 jam 15 mnt', $rows[$open->id]['status']);
        $this->assertStringContainsString(route('order-table/sessions/close', $open->id), $rows[$open->id]['action']);

        $this->assertStringContainsString('Ditutup', $rows[$closed->id]['status']);
        $this->assertStringContainsString('Durasi 1 jam 0 mnt', $rows[$closed->id]['status']);
        $this->assertStringContainsString('Tutup hari · oleh Sistem', $rows[$closed->id]['closed_at']);
        $this->assertStringNotContainsString('sessions/close', $rows[$closed->id]['action']);
    }

    public function test_index_page_renders_filters_with_labels(): void
    {
        $outlet = $this->outlet('Session Index Page');
        $user = $this->userFor($outlet, ['read order-table/sessions']);

        $this->actingAs($user)->get(route('order-table/sessions'))
            ->assertOk()
            ->assertSee('ob-page')
            ->assertSee('<option value="open">Aktif</option>', false)
            ->assertSee('Tanggal Dibuka')
            ->assertSee('window.initDateRangeFilter', false);
    }

    public function test_session_close_is_blocked_while_node_disabled_and_has_no_hard_delete(): void
    {
        config(['order-table.node.enabled' => false]);
        $outlet = $this->outlet('Session Close');
        $user = $this->userFor($outlet, ['close order-table/sessions']);
        $session = $this->tableSession($outlet);

        $this->actingAs($user)->postJson(route('order-table/sessions/close', $session->id))
            ->assertOk()->assertJsonPath('status', 'warning');

        $this->assertSame('open', $session->fresh()->status);
        $this->assertFalse(Route::has('order-table/sessions/destroy'));
        $this->assertFalse(Route::has('order-table/sessions/delete'));
    }
}

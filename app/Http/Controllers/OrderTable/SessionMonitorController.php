<?php

namespace App\Http\Controllers\OrderTable;

use App\DataTables\OrderTable\SessionMonitorDataTable;
use App\Http\Controllers\Controller;
use App\Models\OrderTable\TableSession;
use App\Models\Outlets;
use App\Services\OrderTable\AuditLogger;
use App\Services\OrderTable\OrderTableNodeClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SessionMonitorController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly OrderTableNodeClient $nodeClient,
    ) {
        $this->abilities = [];
    }

    public function index(SessionMonitorDataTable $dataTable)
    {
        $this->authorize('read order-table/sessions');

        return $dataTable->render('layouts.order-table.sessions.index', [
            'outlets' => $this->outlets(),
            'nodeEnabled' => $this->nodeClient->enabled(),
        ]);
    }

    public function close(Request $request, int $session)
    {
        $this->authorize('close order-table/sessions');
        $session = $this->session($session);
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        try {
            $this->nodeClient->closeSession($session->id, $validated['reason'] ?? null);
        } catch (RuntimeException $exception) {
            return responseWarning($exception->getMessage());
        }

        DB::transaction(function () use ($session, $validated) {
            $this->auditLogger->log('session.close-requested', $session, ['status' => $session->status], ['status' => 'closed'], [
                'reason' => $validated['reason'] ?? null,
            ]);
        });

        return responseSuccess(true, 'Permintaan penutupan sesi dikirim ke layanan order.');
    }

    private function session(int $id): TableSession
    {
        return TableSession::query()
            ->whereKey($id)
            ->whereIn('outlet_id', request()->user()->outletIds())
            ->firstOrFail();
    }

    private function outlets()
    {
        return Outlets::query()
            ->whereIn('id', request()->user()->outletIds())
            ->orderBy('name')
            ->get();
    }
}

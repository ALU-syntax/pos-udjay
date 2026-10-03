<?php

namespace App\Http\Controllers\OrderTable;

use App\DataTables\OrderTable\BridgeMonitorDataTable;
use App\Http\Controllers\Controller;
use App\Models\OrderTable\Order;
use App\Models\Outlets;
use App\Services\OrderTable\AuditLogger;
use App\Services\OrderTable\OrderTableNodeClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BridgeMonitorController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly OrderTableNodeClient $nodeClient,
    ) {
        $this->abilities = [];
    }

    public function index(BridgeMonitorDataTable $dataTable)
    {
        $this->authorize('read order-table/bridge');

        return $dataTable->render('layouts.order-table.bridge.index', [
            'outlets' => $this->outlets(),
            'nodeEnabled' => $this->nodeClient->enabled(),
        ]);
    }

    public function rebridge(Request $request, int $order)
    {
        $this->authorize('retry order-table/bridge');
        $order = $this->order($order);

        try {
            $this->nodeClient->rebridge($order->id);
        } catch (RuntimeException $exception) {
            return responseWarning($exception->getMessage());
        }

        DB::transaction(function () use ($order) {
            $this->auditLogger->log('bridge.retry-requested', $order, [
                'pos_bridge_status' => $order->pos_bridge_status,
                'pos_transaction_id' => $order->pos_transaction_id,
            ], [
                'pos_bridge_status' => $order->pos_bridge_status,
                'pos_transaction_id' => $order->pos_transaction_id,
            ], [
                'requested_by' => request()->user()?->id,
            ]);
        });

        return responseSuccess(true, 'Permintaan re-bridge dikirim ke layanan order.');
    }

    private function order(int $id): Order
    {
        return Order::query()
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

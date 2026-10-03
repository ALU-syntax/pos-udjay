<?php

namespace App\Http\Controllers\OrderTable;

use App\DataTables\OrderTable\OrderMonitorDataTable;
use App\Http\Controllers\Controller;
use App\Models\OrderTable\Order;
use App\Models\Outlets;
use App\Services\OrderTable\AuditLogger;
use App\Services\OrderTable\OrderTableNodeClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderMonitorController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly OrderTableNodeClient $nodeClient,
    ) {
        $this->abilities = [];
    }

    public function index(OrderMonitorDataTable $dataTable)
    {
        $this->authorize('read order-table/orders');

        return $dataTable->render('layouts.order-table.orders.index', [
            'outlets' => $this->outlets(),
            'nodeEnabled' => $this->nodeClient->enabled(),
        ]);
    }

    public function show(int $order)
    {
        $this->authorize('read order-table/orders');
        $order = $this->order($order);

        return view('layouts.order-table.orders.show', [
            'order' => $order,
            'nodeEnabled' => $this->nodeClient->enabled(),
        ]);
    }

    public function serve(Request $request, int $order)
    {
        $this->authorize('serve order-table/orders');
        $order = $this->order($order);

        try {
            $this->nodeClient->serve($order->id);
        } catch (RuntimeException $exception) {
            return responseWarning($exception->getMessage());
        }

        DB::transaction(function () use ($order) {
            $this->auditLogger->log('order.serve-requested', $order, ['status' => $order->status], ['status' => 'served']);
        });

        return responseSuccess(true, 'Permintaan tandai disajikan dikirim ke layanan order.');
    }

    public function cancel(Request $request, int $order)
    {
        $this->authorize('cancel order-table/orders');
        $order = $this->order($order);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        try {
            $this->nodeClient->cancel($order->id, $validated['reason']);
        } catch (RuntimeException $exception) {
            return responseWarning($exception->getMessage());
        }

        DB::transaction(function () use ($order, $validated) {
            $this->auditLogger->log('order.cancel-requested', $order, ['status' => $order->status], ['status' => 'cancelled'], [
                'reason' => $validated['reason'],
            ]);
        });

        return responseSuccess(true, 'Permintaan pembatalan dikirim ke layanan order.');
    }

    private function order(int $id): Order
    {
        return Order::query()
            ->with(['outlet', 'table', 'items.modifiers', 'payments', 'statusLogs', 'voucher'])
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

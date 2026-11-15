<?php

namespace App\Http\Controllers\B2b;

use App\DataTables\B2b\OrderDataTable;
use App\Http\Controllers\Controller;
use App\Models\B2b\ActivityLog;
use App\Models\B2b\Order;
use App\Models\Outlets;
use App\Services\B2b\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders)
    {
        $this->abilities = [];
    }

    public function index(OrderDataTable $dataTable, Request $request)
    {
        $this->authorize('read b2b/orders');

        return $dataTable->render('layouts.b2b.orders.index', [
            'outlets' => Outlets::query()->whereIn('id', $request->user()->outletIds())->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(int $order)
    {
        $this->authorize('read b2b/orders');
        $order = $this->order($order)->load(['items', 'customer', 'contract', 'outlet', 'cashier', 'canceller', 'invoice.schedules']);

        return view('layouts.b2b.orders.show', [
            'order' => $order,
            'activities' => ActivityLog::query()
                ->with('user:id,name')
                ->where('subject_type', $order->getMorphClass())
                ->where('subject_id', $order->id)
                ->latest('id')
                ->get(),
        ]);
    }

    public function complete(int $order)
    {
        $this->authorize('complete b2b/orders');
        $this->orders->complete(request()->user(), $this->order($order));

        return responseSuccess(true, 'Pesanan ditandai selesai');
    }

    public function cancel(Request $request, int $order)
    {
        $this->authorize('cancel b2b/orders');
        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']], [], ['reason' => 'alasan']);
        $this->orders->cancel($request->user(), $this->order($order), $validated['reason']);

        return responseSuccess(true, 'Pesanan dibatalkan. Stok dikembalikan dan invoice dibatalkan.');
    }

    /** Pesanan hanya bisa diakses user yang punya akses ke outlet pesanannya. */
    private function order(int $id): Order
    {
        return Order::query()
            ->whereKey($id)
            ->whereIn('outlet_id', request()->user()->outletIds())
            ->firstOrFail();
    }
}

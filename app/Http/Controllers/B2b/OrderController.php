<?php

namespace App\Http\Controllers\B2b;

use App\DataTables\B2b\OrderDataTable;
use App\Http\Controllers\Controller;
use App\Models\B2b\ActivityLog;
use App\Models\B2b\Invoice;
use App\Models\B2b\Order;
use App\Models\B2b\Payment;
use App\Models\Outlets;
use App\Services\B2b\OrderService;
use App\Services\B2b\PaymentService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly PaymentService $payments,
    ) {
        $this->abilities = [];
    }

    public function index(OrderDataTable $dataTable, Request $request)
    {
        $this->authorize('read b2b/orders');

        $outletIds = $request->user()->outletIds();
        $orders = fn () => Order::query()->whereIn('outlet_id', $outletIds);
        $month = $orders()->where('status', '!=', Order::STATUS_CANCELLED)
            ->whereBetween('order_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('count(*) as total, coalesce(sum(grand_total), 0) as amount')->first();
        // Tagihan tempo yang belum lunas; status overdue dihitung dari tanggal (lihat Invoice::displayStatus()).
        $open = fn () => Invoice::query()
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_OVERDUE])
            ->whereHas('order', fn ($order) => $order->whereIn('outlet_id', $outletIds));
        $outstanding = 'coalesce(sum(total_to_pay - paid_amount), 0) as amount';
        $unpaid = $open()->selectRaw('count(*) as total, '.$outstanding)->first();
        $overdue = $open()->whereDate('due_date', '<', today()->toDateString())->selectRaw('count(*) as total, '.$outstanding)->first();

        return $dataTable->render('layouts.b2b.orders.index', [
            'outlets' => Outlets::query()->whereIn('id', $outletIds)->orderBy('name')->get(['id', 'name']),
            'stats' => [
                'month_count' => (int) $month->total,
                'month_amount' => (int) $month->amount,
                'confirmed' => $orders()->where('status', Order::STATUS_CONFIRMED)->count(),
                'unpaid_count' => (int) $unpaid->total,
                'unpaid_amount' => (int) $unpaid->amount,
                'overdue_count' => (int) $overdue->total,
                'overdue_amount' => (int) $overdue->amount,
            ],
        ]);
    }

    public function show(int $order)
    {
        $this->authorize('read b2b/orders');
        $order = $this->order($order)->load([
            'items', 'customer', 'contract', 'outlet', 'cashier', 'canceller', 'invoice.schedules',
            'invoice.allocations.payment.receiver:id,name', 'invoice.allocations.payment.reverser:id,name', 'invoice.refunds.refunder:id,name',
        ]);
        $payments = $order->invoice?->allocations->pluck('payment')->filter()->unique('id')->values() ?? collect();

        return view('layouts.b2b.orders.show', [
            'order' => $order,
            'payments' => $payments,
            'paymentMethods' => $this->payments->methodOptions(),
            'outlets' => Outlets::query()->whereIn('id', request()->user()->outletIds())->orderBy('name')->get(['id', 'name']),
            // Riwayat pesanan + riwayat pembayarannya (log pembayaran disimpan dengan subject Payment).
            'activities' => ActivityLog::query()
                ->with('user:id,name')
                ->where(fn ($query) => $query
                    ->where(fn ($q) => $q->where('subject_type', $order->getMorphClass())->where('subject_id', $order->id))
                    ->orWhere(fn ($q) => $q->where('subject_type', (new Payment)->getMorphClass())->whereIn('subject_id', $payments->pluck('id'))))
                ->latest('id')
                ->get(),
            // Riwayat reminder invoice ini (otomatis + manual), terbaru dulu.
            'reminders' => $order->invoice
                ? $order->invoice->reminderLogs()->with('sender:id,name')->latest('b2b_reminder_logs.id')->limit(self::REMINDER_HISTORY_LIMIT)->get()
                : collect(),
        ]);
    }

    private const REMINDER_HISTORY_LIMIT = 10;

    public function complete(int $order)
    {
        $this->authorize('complete b2b/orders');
        $this->orders->complete(request()->user(), $this->order($order));

        return responseSuccess(true, 'Pesanan ditandai selesai');
    }

    public function cancel(Request $request, int $order)
    {
        $model = $this->order($order);
        $this->authorize($model->cancelPermission() ?? 'cancel b2b/orders');
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            // Wajib untuk pesanan yang sudah dibayar; dicek di OrderService/PaymentService.
            'refund' => ['nullable', 'array'],
            'refund.category_payment_id' => ['required_with:refund', 'integer'],
            'refund.payment_id' => ['nullable', 'integer'],
            'refund.reference' => ['nullable', 'string', 'max:100'],
        ], [
            'refund.category_payment_id.required_with' => 'Pilih metode pengembalian dana.',
        ], [
            'reason' => 'alasan',
            'refund.category_payment_id' => 'metode pengembalian dana',
            'refund.reference' => 'no. referensi',
        ]);
        $order = $this->orders->cancel($request->user(), $model, $validated['reason'], $validated['refund'] ?? null);
        $refunded = (int) $order->invoice()->first()?->refunds()->sum('amount');

        return responseSuccess(true, $refunded
            ? 'Pesanan dibatalkan. Pengembalian dana Rp '.number_format($refunded, 0, ',', '.').' tercatat, stok dikembalikan.'
            : 'Pesanan dibatalkan. Stok dikembalikan dan invoice dibatalkan.');
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

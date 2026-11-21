<?php

namespace App\Services\B2b;

use App\Models\B2b\Invoice;
use App\Models\B2b\Order;
use App\Models\B2b\Payment;
use App\Models\B2b\Refund;
use App\Support\B2b\ReportFilter;
use Illuminate\Database\Eloquent\Builder;

/**
 * Angka Laporan B2B. Definisi (disepakati 21 Nov 2026):
 * - Penjualan: grand_total pesanan Dikonfirmasi + Selesai menurut tanggal pesanan. Batal dan draft tidak dihitung.
 * - Kas: uang yang benar-benar diterima (pembayaran posted, atau reversed karena pesanan batal + refund) menurut tanggal bayar,
 *   dikurangi refund menurut tanggal refund. Pelunasan yang dibatalkan karena salah catat tidak dihitung sama sekali.
 * - Piutang: sisa invoice yang belum lunas per hari ini (tidak ikut periode), tetap ikut filter outlet dan customer.
 * Outlet = outlet pesanan.
 */
class ReportService
{
    /** Kartu ringkasan di bagian atas halaman. */
    public function summary(ReportFilter $filter): array
    {
        $sales = $this->orders($filter)
            ->whereIn('status', [Order::STATUS_CONFIRMED, Order::STATUS_COMPLETED])
            ->selectRaw('count(*) as orders, count(distinct b2b_customer_id) as customers, coalesce(sum(grand_total), 0) as amount')
            ->selectRaw('coalesce(sum(case when status = ? then 1 else 0 end), 0) as completed', [Order::STATUS_COMPLETED])
            ->first();
        $cancelled = $this->orders($filter)
            ->where('status', Order::STATUS_CANCELLED)
            ->selectRaw('count(*) as orders, coalesce(sum(grand_total), 0) as amount')
            ->first();

        $received = $this->receivedPayments($filter)->selectRaw('count(*) as total, coalesce(sum(amount), 0) as amount')->first();
        $refunded = $this->refunds($filter)->selectRaw('count(*) as total, coalesce(sum(amount), 0) as amount')->first();

        $outstanding = 'count(*) as invoices, count(distinct b2b_customer_id) as customers, coalesce(sum(total_to_pay - paid_amount), 0) as amount';
        $receivable = $this->openInvoices($filter)->selectRaw($outstanding)->first();
        $overdue = $this->openInvoices($filter)->whereDate('due_date', '<', today())->selectRaw($outstanding)->first();

        return [
            'as_of' => today()->toDateString(),
            'sales' => [
                'amount' => (int) $sales->amount,
                'orders' => (int) $sales->orders,
                'customers' => (int) $sales->customers,
                'completed' => (int) $sales->completed,
                'confirmed' => (int) $sales->orders - (int) $sales->completed,
            ],
            'cancelled' => ['amount' => (int) $cancelled->amount, 'orders' => (int) $cancelled->orders],
            'cash' => [
                'received' => (int) $received->amount,
                'received_count' => (int) $received->total,
                'refunded' => (int) $refunded->amount,
                'refund_count' => (int) $refunded->total,
                'net' => (int) $received->amount - (int) $refunded->amount,
            ],
            'receivable' => $this->outstanding($receivable),
            'overdue' => $this->outstanding($overdue),
        ];
    }

    /** Pesanan di periode, outlet, dan customer terpilih (semua status). */
    private function orders(ReportFilter $filter): Builder
    {
        return Order::query()
            ->whereIn('outlet_id', $filter->outletIds)
            ->whereBetween('order_date', $filter->between())
            ->when($filter->customerId, fn (Builder $query, int $id) => $query->where('b2b_customer_id', $id));
    }

    /** Pembayaran yang uangnya benar-benar diterima di periode. */
    private function receivedPayments(ReportFilter $filter): Builder
    {
        return Payment::query()
            ->whereBetween('paid_at', $filter->between())
            ->where(fn (Builder $query) => $query
                ->where('status', Payment::STATUS_POSTED)
                ->orWhere(fn (Builder $reversed) => $reversed->where('status', Payment::STATUS_REVERSED)->whereHas('refunds')))
            ->whereHas('allocations.invoice.order', fn (Builder $order) => $order->withTrashed()->whereIn('outlet_id', $filter->outletIds))
            ->when($filter->customerId, fn (Builder $query, int $id) => $query->where('b2b_customer_id', $id));
    }

    /** Pengembalian dana di periode. */
    private function refunds(ReportFilter $filter): Builder
    {
        return Refund::query()
            ->whereBetween('refunded_at', $filter->between())
            ->whereHas('invoice', fn (Builder $invoice) => $invoice
                ->when($filter->customerId, fn (Builder $query, int $id) => $query->where('b2b_customer_id', $id))
                ->whereHas('order', fn (Builder $order) => $order->withTrashed()->whereIn('outlet_id', $filter->outletIds)));
    }

    /** Invoice yang belum lunas per hari ini, tanpa melihat periode. */
    private function openInvoices(ReportFilter $filter): Builder
    {
        return Invoice::query()
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_OVERDUE])
            ->whereHas('order', fn (Builder $order) => $order->whereIn('outlet_id', $filter->outletIds))
            ->when($filter->customerId, fn (Builder $query, int $id) => $query->where('b2b_customer_id', $id));
    }

    private function outstanding(object $row): array
    {
        return ['amount' => (int) $row->amount, 'invoices' => (int) $row->invoices, 'customers' => (int) $row->customers];
    }
}

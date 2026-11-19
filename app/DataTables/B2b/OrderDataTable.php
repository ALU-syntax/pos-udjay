<?php

namespace App\DataTables\B2b;

use App\Models\B2b\Invoice;
use App\Models\B2b\Order;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class OrderDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('order_number', fn (Order $order) => '<div class="b2b-cust-cell">'
                .'<span class="b2b-doc-icon b2b-doc-icon--'.$order->statusVariant().'" aria-hidden="true"><i class="fas fa-shopping-bag"></i></span>'
                .'<div class="ob-cell-stack">'
                .'<a href="'.route('b2b/orders/show', $order->id).'" class="b2b-cust-cell__name b2b-mono">'.e($order->order_number).'</a>'
                .'<span class="ob-cell-meta">'.$order->order_date->translatedFormat('d M Y, H:i').'</span>'
                .'<span class="ob-cell-meta"><i class="fas fa-store me-1" aria-hidden="true"></i>'.e($order->outlet?->name).'</span>'
                .'</div></div>')
            ->addColumn('customer_name', function (Order $order) {
                $customer = $order->customer;
                $name = auth()->user()->can('read b2b/customers')
                    ? '<a href="'.route('b2b/customers/show', $order->b2b_customer_id).'" class="b2b-cust-cell__name">'.e($customer?->name).'</a>'
                    : '<span class="b2b-cust-cell__name">'.e($customer?->name).'</span>';

                return '<div class="b2b-cust-cell">'
                    .'<span class="b2b-avatar b2b-avatar--sm b2b-avatar--'.e($customer?->type).'" aria-hidden="true">'.e($customer?->initials()).'</span>'
                    .'<div class="ob-cell-stack">'.$name
                    .'<span class="ob-cell-meta">'.($order->contract
                        ? '<i class="fas fa-file-signature me-1" aria-hidden="true"></i>'.e($order->contract->contract_number)
                        : 'Harga normal').'</span>'
                    .'</div></div>';
            })
            ->editColumn('grand_total', fn (Order $order) => '<div class="ob-cell-stack align-items-end">'
                .'<span class="b2b-cell-strong b2b-money">Rp '.number_format($order->grand_total, 0, ',', '.').'</span>'
                .'<span class="ob-cell-meta">'.number_format($order->items_count, 0, ',', '.').' item</span></div>')
            ->addColumn('payment', fn (Order $order) => $this->paymentCell($order))
            ->editColumn('status', fn (Order $order) => '<span class="badge badge-'.$order->statusVariant().'">'.$order->statusLabel().'</span>')
            ->addColumn('action', fn (Order $order) => '<div class="ob-row-actions">'
                .($order->invoice ? '<a href="'.route('b2b/invoices/print', $order->invoice->id).'" target="_blank" class="ob-icon-btn ob-icon-btn--sm" title="Cetak invoice" aria-label="Cetak invoice '.e($order->invoice->invoice_number).'"><i class="fas fa-print" aria-hidden="true"></i></a>' : '')
                .'<a href="'.route('b2b/orders/show', $order->id).'" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--primary" title="Detail pesanan" aria-label="Detail pesanan '.e($order->order_number).'"><i class="fas fa-arrow-right" aria-hidden="true"></i></a>'
                .'</div>')
            ->rawColumns(['order_number', 'customer_name', 'grand_total', 'payment', 'status', 'action'])
            ->setRowId('id');
    }

    /** Jenis bayar + status invoice dalam satu sel: Bayar langsung / Tempo (jatuh tempo, sisa, telat). */
    private function paymentCell(Order $order): string
    {
        $invoice = $order->invoice;
        if (! $invoice) {
            return '<span class="text-muted">—</span>';
        }
        if ($invoice->status === Invoice::STATUS_VOID) {
            return '<span class="ob-cell-meta">Invoice dibatalkan</span>';
        }
        $lines = [];
        if ($order->payment_type === Order::PAYMENT_IMMEDIATE) {
            $lines[] = 'Bayar langsung';
        } elseif ($invoice->status === Invoice::STATUS_PAID) {
            $lines[] = 'Tempo';
        } else {
            $lines[] = 'Jatuh tempo '.$invoice->due_date->translatedFormat('d M Y');
            if ($invoice->displayStatus() === Invoice::STATUS_OVERDUE) {
                $lines[] = '<span class="text-danger fw-semibold">Telat '.$invoice->due_date->diffInDays(today()).' hari</span>';
            }
            if ($invoice->paid_amount > 0) {
                $lines[] = 'Sisa Rp '.number_format($invoice->outstandingAmount(), 0, ',', '.');
            }
        }

        return '<div class="ob-cell-stack"><span class="badge badge-'.$invoice->statusVariant().'">'.$invoice->statusLabel().'</span>'
            .implode('', array_map(fn ($line) => '<span class="ob-cell-meta text-nowrap">'.$line.'</span>', $lines)).'</div>';
    }

    public function query(Order $model): QueryBuilder
    {
        $outletIds = auth()->user()->outletIds();
        $query = $model->newQuery()
            ->select('b2b_orders.*')
            ->with(['customer', 'contract', 'outlet', 'invoice'])
            ->withCount('items')
            ->whereIn('b2b_orders.outlet_id', $outletIds);

        if (request()->filled('outlet_id') && in_array(request()->integer('outlet_id'), $outletIds, true)) {
            $query->where('b2b_orders.outlet_id', request()->integer('outlet_id'));
        }
        if (request()->filled('status')) {
            $query->where('b2b_orders.status', request('status'));
        }
        if (request()->filled('customer_id')) {
            $query->where('b2b_orders.b2b_customer_id', request()->integer('customer_id'));
        }
        if (request()->filled('date_from') && request()->filled('date_to')) {
            $query->whereBetween('b2b_orders.order_date', [
                Carbon::parse(request('date_from'))->startOfDay(),
                Carbon::parse(request('date_to'))->endOfDay(),
            ]);
        }
        // Filter pembayaran, sama dengan kartu ringkasan di atas tabel.
        $open = [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_OVERDUE];
        match (request('payment')) {
            'unpaid' => $query->whereHas('invoice', fn ($invoice) => $invoice->whereIn('status', $open)),
            'overdue' => $query->whereHas('invoice', fn ($invoice) => $invoice->whereIn('status', $open)
                ->whereDate('due_date', '<', today()->toDateString())),
            'paid' => $query->whereHas('invoice', fn ($invoice) => $invoice->where('status', Invoice::STATUS_PAID)),
            'immediate' => $query->where('b2b_orders.payment_type', Order::PAYMENT_IMMEDIATE),
            default => null,
        };

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('b2b-order-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'outlet_id' => "$('#outlet-filter').val()",
                'customer_id' => "$('#customer-filter').val()",
                'status' => "$('#status-filter').val()",
                'date_from' => "$('#date-from').val()",
                'date_to' => "$('#date-to').val()",
                'payment' => "$('#payment-filter').val()",
            ])
            ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
            ->orderBy(0, 'desc');
    }

    public function getColumns(): array
    {
        return [
            // Nomor pesanan berurutan, jadi urut nomor = urut waktu.
            Column::make('order_number')->title('Pesanan'),
            Column::make('customer_name')->title('Customer')->name('customer.name'),
            Column::computed('payment')->title('Pembayaran'),
            Column::make('status')->title('Status')->searchable(false),
            Column::make('grand_total')->title('Total')->searchable(false)->addClass('text-end'),
            Column::computed('action')->title('')->exportable(false)->printable(false)->width(90)->addClass('text-end'),
        ];
    }

    protected function filename(): string
    {
        return 'B2B_Pesanan_'.date('YmdHis');
    }
}

<?php

namespace App\DataTables\OrderTable;

use App\Models\OrderTable\OrderPayment;
use App\Support\OrderTable\Mask;
use App\Support\OrderTable\OrderLabels;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class PaymentMonitorDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        // Kolom raw di-escape manual
        return (new EloquentDataTable($query))
            ->editColumn('created_at', fn (OrderPayment $payment) => $payment->created_at
                ? $this->stack(e($payment->created_at->format('d M Y')), e($payment->created_at->format('H:i')))
                : '<span class="text-muted">—</span>')
            ->addColumn('order_no', fn (OrderPayment $payment) => $this->stack(
                '<span class="ob-order-no">'.e($payment->order?->order_no ?? '-').'</span>',
                e($payment->order?->outlet?->name ?? '-'),
            ))
            ->editColumn('method', fn (OrderPayment $payment) => e(OrderLabels::label(OrderLabels::PAYMENT_METHOD, $payment->method)))
            ->editColumn('gateway_ref', fn (OrderPayment $payment) => e(Mask::gatewayRef($payment->gateway_ref)))
            ->editColumn('amount', fn (OrderPayment $payment) => e('Rp '.number_format((int) $payment->amount, 0, ',', '.')))
            ->editColumn('status', fn (OrderPayment $payment) => $this->stack(
                OrderLabels::badge(OrderLabels::PAYMENT, $payment->status),
                e($this->statusDetail($payment)),
            ))
            ->removeColumn('order')
            // Payload callback gateway dan string QR tidak boleh sampai ke browser
            ->removeColumn('raw_callback')
            ->removeColumn('qr_string')
            ->rawColumns(['created_at', 'order_no', 'method', 'gateway_ref', 'amount', 'status'])
            ->setRowId('id');
    }

    public function query(OrderPayment $model): QueryBuilder
    {
        $outletIds = auth()->user()->outletIds();
        $query = $model->newQuery()
            ->with(['order.outlet'])
            ->whereHas('order', fn (QueryBuilder $order) => $order->whereIn('outlet_id', $outletIds));

        if (request()->filled('outlet_id')) {
            $outletId = request()->integer('outlet_id');
            if (in_array($outletId, $outletIds, true)) {
                $query->whereHas('order', fn (QueryBuilder $order) => $order->where('outlet_id', $outletId));
            }
        }
        if (request()->filled('method')) {
            $query->where('method', request('method'));
        }
        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }
        if (request()->filled('order_no')) {
            $term = request('order_no');
            $query->whereHas('order', fn (QueryBuilder $order) => $order->where('order_no', 'like', '%'.$term.'%'));
        }
        if (request()->filled('date_from')) {
            $query->whereDate('created_at', '>=', request('date_from'));
        }
        if (request()->filled('date_to')) {
            $query->whereDate('created_at', '<=', request('date_to'));
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('payment-monitor-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'outlet_id' => "$('#outlet-filter').val()",
                'method' => "$('#method-filter').val()",
                'status' => "$('#status-filter').val()",
                'order_no' => "$('#order-filter').val()",
                'date_from' => "$('#date-from-filter').val()",
                'date_to' => "$('#date-to-filter').val()",
            ])
            // Default Bootstrap 4 dom, but only the table scrolls horizontally so length/search/paging stay put.
            ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
            ->orderBy(0, 'desc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('created_at')->title('Dibuat')->addClass('ob-col-time'),
            Column::computed('order_no')->title('Order / Outlet')->addClass('ob-col-name'),
            Column::make('method')->title('Metode'),
            Column::make('gateway_ref')->title('Referensi Gateway')->addClass('ob-col-device'),
            Column::make('amount')->title('Nominal')->addClass('ob-col-amount'),
            Column::make('status')->title('Status'),
        ];
    }

    protected function filename(): string
    {
        return 'PaymentMonitor_'.date('YmdHis');
    }

    /** Sel dua baris; kedua argumen harus sudah di-escape. */
    private function stack(string $main, string $meta): string
    {
        return '<div class="ob-cell-stack"><span>'.$main.'</span><span class="ob-cell-meta">'.$meta.'</span></div>';
    }

    /** Waktu yang relevan dengan status: kapan dibayar, atau batas waktu pembayaran. */
    private function statusDetail(OrderPayment $payment): string
    {
        if ($payment->status === 'paid' && $payment->paid_at) {
            return 'Dibayar '.$payment->paid_at->format('d M Y, H:i');
        }
        if (in_array($payment->status, ['pending', 'expired'], true) && $payment->expires_at) {
            return 'Batas '.$payment->expires_at->format('d M Y, H:i');
        }

        return '';
    }
}

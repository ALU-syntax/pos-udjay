<?php

namespace App\DataTables\OrderTable;

use App\Models\OrderTable\Order;
use App\Support\OrderTable\Mask;
use App\Support\OrderTable\OrderLabels;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class OrderMonitorDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        // Kolom raw di-escape manual; atas_nama & device berasal dari pelanggan
        return (new EloquentDataTable($query))
            ->editColumn('created_at', fn (Order $order) => $order->created_at
                ? $this->stack(e($order->created_at->format('d M Y')), e($order->created_at->format('H:i')))
                : '<span class="text-muted">—</span>')
            ->editColumn('order_no', fn (Order $order) => $this->stack(
                '<span class="ob-order-no">'.e($order->order_no).'</span>',
                e($order->atas_nama ?: 'Tanpa nama'),
            ))
            // Kolom order menampilkan nomor + atas nama, jadi pencarian mencakup keduanya
            ->filterColumn('order_no', fn (QueryBuilder $query, string $keyword) => $query->where(
                fn (QueryBuilder $query) => $query->where('ot_orders.order_no', 'like', "%{$keyword}%")
                    ->orWhere('ot_orders.atas_nama', 'like', "%{$keyword}%")
            ))
            ->addColumn('outlet_table', fn (Order $order) => $this->stack(
                e($order->outlet?->name ?? '-'),
                e($order->table?->name ?? $order->table?->code ?? '-'),
            ))
            ->editColumn('grand_total', fn (Order $order) => e('Rp '.number_format((int) $order->grand_total, 0, ',', '.')))
            ->editColumn('status', fn (Order $order) => OrderLabels::badge(OrderLabels::ORDER, $order->status))
            ->editColumn('payment_status', fn (Order $order) => $this->stack(
                OrderLabels::badge(OrderLabels::PAYMENT, $order->payment_status),
                e(OrderLabels::label(OrderLabels::PAYMENT_MODE, $order->payment_mode)),
            ))
            ->editColumn('pos_bridge_status', fn (Order $order) => OrderLabels::badge(OrderLabels::BRIDGE, $order->pos_bridge_status))
            ->editColumn('device_id', fn (Order $order) => e(Mask::value($order->device_id)))
            ->addColumn('action', fn (Order $order) => view('layouts.order-table.orders.action', compact('order'))->render())
            ->removeColumn('outlet')
            ->removeColumn('table')
            ->rawColumns(['created_at', 'order_no', 'outlet_table', 'grand_total', 'status', 'payment_status', 'pos_bridge_status', 'device_id', 'action'])
            ->setRowId('id');
    }

    public function query(Order $model): QueryBuilder
    {
        $outletIds = auth()->user()->outletIds();
        $query = $model->newQuery()
            ->with(['outlet', 'table'])
            ->whereIn('outlet_id', $outletIds);

        $outletId = request()->integer('outlet_id');
        if ($outletId && in_array($outletId, $outletIds, true)) {
            $query->where('outlet_id', $outletId);
        }
        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }
        if (request()->filled('payment_status')) {
            $query->where('payment_status', request('payment_status'));
        }
        if (request()->filled('payment_mode')) {
            $query->where('payment_mode', request('payment_mode'));
        }
        if (request()->filled('bridge_status')) {
            $query->where('pos_bridge_status', request('bridge_status'));
        }
        if (request()->filled('order_no')) {
            $query->where('order_no', 'like', '%'.request('order_no').'%');
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
            ->setTableId('order-monitor-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'outlet_id' => "$('#outlet-filter').val()",
                'status' => "$('#status-filter').val()",
                'payment_status' => "$('#payment-status-filter').val()",
                'payment_mode' => "$('#payment-mode-filter').val()",
                'bridge_status' => "$('#bridge-status-filter').val()",
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
            Column::make('created_at')->title('Waktu')->addClass('ob-col-time'),
            Column::make('order_no')->title('Order'),
            Column::computed('outlet_table')->title('Outlet / Meja')->addClass('ob-col-name'),
            Column::make('grand_total')->title('Total')->addClass('ob-col-amount'),
            Column::make('status')->title('Status'),
            Column::make('payment_status')->title('Pembayaran'),
            Column::make('pos_bridge_status')->title('Bridge'),
            Column::make('device_id')->title('Perangkat')->addClass('ob-col-device'),
            Column::computed('action')->title('')->exportable(false)->printable(false)->orderable(false)->searchable(false)->width(60)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'OrderMonitor_'.date('YmdHis');
    }

    /** Sel dua baris; kedua argumen harus sudah di-escape. */
    private function stack(string $main, string $meta): string
    {
        return '<div class="ob-cell-stack"><span>'.$main.'</span><span class="ob-cell-meta">'.$meta.'</span></div>';
    }
}

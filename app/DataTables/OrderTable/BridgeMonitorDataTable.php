<?php

namespace App\DataTables\OrderTable;

use App\Models\OrderTable\Order;
use App\Services\OrderTable\OrderTableNodeClient;
use App\Support\OrderTable\Mask;
use App\Support\OrderTable\OrderLabels;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class BridgeMonitorDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        // Sama dengan pengecekan di controller, agar tombol tidak aktif saat request pasti ditolak
        $nodeEnabled = app(OrderTableNodeClient::class)->enabled();

        // Kolom raw di-escape manual; atas_nama & device berasal dari pelanggan
        return (new EloquentDataTable($query))
            ->editColumn('created_at', fn (Order $order) => $this->dateStack($order->created_at))
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
            ->editColumn('paid_at', fn (Order $order) => $this->dateStack($order->paid_at))
            ->editColumn('pos_bridge_status', fn (Order $order) => $this->stack(
                OrderLabels::badge(OrderLabels::BRIDGE, $order->pos_bridge_status),
                e(match (true) {
                    filled($order->pos_transaction_id) => 'POS Trx #'.$order->pos_transaction_id,
                    $order->pos_bridge_status === 'failed' => 'Belum tercatat di POS',
                    default => 'Menunggu diproses',
                }),
            ))
            ->editColumn('device_id', fn (Order $order) => e(Mask::value($order->device_id)))
            // Array eksplisit: compact() di arrow function tidak ikut menangkap $nodeEnabled
            ->addColumn('action', fn (Order $order) => view('layouts.order-table.bridge.action', [
                'order' => $order,
                'nodeEnabled' => $nodeEnabled,
            ])->render())
            ->removeColumn('outlet')
            ->removeColumn('table')
            ->rawColumns(['created_at', 'order_no', 'outlet_table', 'grand_total', 'paid_at', 'pos_bridge_status', 'device_id', 'action'])
            ->setRowId('id');
    }

    public function query(Order $model): QueryBuilder
    {
        $outletIds = auth()->user()->outletIds();
        $query = $model->newQuery()
            ->with(['outlet', 'table'])
            ->whereIn('outlet_id', $outletIds)
            ->where('payment_status', 'paid');

        $outletId = request()->integer('outlet_id');
        if ($outletId && in_array($outletId, $outletIds, true)) {
            $query->where('outlet_id', $outletId);
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
            ->setTableId('bridge-monitor-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'outlet_id' => "$('#outlet-filter').val()",
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
            Column::make('paid_at')->title('Dibayar')->addClass('ob-col-time'),
            Column::make('pos_bridge_status')->title('Bridge POS'),
            Column::make('device_id')->title('Perangkat')->addClass('ob-col-device'),
            Column::computed('action')->title('')->exportable(false)->printable(false)->orderable(false)->searchable(false)->width(60)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'BridgeMonitor_'.date('YmdHis');
    }

    /** Sel dua baris; kedua argumen harus sudah di-escape. */
    private function stack(string $main, string $meta): string
    {
        return '<div class="ob-cell-stack"><span>'.$main.'</span><span class="ob-cell-meta">'.$meta.'</span></div>';
    }

    private function dateStack($date): string
    {
        return $date
            ? $this->stack(e($date->format('d M Y')), e($date->format('H:i')))
            : '<span class="text-muted">—</span>';
    }
}

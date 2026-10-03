<?php

namespace App\DataTables\OrderTable;

use App\Models\OrderTable\Order;
use App\Support\OrderTable\Mask;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class BridgeMonitorDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('created_at', fn (Order $order) => $order->created_at?->format('d M Y H:i') ?? '-')
            ->editColumn('paid_at', fn (Order $order) => $order->paid_at?->format('d M Y H:i') ?? '-')
            ->addColumn('outlet_name', fn (Order $order) => $order->outlet?->name ?? '-')
            ->editColumn('device_id', fn (Order $order) => Mask::value($order->device_id))
            ->editColumn('grand_total', fn (Order $order) => 'Rp '.number_format((int) $order->grand_total, 0, ',', '.'))
            ->editColumn('pos_bridge_status', fn (Order $order) => '<span class="badge bg-'.self::bridgeColor($order->pos_bridge_status).'">'.e($order->pos_bridge_status).'</span>')
            ->editColumn('pos_transaction_id', fn (Order $order) => $order->pos_transaction_id ?? '-')
            ->addColumn('action', fn (Order $order) => view('layouts.order-table.bridge.action', compact('order'))->render())
            ->removeColumn('outlet')
            ->rawColumns(['pos_bridge_status', 'action'])
            ->setRowId('id');
    }

    public function query(Order $model): QueryBuilder
    {
        $outletIds = auth()->user()->outletIds();
        $query = $model->newQuery()
            ->with('outlet')
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
            ->responsive(true)
            ->orderBy(0, 'desc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('created_at')->title('Waktu'),
            Column::make('order_no')->title('Order'),
            Column::computed('outlet_name')->title('Outlet')->name('outlet.name'),
            Column::make('device_id')->title('Perangkat'),
            Column::make('grand_total')->title('Total'),
            Column::make('pos_bridge_status')->title('Status Bridge'),
            Column::make('pos_transaction_id')->title('POS Trx ID'),
            Column::make('paid_at')->title('Dibayar'),
            Column::computed('action')->title('Aksi')->exportable(false)->printable(false)->orderable(false)->searchable(false)->width(90)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'BridgeMonitor_'.date('YmdHis');
    }

    private static function bridgeColor(string $status): string
    {
        return match ($status) {
            'linked' => 'success',
            'failed' => 'danger',
            default => 'warning',
        };
    }
}

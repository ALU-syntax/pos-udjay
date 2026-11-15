<?php

namespace App\DataTables\B2b;

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
            ->editColumn('order_number', fn (Order $order) => '<div class="ob-cell-stack">'
                .'<a href="'.route('b2b/orders/show', $order->id).'" class="fw-bold">'.e($order->order_number).'</a>'
                .'<span class="ob-cell-meta">'.$order->order_date->translatedFormat('d M Y, H:i').'</span></div>')
            ->addColumn('customer_name', fn (Order $order) => '<div class="ob-cell-stack">'
                .e($order->customer?->name)
                .'<span class="ob-cell-meta">'.($order->contract ? 'Kontrak '.e($order->contract->contract_number) : 'Harga normal').'</span></div>')
            ->addColumn('outlet_name', fn (Order $order) => e($order->outlet?->name))
            ->editColumn('grand_total', fn (Order $order) => '<span class="text-nowrap">Rp '.number_format($order->grand_total, 0, ',', '.').'</span>')
            ->editColumn('status', fn (Order $order) => '<span class="badge badge-'.$order->statusVariant().'">'.$order->statusLabel().'</span>')
            ->addColumn('invoice', function (Order $order) {
                if (! $order->invoice) {
                    return '<span class="text-muted">-</span>';
                }

                return '<div class="ob-cell-stack"><span class="badge badge-'.$order->invoice->statusVariant().'">'.$order->invoice->statusLabel().'</span>'
                    .'<span class="ob-cell-meta">Jatuh tempo '.$order->invoice->due_date->translatedFormat('d M Y').'</span></div>';
            })
            ->addColumn('action', fn (Order $order) => '<div class="ob-row-actions">'
                .'<a href="'.route('b2b/orders/show', $order->id).'" class="ob-icon-btn ob-icon-btn--sm" title="Detail pesanan" aria-label="Detail pesanan"><i class="fas fa-eye" aria-hidden="true"></i></a>'
                .($order->invoice ? '<a href="'.route('b2b/invoices/print', $order->invoice->id).'" target="_blank" class="ob-icon-btn ob-icon-btn--sm" title="Cetak invoice" aria-label="Cetak invoice"><i class="fas fa-print" aria-hidden="true"></i></a>' : '')
                .'</div>')
            ->rawColumns(['order_number', 'customer_name', 'grand_total', 'status', 'invoice', 'action'])
            ->setRowId('id');
    }

    public function query(Order $model): QueryBuilder
    {
        $outletIds = auth()->user()->outletIds();
        $query = $model->newQuery()
            ->select('b2b_orders.*')
            ->with(['customer', 'contract', 'outlet', 'invoice'])
            ->whereIn('b2b_orders.outlet_id', $outletIds);

        if (request()->filled('outlet_id') && in_array(request()->integer('outlet_id'), $outletIds, true)) {
            $query->where('b2b_orders.outlet_id', request()->integer('outlet_id'));
        }
        if (request()->filled('status')) {
            $query->where('b2b_orders.status', request('status'));
        }
        if (request()->filled('date_from') && request()->filled('date_to')) {
            $query->whereBetween('b2b_orders.order_date', [
                Carbon::parse(request('date_from'))->startOfDay(),
                Carbon::parse(request('date_to'))->endOfDay(),
            ]);
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('b2b-order-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'outlet_id' => "$('#outlet-filter').val()",
                'status' => "$('#status-filter').val()",
                'date_from' => "$('#date-from').val()",
                'date_to' => "$('#date-to').val()",
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
            Column::make('outlet_name')->title('Outlet')->name('outlet.name'),
            Column::make('grand_total')->title('Total')->searchable(false),
            Column::make('status')->title('Status')->searchable(false),
            Column::computed('invoice')->title('Invoice'),
            Column::computed('action')->title('')->exportable(false)->printable(false)->width(90)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'B2B_Pesanan_'.date('YmdHis');
    }
}

<?php

namespace App\DataTables;

use App\Models\RawMaterialRequests;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class RequestOrderDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function (RawMaterialRequests $requestOrder) {
                return view('layouts.request_order.action', compact('requestOrder'))->render();
            })
            ->editColumn('request_number', function (RawMaterialRequests $requestOrder) {
                return '<span class="badge bg-light text-dark border">' . e($requestOrder->request_number) . '</span>';
            })
            ->addColumn('status_name', function (RawMaterialRequests $requestOrder) {
                $status = $requestOrder->status;
                $code = $status?->code ?? 'unknown';
                $class = match ($code) {
                    'draft' => 'bg-secondary',
                    'submitted' => 'bg-primary',
                    'approved', 'fulfilled' => 'bg-success',
                    'waiting_stock', 'partially_fulfilled' => 'bg-warning text-dark',
                    'waiting_procurement' => 'bg-info text-dark',
                    'rejected' => 'bg-danger',
                    'cancelled' => 'bg-dark',
                    default => 'bg-light text-dark border',
                };

                return '<span class="badge ' . $class . '">' . e($status?->name ?? '-') . '</span>'
                    . '<span class="d-none">' . e($code) . '</span>';
            })
            ->addColumn('requester_name', function (RawMaterialRequests $requestOrder) {
                return $requestOrder->requesterInventory
                    ? e($requestOrder->requesterInventory->name)
                    : '<span class="text-muted">-</span>';
            })
            ->addColumn('fulfillment_name', function (RawMaterialRequests $requestOrder) {
                return $requestOrder->fulfillmentLocation
                    ? e($requestOrder->fulfillmentLocation->name)
                    : '<span class="text-muted">Belum ditentukan</span>';
            })
            ->addColumn('items_count_label', function (RawMaterialRequests $requestOrder) {
                return '<strong>' . (int) ($requestOrder->items_count ?? 0) . '</strong> item';
            })
            ->editColumn('needed_at', function (RawMaterialRequests $requestOrder) {
                return $requestOrder->needed_at ? $requestOrder->needed_at->format('d M Y') : '<span class="text-muted">-</span>';
            })
            ->editColumn('requested_at', function (RawMaterialRequests $requestOrder) {
                return $requestOrder->requested_at ? $requestOrder->requested_at->format('d M Y H:i') : '<span class="text-muted">Belum submit</span>';
            })
            ->editColumn('updated_at', function (RawMaterialRequests $requestOrder) {
                return $requestOrder->updated_at ? $requestOrder->updated_at->format('d M Y H:i') : '-';
            })
            ->filterColumn('status_name', function (QueryBuilder $query, $keyword) {
                $query->whereHas('status', fn ($status) => $status
                    ->where('code', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%"));
            })
            ->filterColumn('requester_name', function (QueryBuilder $query, $keyword) {
                $query->whereHas('requesterInventory', fn ($inventory) => $inventory->where('name', 'like', "%{$keyword}%"));
            })
            ->filterColumn('fulfillment_name', function (QueryBuilder $query, $keyword) {
                if ($keyword === '__empty__') {
                    $query->whereNull('fulfillment_location_id');
                    return;
                }

                $query->whereHas('fulfillmentLocation', fn ($inventory) => $inventory->where('name', 'like', "%{$keyword}%"));
            })
            ->addIndexColumn()
            ->rawColumns([
                'action',
                'request_number',
                'status_name',
                'requester_name',
                'fulfillment_name',
                'items_count_label',
                'needed_at',
                'requested_at',
            ]);
    }

    public function query(RawMaterialRequests $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['status', 'requesterInventory', 'fulfillmentLocation'])
            ->withCount('items')
            ->latest('updated_at');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('request-order-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->responsive(true)
            ->orderBy(8, 'desc')
            ->parameters([
                'autoWidth' => false,
                'scrollX' => true,
                'scrollCollapse' => true,
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::make('DT_RowIndex')->title('No')->searchable(false)->orderable(false)->width(48),
            Column::make('request_number')->title('Nomor Request'),
            Column::make('status_name')->title('Status')->orderable(false),
            Column::make('requester_name')->title('Pemohon')->orderable(false),
            Column::make('fulfillment_name')->title('Dipenuhi Oleh')->orderable(false),
            Column::make('needed_at')->title('Dibutuhkan'),
            Column::make('items_count_label')->title('Item')->searchable(false)->orderable(false),
            Column::make('requested_at')->title('Submit Pada'),
            Column::make('updated_at')->title('Update Terakhir'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(90)
                ->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'Request_Order_' . date('YmdHis');
    }
}

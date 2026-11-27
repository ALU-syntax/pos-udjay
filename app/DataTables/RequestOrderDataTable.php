<?php

namespace App\DataTables;

use App\Models\RawMaterialRequestStatus;
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
            ->editColumn('request_number', fn (RawMaterialRequests $requestOrder) => '<div class="ob-cell-stack">'
                . '<span class="ob-item-cell__title">' . e($requestOrder->request_number) . '</span>'
                . '<span class="ob-cell-meta">Dibuat ' . e($requestOrder->created_at?->translatedFormat('d M Y, H:i') ?? '-') . '</span>'
                . '</div>')
            ->addColumn('route', fn (RawMaterialRequests $requestOrder) => '<div class="ob-cell-stack">'
                . '<span class="ro-strong">' . e($requestOrder->requesterInventory?->name ?? '-') . '</span>'
                . '<span class="ob-cell-meta"><i class="fas fa-long-arrow-alt-right" aria-hidden="true"></i> '
                . ($requestOrder->fulfillmentInventory ? e($requestOrder->fulfillmentInventory->name) : 'Pemenuh belum ditentukan')
                . '</span>'
                . '</div>')
            ->addColumn('items_count_label', fn (RawMaterialRequests $requestOrder) => '<span class="ro-strong">'
                . number_format((int) $requestOrder->items_count, 0, ',', '.') . ' item</span>')
            ->editColumn('needed_at', function (RawMaterialRequests $requestOrder) {
                if (! $requestOrder->needed_at) {
                    return '<span class="ob-cell-meta">-</span>';
                }

                $date = e($requestOrder->needed_at->translatedFormat('d M Y'));
                $overdue = $requestOrder->needed_at->lt(today())
                    && ! in_array($requestOrder->status?->code, RawMaterialRequestStatus::CLOSED, true);

                return $overdue
                    ? '<div class="ob-cell-stack"><span class="ro-overdue">' . $date . '</span><span class="ob-cell-meta">Lewat tanggal</span></div>'
                    : '<span class="ro-date">' . $date . '</span>';
            })
            ->addColumn('status_name', fn (RawMaterialRequests $requestOrder) => $requestOrder->status
                ? '<span class="badge ' . $requestOrder->status->badge_class . '">' . e($requestOrder->status->label) . '</span>'
                : '<span class="ob-cell-meta">-</span>')
            ->editColumn('updated_at', fn (RawMaterialRequests $requestOrder) => $requestOrder->updated_at
                ? '<span class="ob-cell-meta">' . e($requestOrder->updated_at->translatedFormat('d M Y, H:i')) . '</span>'
                : '<span class="ob-cell-meta">-</span>')
            ->filterColumn('request_number', fn (QueryBuilder $query, string $keyword) => $query
                ->where('raw_material_requests.request_number', 'like', "%{$keyword}%"))
            ->rawColumns(['action', 'request_number', 'route', 'items_count_label', 'needed_at', 'status_name', 'updated_at'])
            ->setRowId('id');
    }

    public function query(RawMaterialRequests $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['status', 'requesterInventory', 'fulfillmentInventory'])
            ->withCount('items');

        // Filter dikirim lewat parameter ajax (bukan pencarian kolom) agar bisa dikombinasikan
        $status = $this->request()->get('status');
        if (ctype_digit((string) $status)) {
            $query->where('status_id', (int) $status);
        }

        $requester = $this->request()->get('requester');
        if (ctype_digit((string) $requester)) {
            $query->where('requester_inventory_id', (int) $requester);
        }

        $fulfillment = $this->request()->get('fulfillment');
        if ($fulfillment === 'none') {
            $query->whereNull('fulfillment_inventory_id');
        } elseif (ctype_digit((string) $fulfillment)) {
            $query->where('fulfillment_inventory_id', (int) $fulfillment);
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('request-order-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'status' => "$('#filter-status').val()",
                'requester' => "$('#filter-requester').val()",
                'fulfillment' => "$('#filter-fulfillment').val()",
            ])
            // Hanya tabel yang scroll horizontal, length/search/paging tetap di tempat.
            ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
            // Terbaru di atas
            ->orderBy(5, 'desc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('request_number')->title('Request'),
            Column::make('route')->title('Peminta → Pemenuh')->orderable(false)->searchable(false),
            Column::make('items_count_label')->title('Item')->orderable(false)->searchable(false),
            Column::make('needed_at')->title('Dibutuhkan')->searchable(false),
            Column::make('status_name')->title('Status')->orderable(false)->searchable(false),
            Column::make('updated_at')->title('Diperbarui')->searchable(false),
            Column::computed('action')
                ->title('')
                ->exportable(false)
                ->printable(false)
                ->width(140)
                ->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'Request_Order_' . date('YmdHis');
    }
}

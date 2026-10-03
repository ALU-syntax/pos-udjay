<?php

namespace App\DataTables\OrderTable;

use App\Models\OrderTable\TableSession;
use App\Support\OrderTable\Mask;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class SessionMonitorDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('opened_at', fn (TableSession $session) => $session->opened_at?->format('d M Y H:i') ?? '-')
            ->editColumn('closed_at', fn (TableSession $session) => $session->closed_at?->format('d M Y H:i') ?? '-')
            ->addColumn('outlet_name', fn (TableSession $session) => $session->outlet?->name ?? '-')
            ->addColumn('table_name', fn (TableSession $session) => $session->table?->name ?? $session->table?->code ?? '-')
            ->editColumn('device_id', fn (TableSession $session) => Mask::value($session->device_id))
            ->editColumn('status', fn (TableSession $session) => $session->status === 'open'
                ? '<span class="badge bg-success">open</span>'
                : '<span class="badge bg-secondary">closed</span>')
            ->addColumn('orders_count', fn (TableSession $session) => $session->orders_count ?? 0)
            ->addColumn('action', fn (TableSession $session) => view('layouts.order-table.sessions.action', compact('session'))->render())
            ->removeColumn('outlet')
            ->removeColumn('table')
            ->rawColumns(['status', 'action'])
            ->setRowId('id');
    }

    public function query(TableSession $model): QueryBuilder
    {
        $outletIds = auth()->user()->outletIds();
        $query = $model->newQuery()
            ->with(['outlet', 'table'])
            ->withCount('orders')
            ->whereIn('outlet_id', $outletIds);

        $outletId = request()->integer('outlet_id');
        if ($outletId && in_array($outletId, $outletIds, true)) {
            $query->where('outlet_id', $outletId);
        }
        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }
        if (request()->filled('date_from')) {
            $query->whereDate('opened_at', '>=', request('date_from'));
        }
        if (request()->filled('date_to')) {
            $query->whereDate('opened_at', '<=', request('date_to'));
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('session-monitor-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'outlet_id' => "$('#outlet-filter').val()",
                'status' => "$('#status-filter').val()",
                'date_from' => "$('#date-from-filter').val()",
                'date_to' => "$('#date-to-filter').val()",
            ])
            ->responsive(true)
            ->orderBy(0, 'desc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('opened_at')->title('Dibuka'),
            Column::computed('outlet_name')->title('Outlet')->name('outlet.name'),
            Column::computed('table_name')->title('Meja')->name('table.name'),
            Column::make('device_id')->title('Perangkat'),
            Column::make('status')->title('Status'),
            Column::make('close_reason')->title('Alasan Tutup'),
            Column::make('closed_by')->title('Ditutup Oleh'),
            Column::computed('orders_count')->title('Order')->orderable(false)->searchable(false),
            Column::make('closed_at')->title('Ditutup'),
            Column::computed('action')->title('Aksi')->exportable(false)->printable(false)->orderable(false)->searchable(false)->width(80)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'SessionMonitor_'.date('YmdHis');
    }
}

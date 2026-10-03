<?php

namespace App\DataTables\OrderTable;

use App\Models\OrderTable\DiningTable;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class DiningTableDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('outlet_name', fn (DiningTable $table) => e($table->outlet?->name ?? '-'))
            ->editColumn('code', fn (DiningTable $table) => '<strong>'.e($table->code).'</strong>')
            ->editColumn('capacity', fn (DiningTable $table) => $table->capacity ? e($table->capacity.' orang') : '<span class="text-muted">-</span>')
            ->editColumn('qr_active', fn (DiningTable $table) => $table->qr_active
                ? '<span class="badge badge-success">Aktif</span>'
                : '<span class="badge badge-secondary">Nonaktif</span>')
            ->addColumn('action', fn (DiningTable $table) => view(
                'layouts.order-table.dining-tables.action',
                compact('table')
            )->render())
            ->rawColumns(['code', 'capacity', 'qr_active', 'action'])
            ->setRowId('id');
    }

    public function query(DiningTable $model): QueryBuilder
    {
        $outletIds = auth()->user()->outletIds();
        $query = $model->newQuery()
            ->with('outlet')
            ->whereIn('outlet_id', $outletIds);

        $outletId = request()->integer('outlet_id');
        if ($outletId && in_array($outletId, $outletIds, true)) {
            $query->where('outlet_id', $outletId);
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('dining-table-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, ['outlet_id' => "$('#outlet-filter').val()"])
            ->responsive(true)
            ->orderBy(1);
    }

    public function getColumns(): array
    {
        return [
            Column::make('outlet_name')->title('Outlet')->name('outlet.name'),
            Column::make('code')->title('Kode Meja'),
            Column::make('name')->title('Nama Meja'),
            Column::make('capacity')->title('Kapasitas'),
            Column::make('qr_active')->title('Status QR'),
            Column::computed('action')->title('Aksi')->exportable(false)->printable(false)->orderable(false)->searchable(false)->width(250)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'DiningTable_'.date('YmdHis');
    }
}

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
            ->editColumn('code', fn (DiningTable $table) => $this->tableCell($table))
            // Kolom meja menampilkan kode + nama, jadi pencarian mencakup keduanya
            ->filterColumn('code', fn (QueryBuilder $query, string $keyword) => $query->where(
                fn (QueryBuilder $query) => $query->where('code', 'like', "%{$keyword}%")->orWhere('name', 'like', "%{$keyword}%")
            ))
            ->addColumn('outlet_name', fn (DiningTable $table) => e($table->outlet?->name ?? '-'))
            ->editColumn('capacity', fn (DiningTable $table) => $table->capacity
                ? '<span class="ob-cell-capacity"><i class="fas fa-user-friends" aria-hidden="true"></i> '.e($table->capacity).' orang</span>'
                : '<span class="text-muted">—</span>')
            ->editColumn('qr_active', fn (DiningTable $table) => $table->qr_active
                ? '<span class="badge badge-success">Aktif</span>'
                : '<span class="badge badge-muted">Nonaktif</span>')
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
            // Default Bootstrap 4 dom, but only the table scrolls horizontally so length/search/paging stay put.
            ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
            ->orderBy(0, 'asc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('code')->title('Meja'),
            Column::make('outlet_name')->title('Outlet')->name('outlet.name'),
            Column::make('capacity')->title('Kapasitas')->searchable(false),
            Column::make('qr_active')->title('Status QR')->searchable(false),
            Column::computed('action')->title('')->exportable(false)->printable(false)->orderable(false)->searchable(false)->width(240)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'DiningTable_'.date('YmdHis');
    }

    private function tableCell(DiningTable $table): string
    {
        return '<div class="ob-table-cell">'
            .'<span class="ob-table-code">'.e($table->code).'</span>'
            .'<span class="ob-cell-meta">'.e($table->name).'</span>'
            .'</div>';
    }
}

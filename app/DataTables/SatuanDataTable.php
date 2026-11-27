<?php

namespace App\DataTables;

use App\Models\Satuan;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class SatuanDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function (Satuan $satuan) {
                return view('layouts.satuan.action', compact('satuan'))->render();
            })
            // Kolom satuan menggabungkan nama + simbol, jadi pencarian mencakup keduanya
            ->editColumn('name', function (Satuan $satuan) {
                $symbol = $satuan->symbol
                    ? '<span class="ob-chip">' . e($satuan->symbol) . '</span>'
                    : '<span class="ob-cell-meta">Tanpa simbol</span>';

                return '<div class="ob-cell-stack">'
                    . '<span class="ob-item-cell__title">' . e($satuan->name) . '</span>'
                    . $symbol
                    . '</div>';
            })
            ->filterColumn('name', fn (QueryBuilder $query, string $keyword) => $query->where(
                fn (QueryBuilder $query) => $query->where('satuans.name', 'like', "%{$keyword}%")
                    ->orWhere('satuans.symbol', 'like', "%{$keyword}%")
            ))
            ->editColumn('raw_materials_count', function (Satuan $satuan) {
                return $satuan->raw_materials_count > 0
                    ? '<span class="sat-strong">' . number_format($satuan->raw_materials_count, 0, ',', '.') . ' bahan baku</span>'
                    : '<span class="ob-cell-meta">Belum dipakai</span>';
            })
            ->editColumn('is_active', fn (Satuan $satuan) => $satuan->is_active
                ? '<span class="badge badge-success">Aktif</span>'
                : '<span class="badge badge-muted">Nonaktif</span>')
            ->rawColumns(['action', 'name', 'raw_materials_count', 'is_active'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Satuan $model): QueryBuilder
    {
        // Hanya bahan baku yang belum dihapus (soft delete) yang dihitung
        $query = $model->newQuery()->withCount('rawMaterials');

        $status = $this->request()->get('status');
        if ($status === '0' || $status === '1') {
            $query->where('is_active', (int) $status);
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('satuan-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax('', null, [
                        'status' => "$('#filter-status').val()",
                    ])
                    // Hanya tabel yang scroll horizontal, length/search/paging tetap di tempat.
                    ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
                    ->orderBy(0, 'asc');
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('name')->title('Satuan'),
            Column::make('raw_materials_count')->title('Dipakai oleh')->searchable(false),
            Column::make('is_active')->title('Status')->searchable(false),
            Column::computed('action')
                  ->title('')
                  ->exportable(false)
                  ->printable(false)
                  ->width(70)
                  ->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Satuan_' . date('YmdHis');
    }
}

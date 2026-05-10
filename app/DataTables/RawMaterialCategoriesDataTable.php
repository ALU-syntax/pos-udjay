<?php

namespace App\DataTables;

use App\Models\RawMaterialCategories;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class RawMaterialCategoriesDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function (RawMaterialCategories $category) {
                return view('layouts.kategori_bahan_baku.action', compact('category'))->render();
            })
            ->editColumn('raw_materials_count', function (RawMaterialCategories $category) {
                return '<span class="material-count-pill">' . $category->raw_materials_count . ' bahan</span>';
            })
            ->editColumn('is_active', function (RawMaterialCategories $category) {
                return $category->is_active
                    ? '<span class="badge badge-pill badge-success">Aktif</span>'
                    : '<span class="badge badge-pill badge-secondary">Tidak Aktif</span>';
            })
            ->editColumn('notes', function (RawMaterialCategories $category) {
                if (!$category->notes) {
                    return '<span class="text-muted">-</span>';
                }

                return e(Str::limit($category->notes, 80));
            })
            ->editColumn('updated_at', function (RawMaterialCategories $category) {
                return $category->updated_at ? $category->updated_at->format('d M Y H:i') : '-';
            })
            ->filterColumn('raw_materials_count', function (QueryBuilder $query, $keyword) {
                if ($keyword === 'used') {
                    $query->has('rawMaterials');
                }

                if ($keyword === 'empty') {
                    $query->doesntHave('rawMaterials');
                }
            })
            ->addIndexColumn()
            ->rawColumns(['action', 'raw_materials_count', 'is_active', 'notes']);
    }

    public function query(RawMaterialCategories $model): QueryBuilder
    {
        return $model->newQuery()
            ->withCount('rawMaterials');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('raw-material-categories-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->responsive(true)
            ->orderBy(1);
    }

    public function getColumns(): array
    {
        return [
            Column::make('DT_RowIndex')->title('No')->searchable(false)->orderable(false)->width(48),
            Column::make('name')->title('Nama Kategori'),
            Column::make('raw_materials_count')->title('Jumlah Bahan')->searchable(true)->orderable(true),
            Column::make('is_active')->title('Status'),
            Column::make('notes')->title('Catatan')->orderable(false),
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
        return 'Kategori_Bahan_Baku_' . date('YmdHis');
    }
}

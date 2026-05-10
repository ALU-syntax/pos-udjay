<?php

namespace App\DataTables;

use App\Models\RawMaterials;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class RawMaterialsDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function (RawMaterials $rawMaterial) {
                return view('layouts.bahan_baku.action', compact('rawMaterial'))->render();
            })
            ->editColumn('code', function (RawMaterials $rawMaterial) {
                return $rawMaterial->code
                    ? '<span class="badge bg-light text-dark border">' . e($rawMaterial->code) . '</span>'
                    : '<span class="text-muted">-</span>';
            })
            ->addColumn('category_name', function (RawMaterials $rawMaterial) {
                return $rawMaterial->category
                    ? e($rawMaterial->category->name)
                    : '<span class="text-muted">Tanpa kategori</span>';
            })
            ->addColumn('base_unit_name', function (RawMaterials $rawMaterial) {
                $label = optional($rawMaterial->baseUnit)->symbol ?: optional($rawMaterial->baseUnit)->name;

                return $label
                    ? '<span class="unit-pill">' . e($label) . '</span>'
                    : '<span class="text-muted">-</span>';
            })
            ->addColumn('storage_type_name', function (RawMaterials $rawMaterial) {
                return $rawMaterial->storageType
                    ? '<span class="storage-pill">' . e($rawMaterial->storageType->name) . '</span>'
                    : '<span class="text-muted">-</span>';
            })
            ->editColumn('is_active', function (RawMaterials $rawMaterial) {
                return $rawMaterial->is_active
                    ? '<span class="badge badge-pill badge-success">Aktif</span>'
                    : '<span class="badge badge-pill badge-secondary">Tidak Aktif</span>';
            })
            ->editColumn('notes', function (RawMaterials $rawMaterial) {
                return $rawMaterial->notes
                    ? e(Str::limit($rawMaterial->notes, 70))
                    : '<span class="text-muted">-</span>';
            })
            ->editColumn('updated_at', function (RawMaterials $rawMaterial) {
                return $rawMaterial->updated_at ? $rawMaterial->updated_at->format('d M Y H:i') : '-';
            })
            ->filterColumn('category_name', function (QueryBuilder $query, $keyword) {
                if ($keyword === '__empty__') {
                    $query->whereNull('raw_material_category_id');
                    return;
                }

                $query->whereHas('category', fn ($category) => $category->where('name', 'like', "%{$keyword}%"));
            })
            ->filterColumn('base_unit_name', function (QueryBuilder $query, $keyword) {
                $query->whereHas('baseUnit', fn ($unit) => $unit
                    ->where('name', 'like', "%{$keyword}%")
                    ->orWhere('symbol', 'like', "%{$keyword}%"));
            })
            ->filterColumn('storage_type_name', function (QueryBuilder $query, $keyword) {
                $query->whereHas('storageType', fn ($storageType) => $storageType->where('name', 'like', "%{$keyword}%"));
            })
            ->addIndexColumn()
            ->rawColumns(['action', 'code', 'category_name', 'base_unit_name', 'storage_type_name', 'is_active', 'notes']);
    }

    public function query(RawMaterials $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['category', 'baseUnit', 'storageType']);
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('raw-materials-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->responsive(true)
            ->orderBy(2);
    }

    public function getColumns(): array
    {
        return [
            Column::make('DT_RowIndex')->title('No')->searchable(false)->orderable(false)->width(48),
            Column::make('code')->title('Kode'),
            Column::make('name')->title('Nama Bahan'),
            Column::make('category_name')->title('Kategori')->orderable(false),
            Column::make('base_unit_name')->title('Satuan Dasar')->orderable(false),
            Column::make('storage_type_name')->title('Penyimpanan')->orderable(false),
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
        return 'Bahan_Baku_' . date('YmdHis');
    }
}

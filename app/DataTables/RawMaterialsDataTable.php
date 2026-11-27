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
            // Kolom bahan baku menggabungkan nama + kode + catatan, jadi pencarian mencakup ketiganya
            ->editColumn('name', function (RawMaterials $rawMaterial) {
                $code = $rawMaterial->code
                    ? '<span class="ob-cell-meta">' . e($rawMaterial->code) . '</span>'
                    : '<span class="ob-cell-meta">Tanpa kode</span>';
                $notes = $rawMaterial->notes
                    ? '<span class="ob-cell-meta bb-notes" title="' . e($rawMaterial->notes) . '">' . e(Str::limit($rawMaterial->notes, 80)) . '</span>'
                    : '';

                return '<div class="ob-cell-stack">'
                    . '<span class="ob-item-cell__title">' . e($rawMaterial->name) . '</span>'
                    . $code
                    . $notes
                    . '</div>';
            })
            ->filterColumn('name', fn (QueryBuilder $query, string $keyword) => $query->where(
                fn (QueryBuilder $query) => $query->where('raw_materials.name', 'like', "%{$keyword}%")
                    ->orWhere('raw_materials.code', 'like', "%{$keyword}%")
                    ->orWhere('raw_materials.notes', 'like', "%{$keyword}%")
            ))
            ->addColumn('category_name', fn (RawMaterials $rawMaterial) => $rawMaterial->category
                ? '<span class="bb-strong">' . e($rawMaterial->category->name) . '</span>'
                : '<span class="ob-cell-meta">Tanpa kategori</span>')
            ->addColumn('base_unit_name', function (RawMaterials $rawMaterial) {
                $label = optional($rawMaterial->baseUnit)->symbol ?: optional($rawMaterial->baseUnit)->name;

                return $label
                    ? '<span class="badge badge-primary">' . e($label) . '</span>'
                    : '<span class="ob-cell-meta">-</span>';
            })
            ->addColumn('storage_type_name', fn (RawMaterials $rawMaterial) => $rawMaterial->storageType
                ? e($rawMaterial->storageType->name)
                : '<span class="ob-cell-meta">-</span>')
            ->editColumn('is_active', fn (RawMaterials $rawMaterial) => $rawMaterial->is_active
                ? '<span class="badge badge-success">Aktif</span>'
                : '<span class="badge badge-muted">Nonaktif</span>')
            ->editColumn('updated_at', fn (RawMaterials $rawMaterial) => $rawMaterial->updated_at
                ? '<span class="ob-cell-meta">' . e($rawMaterial->updated_at->translatedFormat('d M Y, H:i')) . '</span>'
                : '<span class="ob-cell-meta">-</span>')
            ->rawColumns(['action', 'name', 'category_name', 'base_unit_name', 'storage_type_name', 'is_active', 'updated_at'])
            ->setRowId('id');
    }

    public function query(RawMaterials $model): QueryBuilder
    {
        $query = $model->newQuery()->with(['category', 'baseUnit', 'storageType']);

        // Filter dikirim lewat parameter ajax (bukan pencarian kolom) agar bisa dikombinasikan
        $category = $this->request()->get('category');
        if ($category === 'none') {
            $query->whereNull('raw_material_category_id');
        } elseif (ctype_digit((string) $category)) {
            $query->where('raw_material_category_id', (int) $category);
        }

        $storage = $this->request()->get('storage');
        if (ctype_digit((string) $storage)) {
            $query->where('storage_type_id', (int) $storage);
        }

        $status = $this->request()->get('status');
        if ($status === '0' || $status === '1') {
            $query->where('is_active', (int) $status);
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('raw-materials-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'category' => "$('#filter-category').val()",
                'storage' => "$('#filter-storage').val()",
                'status' => "$('#filter-status').val()",
            ])
            // Hanya tabel yang scroll horizontal, length/search/paging tetap di tempat.
            ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
            ->orderBy(0, 'asc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('name')->title('Bahan Baku'),
            Column::make('category_name')->title('Kategori')->orderable(false)->searchable(false),
            Column::make('base_unit_name')->title('Satuan Dasar')->orderable(false)->searchable(false),
            Column::make('storage_type_name')->title('Penyimpanan')->orderable(false)->searchable(false),
            Column::make('is_active')->title('Status')->searchable(false),
            Column::make('updated_at')->title('Diperbarui')->searchable(false),
            Column::computed('action')
                ->title('')
                ->exportable(false)
                ->printable(false)
                ->width(110)
                ->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'Bahan_Baku_' . date('YmdHis');
    }
}

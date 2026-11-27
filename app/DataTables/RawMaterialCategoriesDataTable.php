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
            // Kolom kategori menggabungkan nama + catatan, jadi pencarian mencakup keduanya
            ->editColumn('name', function (RawMaterialCategories $category) {
                $notes = $category->notes
                    ? '<span class="ob-cell-meta kb-notes" title="' . e($category->notes) . '">' . e(Str::limit($category->notes, 80)) . '</span>'
                    : '';

                return '<div class="ob-cell-stack">'
                    . '<span class="ob-item-cell__title">' . e($category->name) . '</span>'
                    . $notes
                    . '</div>';
            })
            ->filterColumn('name', fn (QueryBuilder $query, string $keyword) => $query->where(
                fn (QueryBuilder $query) => $query->where('raw_material_categories.name', 'like', "%{$keyword}%")
                    ->orWhere('raw_material_categories.notes', 'like', "%{$keyword}%")
            ))
            ->editColumn('raw_materials_count', function (RawMaterialCategories $category) {
                return $category->raw_materials_count > 0
                    ? '<span class="kb-strong">' . number_format($category->raw_materials_count, 0, ',', '.') . ' bahan baku</span>'
                    : '<span class="ob-cell-meta">Belum dipakai</span>';
            })
            ->editColumn('is_active', fn (RawMaterialCategories $category) => $category->is_active
                ? '<span class="badge badge-success">Aktif</span>'
                : '<span class="badge badge-muted">Nonaktif</span>')
            ->editColumn('updated_at', fn (RawMaterialCategories $category) => $category->updated_at
                ? '<span class="ob-cell-meta">' . e($category->updated_at->translatedFormat('d M Y, H:i')) . '</span>'
                : '<span class="ob-cell-meta">-</span>')
            ->rawColumns(['action', 'name', 'raw_materials_count', 'is_active', 'updated_at'])
            ->setRowId('id');
    }

    public function query(RawMaterialCategories $model): QueryBuilder
    {
        // Hanya bahan baku yang belum dihapus (soft delete) yang dihitung
        $query = $model->newQuery()->withCount('rawMaterials');

        $status = $this->request()->get('status');
        if ($status === '0' || $status === '1') {
            $query->where('is_active', (int) $status);
        }

        $usage = $this->request()->get('usage');
        if ($usage === 'used') {
            $query->has('rawMaterials');
        } elseif ($usage === 'empty') {
            $query->doesntHave('rawMaterials');
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('raw-material-categories-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'status' => "$('#filter-status').val()",
                'usage' => "$('#filter-usage').val()",
            ])
            // Hanya tabel yang scroll horizontal, length/search/paging tetap di tempat.
            ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
            ->orderBy(0, 'asc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('name')->title('Kategori'),
            Column::make('raw_materials_count')->title('Jumlah Bahan')->searchable(false),
            Column::make('is_active')->title('Status')->searchable(false),
            Column::make('updated_at')->title('Diperbarui')->searchable(false),
            Column::computed('action')
                ->title('')
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

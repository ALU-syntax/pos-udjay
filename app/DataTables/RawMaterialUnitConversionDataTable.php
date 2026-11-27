<?php

namespace App\DataTables;

use App\Models\RawMaterialUnitConversions;
use App\Models\Satuan;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/**
 * Tabel "Konversi per Bahan Baku" di halaman Library / Satuan.
 * Datanya diambil dari route library/satuan/conversions, bukan dari halaman index.
 */
class RawMaterialUnitConversionDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function (RawMaterialUnitConversions $conversion) {
                return view('layouts.satuan.conversion-action', compact('conversion'))->render();
            })
            // Nama bahan baku diambil dari join agar kolom ini bisa diurutkan dan dicari
            ->editColumn('raw_material_name', function (RawMaterialUnitConversions $conversion) {
                $baseUnit = $this->unitLabel($conversion->rawMaterial?->baseUnit);
                $meta = $conversion->rawMaterial?->trashed()
                    ? '<span class="badge badge-muted">Bahan baku dihapus</span>'
                    : '<span class="ob-cell-meta">Satuan dasar: ' . e($baseUnit) . '</span>';

                return '<div class="ob-cell-stack">'
                    . '<span class="ob-item-cell__title">' . e($conversion->raw_material_name ?? '-') . '</span>'
                    . $meta
                    . '</div>';
            })
            ->addColumn('formula', function (RawMaterialUnitConversions $conversion) {
                return '<div class="ob-cell-stack">'
                    . '<span class="sat-formula">1 ' . e($this->unitLabel($conversion->fromUnit))
                    . ' = ' . e($this->formatMultiplier($conversion->multiplier))
                    . ' ' . e($this->unitLabel($conversion->toUnit)) . '</span>'
                    . '<span class="ob-cell-meta">' . e($conversion->fromUnit?->name ?? '-')
                    . ' <i class="fas fa-long-arrow-alt-right" aria-hidden="true"></i> '
                    . e($conversion->toUnit?->name ?? '-') . '</span>'
                    . '</div>';
            })
            ->filterColumn('formula', function (QueryBuilder $query, string $keyword) {
                $matchUnit = fn (QueryBuilder $unit) => $unit->where('name', 'like', "%{$keyword}%")
                    ->orWhere('symbol', 'like', "%{$keyword}%");

                $query->where(fn (QueryBuilder $query) => $query->whereHas('fromUnit', $matchUnit)
                    ->orWhereHas('toUnit', $matchUnit));
            })
            ->editColumn('notes', function (RawMaterialUnitConversions $conversion) {
                if (blank($conversion->notes)) {
                    return '<span class="ob-cell-meta">-</span>';
                }

                return '<span class="sat-notes" title="' . e($conversion->notes) . '">' . e($conversion->notes) . '</span>';
            })
            ->editColumn('updated_at', fn (RawMaterialUnitConversions $conversion) => $conversion->updated_at
                ? '<span class="ob-cell-meta">' . e($conversion->updated_at->translatedFormat('d M Y, H:i')) . '</span>'
                : '<span class="ob-cell-meta">-</span>')
            ->rawColumns(['action', 'raw_material_name', 'formula', 'notes', 'updated_at'])
            ->setRowId('id');
    }

    public function query(RawMaterialUnitConversions $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->select('raw_material_unit_conversions.*', 'rm.name as raw_material_name')
            ->leftJoin('raw_materials as rm', 'rm.id', '=', 'raw_material_unit_conversions.raw_material_id')
            ->with([
                // Bahan baku yang sudah dihapus tetap ditampilkan dengan penanda
                'rawMaterial' => fn ($rawMaterial) => $rawMaterial->withTrashed()->with('baseUnit'),
                'fromUnit',
                'toUnit',
            ]);

        $rawMaterialId = (int) $this->request()->get('raw_material');
        if ($rawMaterialId > 0) {
            $query->where('raw_material_unit_conversions.raw_material_id', $rawMaterialId);
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('conversion-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax(route('library/satuan/conversions'), null, [
                        'raw_material' => "$('#filter-material').val()",
                    ])
                    // Hanya tabel yang scroll horizontal, length/search/paging tetap di tempat.
                    ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
                    ->orderBy(0, 'asc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('raw_material_name')->name('rm.name')->title('Bahan Baku'),
            Column::make('formula')->title('Formula Konversi')->orderable(false),
            Column::make('notes')->name('raw_material_unit_conversions.notes')->title('Catatan')->orderable(false),
            Column::make('updated_at')->name('raw_material_unit_conversions.updated_at')->title('Diperbarui')->searchable(false),
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
        return 'Konversi_Satuan_' . date('YmdHis');
    }

    private function unitLabel(?Satuan $unit): string
    {
        return $unit?->symbol ?: $unit?->name ?: '-';
    }

    /** 1000.000000 → "1.000", 0.500000 → "0,5" */
    private function formatMultiplier($multiplier): string
    {
        return rtrim(rtrim(number_format((float) $multiplier, 6, ',', '.'), '0'), ',');
    }
}

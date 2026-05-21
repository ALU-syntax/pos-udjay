<?php

namespace App\DataTables;

use App\Models\Inventory;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class InventoryDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function (Inventory $inventory) {
                return view('layouts.inventory.action', compact('inventory'))->render();
            })
            ->editColumn('code', function (Inventory $inventory) {
                return $inventory->code
                    ? '<span class="badge bg-light text-dark border">' . e($inventory->code) . '</span>'
                    : '<span class="text-muted">-</span>';
            })
            ->addColumn('type_name', function (Inventory $inventory) {
                return $inventory->type
                    ? '<span class="inv-tag inv-tag-type">' . e($inventory->type->name) . '</span>'
                    : '<span class="text-muted">-</span>';
            })
            ->addColumn('parent_name', function (Inventory $inventory) {
                return $inventory->parent
                    ? e($inventory->parent->name)
                    : '<span class="text-muted">-</span>';
            })
            ->addColumn('outlet_name', function (Inventory $inventory) {
                return $inventory->outlet
                    ? e($inventory->outlet->name)
                    : '<span class="text-muted">Semua outlet</span>';
            })
            ->addColumn('brand_name', function (Inventory $inventory) {
                return $inventory->brand
                    ? e($inventory->brand->name)
                    : '<span class="text-muted">Semua brand</span>';
            })
            ->addColumn('stock_summary', function (Inventory $inventory) {
                $available = (float) ($inventory->qty_available_sum ?? 0);
                $reserved = (float) ($inventory->qty_reserved_sum ?? 0);
                $materials = (int) ($inventory->stock_balances_count ?? 0);
                $free = $available - $reserved;

                return '<div class="inv-stock-meta">'
                    . '<div><strong>' . $materials . '</strong> bahan</div>'
                    . '<small>Ready ' . number_format($free, 2, ',', '.') . '</small>'
                    . '</div>';
            })
            ->editColumn('is_active', function (Inventory $inventory) {
                return $inventory->is_active
                    ? '<span class="badge badge-pill badge-success">Aktif</span><span class="d-none">1</span>'
                    : '<span class="badge badge-pill badge-secondary">Tidak Aktif</span><span class="d-none">0</span>';
            })
            ->editColumn('updated_at', function (Inventory $inventory) {
                return $inventory->updated_at ? $inventory->updated_at->format('d M Y H:i') : '-';
            })
            ->filterColumn('type_name', function (QueryBuilder $query, $keyword) {
                $query->whereHas('type', fn ($type) => $type->where('name', 'like', "%{$keyword}%"));
            })
            ->filterColumn('parent_name', function (QueryBuilder $query, $keyword) {
                if ($keyword === '__empty__') {
                    $query->whereNull('parent_id');
                    return;
                }

                $query->whereHas('parent', fn ($parent) => $parent->where('name', 'like', "%{$keyword}%"));
            })
            ->filterColumn('outlet_name', function (QueryBuilder $query, $keyword) {
                if ($keyword === '__empty__') {
                    $query->whereNull('outlet_id');
                    return;
                }

                $query->whereHas('outlet', fn ($outlet) => $outlet->where('name', 'like', "%{$keyword}%"));
            })
            ->filterColumn('brand_name', function (QueryBuilder $query, $keyword) {
                if ($keyword === '__empty__') {
                    $query->whereNull('brand_id');
                    return;
                }

                $query->whereHas('brand', fn ($brand) => $brand->where('name', 'like', "%{$keyword}%"));
            })
            ->addIndexColumn()
            ->rawColumns([
                'action',
                'code',
                'type_name',
                'parent_name',
                'outlet_name',
                'brand_name',
                'stock_summary',
                'is_active',
            ]);
    }

    public function query(Inventory $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['type', 'parent', 'outlet', 'brand'])
            ->withCount('stockBalances')
            ->withSum('stockBalances as qty_available_sum', 'qty_available')
            ->withSum('stockBalances as qty_reserved_sum', 'qty_reserved');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('inventory-locations-table')
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
            Column::make('name')->title('Nama Lokasi'),
            Column::make('type_name')->title('Tipe')->orderable(false),
            Column::make('parent_name')->title('Parent')->orderable(false),
            Column::make('outlet_name')->title('Outlet')->orderable(false),
            Column::make('brand_name')->title('Brand')->orderable(false),
            Column::make('stock_summary')->title('Ringkasan Stok')->searchable(false)->orderable(false),
            Column::make('is_active')->title('Status'),
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
        return 'Inventory_' . date('YmdHis');
    }
}

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
            // Kolom lokasi menggabungkan nama + kode + parent, jadi pencarian mencakup nama & kode
            ->editColumn('name', function (Inventory $inventory) {
                $code = $inventory->code
                    ? '<span class="ob-cell-meta">' . e($inventory->code) . '</span>'
                    : '<span class="ob-cell-meta">Tanpa kode</span>';
                $parent = $inventory->parent
                    ? '<span class="ob-cell-meta"><i class="fas fa-level-up-alt fa-rotate-90" aria-hidden="true"></i> Bagian dari: ' . e($inventory->parent->name) . '</span>'
                    : '';

                return '<div class="ob-cell-stack">'
                    . '<span class="ob-item-cell__title">' . e($inventory->name) . '</span>'
                    . $code
                    . $parent
                    . '</div>';
            })
            ->filterColumn('name', fn (QueryBuilder $query, string $keyword) => $query->where(
                fn (QueryBuilder $query) => $query->where('inventory.name', 'like', "%{$keyword}%")
                    ->orWhere('inventory.code', 'like', "%{$keyword}%")
            ))
            ->addColumn('type_name', fn (Inventory $inventory) => $inventory->type
                ? '<span class="badge badge-primary">' . e($inventory->type->name) . '</span>'
                : '<span class="ob-cell-meta">-</span>')
            ->addColumn('scope', fn (Inventory $inventory) => '<div class="ob-cell-stack">'
                . ($inventory->outlet
                    ? '<span class="inv-strong">' . e($inventory->outlet->name) . '</span>'
                    : '<span class="ob-cell-meta">Semua outlet</span>')
                . '<span class="ob-cell-meta">' . ($inventory->brand ? e($inventory->brand->name) : 'Semua brand') . '</span>'
                . '</div>')
            // Hitungan bahan, bukan jumlah qty: tiap bahan punya satuan berbeda sehingga qty tidak bisa dijumlahkan
            ->addColumn('stock_summary', function (Inventory $inventory) {
                $materials = (int) $inventory->stock_balances_count;
                $empty = (int) $inventory->empty_stock_count;

                if ($materials === 0) {
                    return '<span class="ob-cell-meta">Belum ada stok</span>';
                }

                return '<span class="inv-strong">' . number_format($materials, 0, ',', '.') . ' bahan</span>'
                    . ($empty > 0
                        ? ' <span class="inv-empty-count">· ' . number_format($empty, 0, ',', '.') . ' habis</span>'
                        : '');
            })
            ->editColumn('is_active', fn (Inventory $inventory) => $inventory->is_active
                ? '<span class="badge badge-success">Aktif</span>'
                : '<span class="badge badge-muted">Nonaktif</span>')
            ->editColumn('updated_at', fn (Inventory $inventory) => $inventory->updated_at
                ? '<span class="ob-cell-meta">' . e($inventory->updated_at->translatedFormat('d M Y, H:i')) . '</span>'
                : '<span class="ob-cell-meta">-</span>')
            ->rawColumns(['action', 'name', 'type_name', 'scope', 'stock_summary', 'is_active', 'updated_at'])
            ->setRowId('id');
    }

    public function query(Inventory $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['type', 'parent', 'outlet', 'brand'])
            ->withCount([
                'stockBalances',
                'stockBalances as empty_stock_count' => fn ($query) => $query->where('qty_available', '<=', 0),
            ]);

        // Filter dikirim lewat parameter ajax (bukan pencarian kolom) agar bisa dikombinasikan
        $type = $this->request()->get('type');
        if (ctype_digit((string) $type)) {
            $query->where('inventory_type_id', (int) $type);
        }

        foreach (['outlet' => 'outlet_id', 'brand' => 'brand_id'] as $param => $column) {
            $value = $this->request()->get($param);
            if ($value === 'all') {
                $query->whereNull($column);
            } elseif (ctype_digit((string) $value)) {
                $query->where($column, (int) $value);
            }
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
            ->setTableId('inventory-locations-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'type' => "$('#filter-type').val()",
                'outlet' => "$('#filter-outlet').val()",
                'brand' => "$('#filter-brand').val()",
                'status' => "$('#filter-status').val()",
            ])
            // Hanya tabel yang scroll horizontal, length/search/paging tetap di tempat.
            ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
            ->orderBy(0, 'asc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('name')->title('Lokasi'),
            Column::make('type_name')->title('Tipe')->orderable(false)->searchable(false),
            Column::make('scope')->title('Outlet / Brand')->orderable(false)->searchable(false),
            Column::make('stock_summary')->title('Isi Stok')->orderable(false)->searchable(false),
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
        return 'Inventory_' . date('YmdHis');
    }
}

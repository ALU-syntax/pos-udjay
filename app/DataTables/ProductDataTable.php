<?php

namespace App\DataTables;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ProductDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $canUpdate = auth()->user()->can('update library/product');
        $canDelete = auth()->user()->can('delete library/product');

        return (new EloquentDataTable($query))
            ->editColumn('name', fn (Product $row) => $this->productCell($row))
            ->editColumn('category_id', fn (Product $row) => $row->category ? e($row->category->name) : '<span class="text-muted">-</span>')
            ->addColumn('harga_jual', fn (Product $row) => $this->priceCell($row))
            ->editColumn('harga_modal', fn (Product $row) => '<span class="ob-cell-stack">'.$this->rupiah($row->harga_modal).'</span>')
            ->editColumn('outlet_id', fn (Product $row) => $row->outlet
                ? '<span class="badge badge-primary rounded-pill">'.e($row->outlet->name).'</span>'
                : '<span class="text-muted">-</span>')
            ->editColumn('status', fn (Product $row) => $row->status
                ? '<span class="badge badge-success">Aktif</span>'
                : '<span class="badge badge-muted">Nonaktif</span>')
            ->editColumn('show_in_order_table', function (Product $row) use ($canUpdate) {
                return view('components.order-table-switch', [
                    'checked' => (bool) $row->show_in_order_table,
                    'url' => route('library/product/order-table', $row->id),
                    'canUpdate' => $canUpdate,
                    // Switch hidup tidak berarti tampil bila kategorinya disembunyikan.
                    'note' => $row->category && ! $row->category->show_in_order_table ? 'Kategori disembunyikan' : null,
                ])->render();
            })
            ->addColumn('action', fn (Product $row) => view('layouts.product.action', [
                'product' => $row,
                'canUpdate' => $canUpdate,
                'canDelete' => $canDelete,
            ])->render())
            ->filterColumn('category_id', fn (QueryBuilder $query, string $keyword) => $query
                ->whereHas('category', fn (QueryBuilder $category) => $category->where('name', 'like', "%{$keyword}%")))
            ->rawColumns(['name', 'category_id', 'harga_jual', 'harga_modal', 'outlet_id', 'status', 'show_in_order_table', 'action'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Product $model): QueryBuilder
    {
        $outletIds = auth()->user()->outletIds();
        $query = $model->newQuery()
            ->with(['outlet', 'category', 'variants'])
            ->whereIn('outlet_id', $outletIds);

        $outlet = $this->request()->get('outlet');
        if ($outlet !== null && $outlet !== '' && $outlet !== 'all' && in_array((int) $outlet, $outletIds, true)) {
            $query->where('outlet_id', (int) $outlet);
        }

        $status = $this->request()->get('status');
        if ($status === '0' || $status === '1') {
            $query->where('status', (int) $status);
        }

        $orderTable = $this->request()->get('order_table');
        if ($orderTable === '0' || $orderTable === '1') {
            $query->where('show_in_order_table', (int) $orderTable);
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('product-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'outlet' => "$('#filter-outlet').val()",
                'status' => "$('#filter-status').val()",
                'order_table' => "$('#filter-order-table').val()",
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
            Column::make('name')->title('Produk'),
            Column::make('category_id')->title('Kategori'),
            Column::computed('harga_jual')->title('Harga Jual'),
            Column::make('harga_modal')->title('Harga Modal')->searchable(false),
            Column::make('outlet_id')->title('Outlet')->searchable(false),
            Column::make('status')->title('Status')->searchable(false),
            Column::make('show_in_order_table')->title('Order Table')->searchable(false)->exportable(false)->printable(false),
            Column::computed('action')->title('')->exportable(false)->printable(false)->width(90)->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Product_'.date('YmdHis');
    }

    private function productCell(Product $row): string
    {
        $path = $row->photo_thumb ?: $row->photo;
        $thumb = $path && file_exists(public_path($path))
            ? '<img src="'.e(asset($path)).'" alt="" class="ob-item-thumb" loading="lazy">'
            : '<span class="ob-item-thumb" aria-hidden="true"><i class="fas fa-mug-hot"></i></span>';
        $description = $row->description
            ? '<div class="ob-item-cell__meta">'.e($row->description).'</div>'
            : '';

        return '<div class="ob-item-cell">'.$thumb.'<div>'
            .'<div class="ob-item-cell__title">'.e($row->name).'</div>'
            .$description
            .'</div></div>';
    }

    private function priceCell(Product $row): string
    {
        $prices = $row->variants->pluck('harga')->map(fn ($price) => (int) $price);
        if ($prices->isEmpty()) {
            return '<span class="text-muted">-</span>';
        }
        if ($prices->count() === 1) {
            return '<span class="ob-cell-stack">'.$this->rupiah($prices->first()).'</span>';
        }

        $range = $prices->min() === $prices->max()
            ? $this->rupiah($prices->min())
            : $this->rupiah($prices->min()).' – '.$this->rupiah($prices->max());

        return '<div class="ob-cell-stack"><span>'.$range.'</span>'
            .'<span class="ob-cell-meta">'.$prices->count().' varian</span></div>';
    }

    private function rupiah(int|float|string|null $amount): string
    {
        return 'Rp '.number_format((int) $amount, 0, ',', '.');
    }
}

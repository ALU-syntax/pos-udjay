<?php

namespace App\DataTables;

use App\Models\ModifierGroup;
use App\Models\PilihProduct;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class PilihProductDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        // Ambil data 'modifierGroup' dari request
        $modifierGroup = $this->modifierGroup;

        $productIds = json_decode($modifierGroup->product_id, true);

        // Ambil data checked dari request
        $checkedProducts = $this->request->get('checkedProducts', []);

        $dataClosure = ['productId' => $productIds, 'checkedProduct' => $checkedProducts];

        return (new EloquentDataTable($query))
            ->addColumn('checkbox', function ($row) use ($dataClosure) {
                if (!empty($dataClosure['checkedProduct'])) {
                    $checked = in_array($row->id, $dataClosure['checkedProduct']) ? 'checked' : '';
                } else if ($dataClosure['productId'] != null) {
                    $checked = in_array($row->id, $dataClosure['productId']) ? 'checked' : '';
                } else {
                    $checked = '';
                }
                return '<input type="checkbox" class="product-checkbox" name="products[]" value="' . $row->id . '" ' . $checked . '>';
            })
            ->addColumn('price', function ($row) {
                return formatRupiah(intval($row->harga_jual), "Rp. ");
            })
            ->rawColumns(['checkbox']);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Product $model): QueryBuilder
    {
        // Ambil data 'modifierGroup' dari request
        $modifierGroup = $this->modifierGroup;

        // Ambil data dari request
        $checkedProducts = $this->request->get('checkedProducts', []);

        $productIds = $modifierGroup ? ($modifierGroup->product_id != null ? json_decode($modifierGroup->product_id, true) : []) : [];

        // Query dasar
        $query = $model->newQuery()
            ->select('id', 'name', 'harga_jual');

        if (!empty($checkedProducts)) {
            // Jika ada produk yang dicentang, urutkan berdasarkan `checkedProducts`
            $query->orderByRaw("FIELD(id, " . implode(',', $checkedProducts) . ") DESC");
        } elseif (!empty($productIds)) {
            // Jika tidak ada `checkedProducts`, urutkan berdasarkan `productIds` dari modifierGroup
            $query->orderByRaw("FIELD(id, " . implode(',', $productIds) . ") DESC");
        }

        // Tambahkan order by name sebagai default
        $query->orderBy('name', 'asc');

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('pilihproduct-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            //->dom('Bfrtip')
            ->orderBy(1)
            ->selectStyleSingle()
            ->buttons([
                Button::make('excel'),
                Button::make('csv'),
                Button::make('pdf'),
                Button::make('print'),
                Button::make('reset'),
                Button::make('reload')
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            // ['data' => 'checkbox', 'name' => 'checkbox', 'orderable' => false, 'searchable' => false, 'title' => '<input type="checkbox" id="checkAll">'],
            ['data' => 'checkbox', 'name' => 'checkbox', 'orderable' => false, 'searchable' => false, 'title' => ''],
            ['data' => 'name', 'name' => 'name', 'title' => 'Nama Produk'],
            ['data' => 'price', 'name' => 'price', 'title' => 'Harga', 'orderable' => false, 'searchable' => false],
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'PilihProduct_' . date('YmdHis');
    }
}

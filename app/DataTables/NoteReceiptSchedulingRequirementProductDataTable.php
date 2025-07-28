<?php

namespace App\DataTables;

use App\Models\NoteReceiptScheduling;
use App\Models\NoteReceiptSchedulingRequirementProduct;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class NoteReceiptSchedulingRequirementProductDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        // Ambil data 'noteReceiptScheduling' dari request
        $noteReceiptScheduling = $this->noteReceiptScheduling;

        $productIds = json_decode($noteReceiptScheduling->product_id, true);

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
            ->filterColumn('category', function ($query, $keyword) {
                $query->whereRaw("LOWER(categories.name) REGEXP ?", [strtolower($keyword)]);
            })
            ->editColumn('category', function ($row) {
                return $row->category;
            })
            ->rawColumns(['checkbox']);
    }

    /**
     * Get the query source of dataTable.
     */
    // public function query(Product $model): QueryBuilder
    // {
    //     // Ambil data 'noteReceiptScheduling' dari request
    //     $noteReceiptScheduling = $this->noteReceiptScheduling;

    //     // Ambil data dari request
    //     $checkedProducts = $this->request->get('checkedProducts', []);

    //     $productIds = $noteReceiptScheduling ? ($noteReceiptScheduling->product_id != null ? json_decode($noteReceiptScheduling->product_id, true) : []) : [];

    //     // Query dasar
    //     $query = $model->newQuery()
    //         ->select('id', 'name', 'category_id')->with(['category'])->where('outlet_id', $noteReceiptScheduling->outlet_id);

    //     // $query = $model->newQuery()
    //     //     ->select('id', 'name');

    //     if (!empty($checkedProducts)) {
    //         // Jika ada produk yang dicentang, urutkan berdasarkan `checkedProducts`
    //         $query->orderByRaw("FIELD(id, " . implode(',', $checkedProducts) . ") DESC");
    //     } elseif (!empty($productIds)) {
    //         // Jika tidak ada `checkedProducts`, urutkan berdasarkan `productIds` dari noteReceiptScheduling
    //         $query->orderByRaw("FIELD(id, " . implode(',', $productIds) . ") DESC");
    //     }

    //     // Tambahkan order by name sebagai default
    //     $query->orderBy('name', 'asc');

    //     return $query;
    // }
    public function query(Product $model): QueryBuilder
    {
        $noteReceiptScheduling = $this->noteReceiptScheduling;
        $checkedProducts = $this->request->get('checkedProducts', []);
        $productIds = $noteReceiptScheduling ? ($noteReceiptScheduling->product_id != null ? json_decode($noteReceiptScheduling->product_id, true) : []) : [];

        // Query join dengan categories, ambil kolom yang diperlukan
        $query = $model->newQuery()
            ->select('products.id', 'products.name', 'categories.name as category')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->where('products.outlet_id', $noteReceiptScheduling->outlet_id);


        if (!empty($checkedProducts)) {
            $query->orderByRaw("FIELD(products.id, " . implode(',', $checkedProducts) . ") DESC");
        } elseif (!empty($productIds)) {
            $query->orderByRaw("FIELD(products.id, " . implode(',', $productIds) . ") DESC");
        }

        $query->orderBy('products.name', 'asc');

        // dd($query);
        return $query;
    }


    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('notereceiptschedulingrequirementproduct-table')
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
            ['data' => 'checkbox', 'name' => 'checkbox', 'orderable' => false, 'searchable' => false, 'title' => '<input type="checkbox" id="checkAllRequirement">', 'exportable' => false, 'printable' => false, 'width' => '10px', 'class' => 'text-center'],
            ['data' => 'name', 'name' => 'name', 'title' => 'Nama Produk'],
            ['data' => 'category', 'name' => 'category', 'title' => 'Nama Kategori'],
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'NoteReceiptSchedulingRequirementProduct_' . date('YmdHis');
    }
}

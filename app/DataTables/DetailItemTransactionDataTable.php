<?php

namespace App\DataTables;

use App\Models\DetailItemTransaction;
use App\Models\TransactionItem;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class DetailItemTransactionDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('name', function($row){
                if($row->product && $row->variant){
                    return $row->product->name == $row->variant->name ? $row->product->name : $row->product->name . '-' . $row->variant->name;
                }else{
                    return "custom";
                }
            })
            ->addColumn('qty', function($row){
                return 1;
            })
            ->addColumn('harga', function($row){
                return $row->harga;
            })
            ->addColumn('total_harga', function($row){
                return $row->harga;
            })
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(TransactionItem $model): QueryBuilder
    {
        return $model->with(['variant', 'product'])->where('transaction_id', $this->transactionId)->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('detailitemtransaction-table')
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
            Column::make('name')->title("Nama Item"),
            Column::make('qty')->title("Quantity"),
            Column::make('harga')->title("Harga"),
            Column::make('total_harga')->title("Total"),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'DetailItemTransaction_' . date('YmdHis');
    }
}

<?php

namespace App\DataTables;

use App\Models\ListCustomerTransaction;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class ListCustomerTransactionDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('point', function($row){
                return floor($row->total/100);
            })
            ->addColumn('exp', function($row){
                return floor($row->total/100);
            })
            ->editColumn('created_at', function($row){
                return Carbon::parse($row->created_at)->format('d-m-Y H:i');
            })
            ->addColumn('action', function($row){
                return view('layouts.customer.action-transaction', [
                    'detail' => route('membership/customer/detailTransaction', $row->id),
                    'delete' => route('membership/customer/lepasTransaction', $row->id)
                ]);
            })
            ->addColumn("ordered_at", function($row) {
                return "<span class='badge badge-primary'>{$row->outlet->name} </span></br>";
            })
            ->rawColumns(['ordered_at'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Transaction $model): QueryBuilder
    {
        return $model->with(['customer'])->where('customer_id', $this->customerId)->orderBy('id', 'desc')->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('listcustomertransaction-table')
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
            Column::make('id')->title("Kode Transaksi"),
            Column::make('point'),
            Column::make('exp'),
            Column::make('created_at'),
            Column::make('ordered_at')->title("Outlet Transaksi"),
            Column::make('action'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'ListCustomerTransaction_' . date('YmdHis');
    }
}

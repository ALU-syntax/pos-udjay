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
            ->editColumn('receipt_number', function($row){
                $receiptNumber = $row->receipt_number ?: 'TRX-' . str_pad($row->id, 5, '0', STR_PAD_LEFT);

                return '<div class="transaction-code-cell">'
                    . '<span class="transaction-code">' . e($receiptNumber) . '</span>'
                    . '<span class="transaction-code-sub">#' . e($row->id) . '</span>'
                    . '</div>';
            })
            ->editColumn('total', function($row){
                return '<span class="transaction-total">' . formatRupiah(strval((int) $row->total), 'Rp. ') . '</span>';
            })
            ->addColumn('point', function($row){
                return '<span class="transaction-point-badge">+' . number_format(floor($row->total/100), 0, ',', '.') . '</span>';
            })
            ->addColumn('exp', function($row){
                return '<span class="transaction-exp-badge">+' . number_format(floor($row->total/100), 0, ',', '.') . '</span>';
            })
            ->editColumn('created_at', function($row){
                return '<div class="transaction-date-cell">'
                    . '<span>' . Carbon::parse($row->created_at)->format('d M Y') . '</span>'
                    . '<small>' . Carbon::parse($row->created_at)->format('H:i') . '</small>'
                    . '</div>';
            })
            ->addColumn('action', function($row){
                return view('layouts.customer.action-transaction', [
                    'detail' => route('membership/customer/detailTransaction', $row->id),
                    'delete' => route('membership/customer/lepasTransaction', $row->id)
                ]);
            })
            ->addColumn("ordered_at", function($row) {
                return "<span class='transaction-outlet-badge'>" . e($row->outlet?->name ?? 'Outlet tidak diketahui') . "</span>";
            })
            ->filterColumn('receipt_number', function ($query, $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('receipt_number', 'like', "%{$keyword}%")
                        ->orWhere('id', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('ordered_at', function ($query, $keyword) {
                $query->whereHas('outlet', function ($query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%");
                });
            })
            ->orderColumn('total', 'transactions.total $1')
            ->orderColumn('created_at', 'transactions.created_at $1')
            ->rawColumns(['receipt_number', 'total', 'point', 'exp', 'created_at', 'ordered_at', 'action'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Transaction $model): QueryBuilder
    {
        return $model->with(['customer', 'outlet'])->where('customer_id', $this->customerId)->newQuery();
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
                    ->orderBy(4, 'desc')
                    ->selectStyleSingle()
                    ->parameters([
                        'autoWidth' => false,
                    ])
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
            Column::make('receipt_number')->title("Kode Transaksi")->orderable(false),
            Column::make('total')->title("Nominal")->searchable(false)->orderable(true),
            Column::make('point')->title("Point")->searchable(false)->orderable(false),
            Column::make('exp')->title("EXP")->searchable(false)->orderable(false),
            Column::make('created_at')->title("Tanggal"),
            Column::make('ordered_at')->title("Outlet Transaksi")->orderable(false),
            Column::make('action')->searchable(false)->orderable(false),
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

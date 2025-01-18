<?php

namespace App\DataTables;

use App\Models\Pemasukan;
use App\Traits\DataTableHelper;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class PemasukanDataTable extends DataTable
{
    use DataTableHelper;
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
        ->addColumn('action', function ($row) {
            $actions = $this->basicActions($row);
            return view('action', ['actions' => $actions]);
        })
        ->addColumn('outlet_id', function($row){
            return "<span class='badge badge-primary'>{$row->outlet->name} </span></br>";
        })
        ->addColumn('kategori_pemasukan_id', function($row){
            return $row->kategori_pemasukan_id ? $row->kategoriPemasukan->name : '-';
        })
        ->addColumn('customer_id', function($row){
            return $row->customer_id ? $row->customer->name : '-';
        })
        ->addColumn('jumlah', function($row){
            return $row->jumlah ? formatRupiah(intval($row->jumlah), "Rp. ") : "-";
        })
        ->addColumn('tanggal', function($row){
            return $row->tanggal ? $row->tanggal : '-';
        })
        ->addColumn('catatan', function($row){
            return $row->catatan ? $row->catatan : '-';
        })
        ->addColumn('photo', function($row){
            if($row->photo != null && file_exists(public_path($row->photo))){
                return '<img src="' . asset($row->photo) . '" width="80" style="border-radius: 20%;">';
            }else{
                return '<img src="' . asset("img/img-placeholder.png") .'" width="80" style="border-radius: 20%;">';
            }
        })
        ->rawColumns(['photo', 'outlet_id'])
        ->addIndexColumn();
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Pemasukan $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('pemasukan-table')
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
            Column::make('DT_RowIndex')->title('No')->searchable(false)->orderable(false),
            Column::make('outlet_id'),
            Column::make('kategori_pemasukan_id'),
            Column::make('customer_id'),
            Column::make('jumlah'),
            Column::make('photo'),
            Column::make('tanggal'),
            Column::make('catatan'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->addClass('text-center')
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Pemasukan_' . date('YmdHis');
    }
}

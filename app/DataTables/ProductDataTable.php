<?php

namespace App\DataTables;

use App\Models\Product;
use App\Traits\DataTableHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class ProductDataTable extends DataTable
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
            ->addColumn('status',function($row){
                return view('components.badge', ['data' => $row]);
            })
            ->addColumn('created_at', function($row){
                return Carbon::parse($row->created_at)->diffForHumans();
            })
            ->addColumn('updated_at', function($row){
                return Carbon::parse($row->updated_at)->diffForHumans();
            })
            ->addColumn('category_id', function($row){
                return $row->category ? $row->category->name : '-';
            })
            ->addColumn('harga_jual', function($row){
                return formatRupiah(intval($row->harga_jual), "Rp. ");
            })  
            ->addColumn('harga_modal', function($row){
                return formatRupiah(intval($row->harga_modal), "Rp. ");
            })  
            ->addColumn('outlet_id', function($row){
                return "<span class='badge badge-primary'>{$row->outlet->name} </span></br>";
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
    public function query(Product $model): QueryBuilder
    {   
        $query = $model->newQuery()->with(['outlet']);
        if ($this->request()->has('outlet') && $this->request()->get('outlet') != '') {
            if($this->request()->get('outlet') == 'all'){
                $query->whereIn('outlet_id', json_decode(auth()->user()->outlet_id));
            }else{
                $query->where('outlet_id', $this->request()->get('outlet'));
            }
        } elseif($this->request()->has('outlet') && $this->request()->get('outlet') == ''){
            $query->whereIn('outlet_id', json_decode(auth()->user()->outlet_id));
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
            Column::make('name'),
            Column::make('category_id'),
            Column::make('status'),
            Column::make('photo'),
            // Column::make('harga_jual'),
            Column::make('harga_modal'),
            // Column::make('stock'),
            Column::make('outlet_id')->title('OUTLET'),
            Column::computed('action')
                  ->exportable(false)
                  ->printable(false)
                  ->width(60)
                  ->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Product_' . date('YmdHis');
    }
}

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
            ->editColumn('status',function($row){
                return view('components.badge', ['data' => $row]);
            })
            ->editColumn('description',function($row){
                return $row->description ? $row->description : '-';
            })
            ->editColumn('created_at', function($row){
                return Carbon::parse($row->created_at)->diffForHumans();
            })
            ->editColumn('updated_at', function($row){
                return Carbon::parse($row->updated_at)->diffForHumans();
            })
            ->editColumn('category_id', function($row){
                return $row->category ? $row->category->name : '-';
            })
            ->editColumn('harga_jual', function($row){
                if(count($row->variants) > 1){
                    return "<span class='badge badge-primary'>" . count($row->variants) ." Price</span></br>";
                }else{
                    return formatRupiah(intval($row->variants[0]->harga), "Rp. ");
                }
            })
            ->editColumn('harga_modal', function($row){
                return formatRupiah(intval($row->harga_modal), "Rp. ");
            })
            ->editColumn('outlet_id', function($row){
                return "<span class='badge badge-primary'>{$row->outlet->name} </span></br>";
            })
            ->editColumn('photo', function($row){
                if($row->photo != null && file_exists(public_path($row->photo))){
                    return '<img src="' . asset($row->photo) . '" width="80" style="border-radius: 20%;">';
                }else{
                    return '<img src="' . asset("img/img-placeholder.png") .'" width="80" style="border-radius: 20%;">';
                }
            })
            ->rawColumns(['photo', 'outlet_id', 'harga_jual'])
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
        } elseif($this->request()->has('outlet') && $this->request()->get('outlet') == 'all'){
            $query->whereIn('outlet_id', json_decode(auth()->user()->outlet_id));
        }else{
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
            Column::make('category_id')->title('CATEGORY'),
            Column::make('status'),
            Column::make('description'),
            Column::make('photo'),
            Column::make('harga_jual')->orderable(false),
            Column::make('harga_modal'),
            // Column::make('stock'),
            Column::make('outlet_id')->title('OUTLET')->orderable(false),
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

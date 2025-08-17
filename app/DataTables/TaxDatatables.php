<?php

namespace App\DataTables;

use App\Models\Outlets;
use App\Models\TaxDatatable;
use App\Models\Taxes;
use App\Traits\DataTableHelper;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class TaxDatatables extends DataTable
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
            ->editColumn('outlet_id',function($row){
                $outlet = Outlets::find($row->outlet_id);
                return $outlet->name;
            })
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Taxes $model): QueryBuilder
    {
        $query = $model->newQuery()->with(['outlets']);
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
            ->setTableId('taxdatatables-table')
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
            Column::make('amount'),
            Column::make('satuan'),
            Column::make('outlet_id'),
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
        return 'TaxDatatables_' . date('YmdHis');
    }
}

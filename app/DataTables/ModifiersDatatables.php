<?php

namespace App\DataTables;

use App\Models\ModifierGroup;
use App\Models\Modifiers;
use App\Models\ModifiersDatatable;
use App\Traits\DataTableHelper;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class ModifiersDatatables extends DataTable
{
    use DataTableHelper;
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return datatables()
            ->eloquent($query)
            ->editColumn('option_name', function($row){
                $modifiers = $row->modifier;
                $tag = '';
                foreach($modifiers as $modifier){
                    $tag .= "<span>{$modifier->name}, </span>";
                }

                $result = substr($tag , 0, -9);
                return $result;
            })
            ->addColumn('outlet', function($row){
                return "<span class='badge badge-primary'>{$row->outlet->name} </span></br>";
            })
            ->addColumn('action', function ($row) {
                return view('layouts.modifiers.action', [
                    'edit' => route('library/modifiers/edit', $row->id),
                    'aturProduk' =>route('library/modifiers/getProduct', $row->id),
                    'routeDelete' =>route('library/modifiers/destroy', $row->id)
                ]);
            })
            ->rawColumns(['option_name', 'outlet'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(ModifierGroup $model): QueryBuilder
    {
        $query = $model->newQuery()->with(['modifier', 'outlet']);
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
                    ->setTableId('modifiersdatatables-table')
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
            Column::make('option_name')->orderable(false),
            Column::make('outlet')->orderable(false),
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
        return 'ModifiersDatatables_' . date('YmdHis');
    }
}

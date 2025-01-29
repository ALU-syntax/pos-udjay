<?php

namespace App\DataTables;

use App\Models\Pilihan;
use App\Models\PilihanGroup;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class PilihanDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('option_name', function($row){
                $pilihans = $row->pilihans;
                $tag = '';
                foreach($pilihans as $pilihan){
                    $tag .= "<span>{$pilihan->name}, </span>";
                }

                $result = substr($tag , 0, -9);
                return $result;
            })
            ->addColumn('outlet', function($row){
                return "<span class='badge badge-primary'>{$row->outlet->name} </span></br>";
            })
            ->addColumn('action', function ($row) {
                return view('layouts.pilihan.action', [
                    'edit' => route('library/pilihan/edit', $row->id),
                    'aturProduk' =>route('library/pilihan/getProduct', $row->id),
                    'routeDelete' =>route('library/pilihan/destroy', $row->id)
                ]);
            })
            ->rawColumns(['option_name', 'outlet'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(PilihanGroup $model): QueryBuilder
    {
        $query = $model->newQuery()->with(['pilihans', 'outlet']);
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
                    ->setTableId('pilihan-table')
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
            Column::make('option_name'),
            Column::make('outlet'),
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
        return 'Pilihan_' . date('YmdHis');
    }
}

<?php

namespace App\DataTables;

use App\Models\Promo;
use App\Models\PromoDatatable;
use App\Traits\DataTableHelper;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class PromoDatatables extends DataTable
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
            ->addColumn('time_periode', function ($row) {
                if ($row->promo_date_periode_start != null) {
                    return $row->promo_date_periode_start . ' - ' . $row->promo_date_periode_end;
                } else {
                    return ' - ';
                }
            })
            ->addColumn('outlet_id', function ($row) {
                return "<span class='badge badge-primary'>{$row->outlet->name} </span></br>";
            })
            ->addColumn('status', function ($row) {
                if ($row->status) {
                    return "<span class='badge badge-success'>Aktif</span></br>";
                } else {
                    return "<span class='badge badge-info'>Belum Aktif </span></br>";
                }
            })
            ->rawColumns(['outlet_id', 'status'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Promo $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('promodatatables-table')
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
            Column::make('name')->title('Promo Name'),
            Column::make('time_periode'),
            Column::make('outlet_id'),
            Column::make('status'),
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
        return 'PromoDatatables_' . date('YmdHis');
    }
}

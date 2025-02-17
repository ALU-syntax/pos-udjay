<?php

namespace App\DataTables;

use App\Models\Community;
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

class CommunityDataTable extends DataTable
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
            ->addColumn('status', function ($row) {
                return view('components.badge', ['data' => $row]);
            })
            ->addColumn('name', function ($row) {
                return "<a href=" . route('membership/community/detail', $row->id) . " type='button'>". $row->name ."</a>";
            })
            ->addColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->diffForHumans();
            })
            ->addColumn('total_anggota', function ($row) {
                return count($row->customers);
            })
            ->rawColumns(['name']);
            // ->addIndexColumn();
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Community $model): QueryBuilder
    {
        return $model->with(['customers'])->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('community-table')
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
            // Column::make('DT_RowIndex')->title('No')->searchable(false)->orderable(false),
            Column::make('name'),
            Column::make('status'),
            Column::make('exp'),
            Column::make('total_anggota'),
            Column::make('created_at'),
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
        return 'Community_' . date('YmdHis');
    }
}

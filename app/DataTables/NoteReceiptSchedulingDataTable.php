<?php

namespace App\DataTables;

use App\Models\NoteReceiptScheduling;
use App\Models\Outlets;
use App\Traits\DataTableHelper;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class NoteReceiptSchedulingDataTable extends DataTable
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
        ->addColumn('outlets', function ($row) {
            $outletIds = json_decode($row->list_outlet_id, true) ?: [];
            $listOutlet = "";
            foreach($outletIds as $outlet){
                $outlet = Outlets::find($outlet);
                $listOutlet = $listOutlet . "<span class='badge badge-primary'>{$outlet->name} </span></br>";
            }
            // Misal ambil nama outlet dari model Outlet, atau panggil relasi jika ada
            // Contoh sementara join gunakan Outlet::whereIn('id', $outletIds)->pluck('name')->join(', ');
            // Anda perlu sesuaikan dengan model Outlet Anda
            return $listOutlet;
        })
        ->addColumn('message_preview', function ($row) {
            return \Illuminate\Support\Str::limit($row->message, 50);
        })
        ->editColumn('status', function ($row) {
            return $row->status ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
        })
        ->rawColumns(['status', 'action', 'outlets']) // untuk render html di status dan action
        ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(NoteReceiptScheduling $model): QueryBuilder
    {
         return $model->newQuery()->select('id', 'name', 'message', 'start', 'end', 'list_outlet_id', 'status', 'created_at', 'updated_at');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('notereceiptscheduling-table')
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
        Column::make('id'),
        Column::make('name'),
        Column::computed('message_preview')->title('Message'),
        Column::make('start'),
        Column::make('end'),
        Column::computed('outlets')->title('Outlets'),
        Column::computed('status')->title('Status'),
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
        return 'NoteReceiptScheduling_' . date('YmdHis');
    }
}

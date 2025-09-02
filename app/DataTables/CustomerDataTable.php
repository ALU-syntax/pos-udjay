<?php

namespace App\DataTables;

use App\Models\Customer;
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

class CustomerDataTable extends DataTable
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
                return view('layouts.customer.action', [
                    'detail' => route('membership/customer/detailCustomer', $row->id),
                    'edit' =>route('membership/customer/edit', $row->id),
                    'routeDelete' =>route('membership/customer/destroy', $row->id),
                    'listReferee' =>route('membership/customer/listReferee', $row->id),
                ]);
            })
            ->editColumn('community_id', function($row){
                return $row->community_id ? $row->community->name : '-';
            })
            ->editColumn('level_memberships_id', function($row){
                return "<a class='action' href=" . route('membership/customer/checkRewardConfirmation', $row->id) . " type='button'>". $row->levelMembership->name ."</a>";
            })

            // ->addColumn('community_id', fn($row) => optional($row->community)->name ?? '-')
            // ->addColumn('level_membership_id', fn($row) => optional($row->levelMembership)->name ?? '-')

            // // Petakan order ke kolom tabel relasi
            // ->orderColumn('community_id', function ($q, $order) {
            //     $q->leftJoin('communities', 'communities.id', '=', 'customers.community_id')
            //     ->orderBy('communities.name', $order);
            // })
            // ->orderColumn('level_membership_id', function ($q, $order) {
            //     $q->leftJoin('level_memberships', 'level_memberships.id', '=', 'customers.level_memberships_id')
            //     ->orderBy('level_memberships.name', $order);
            // })

            ->editColumn('created_at', function($row){
                return Carbon::parse($row->created_at)->diffForHumans();
            })
            ->editColumn('name', function ($row) {
                return "<a href=" . route('membership/customer/detail', $row->id) . " type='button'>". $row->name ."</a>";
            })
            ->editColumn('point', function ($row) {
                return "<a class='action' href=" . route('membership/customer/historyPointUse', $row->id) . " type='button'>". $row->point ."</a>";
            })
            ->rawColumns(['created_at', 'name', 'level_memberships_id', 'point'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Customer $model): QueryBuilder
    {
        return $model->with(['community', 'levelMembership'])->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('customer-table')
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
            Column::make('telfon'),
            Column::make('point'),
            Column::make('exp'),
            Column::make('level_memberships_id')->title('Level Membership')->orderable(false),
            Column::make('created_at')->title("Created Date"),
            Column::make('community_id')->title("Community")->orderable(false),
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
        return 'Customer_' . date('YmdHis');
    }
}

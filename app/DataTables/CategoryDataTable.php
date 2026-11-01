<?php

namespace App\DataTables;

use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CategoryDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $canUpdate = auth()->user()->can('update library/category');

        return (new EloquentDataTable($query))
            ->editColumn('name', fn (Category $row) => '<span class="ob-item-cell__title">'.e($row->name).'</span>')
            ->editColumn('status', fn (Category $row) => $row->status
                ? '<span class="badge badge-success">Aktif</span>'
                : '<span class="badge badge-muted">Nonaktif</span>')
            ->editColumn('reward_categories', fn (Category $row) => $row->reward_categories
                ? '<span class="badge badge-warning">Reward</span>'
                : '<span class="text-muted">Biasa</span>')
            ->editColumn('show_in_order_table', fn (Category $row) => $row->show_in_order_table
                ? '<span class="badge badge-success">Tampil</span>'
                : '<span class="badge badge-muted">Disembunyikan</span>')
            ->editColumn('created_at', fn (Category $row) => $row->created_at
                ? '<span title="'.e($row->created_at->translatedFormat('d M Y, H:i')).'">'.e(Carbon::parse($row->created_at)->diffForHumans()).'</span>'
                : '-')
            ->addColumn('action', fn (Category $row) => view('layouts.category.action', [
                'category' => $row,
                'canUpdate' => $canUpdate,
            ])->render())
            ->rawColumns(['name', 'status', 'reward_categories', 'show_in_order_table', 'created_at', 'action'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Category $model): QueryBuilder
    {
        $query = $model->newQuery();

        foreach (['status' => 'status', 'reward' => 'reward_categories', 'order_table' => 'show_in_order_table'] as $param => $column) {
            $value = $this->request()->get($param);
            if ($value === '0' || $value === '1') {
                $query->where($column, (int) $value);
            }
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('category-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'status' => "$('#filter-status').val()",
                'reward' => "$('#filter-reward').val()",
                'order_table' => "$('#filter-order-table').val()",
            ])
            // Hanya tabel yang scroll horizontal, length/search/paging tetap di tempat.
            ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
            ->orderBy(0, 'asc');
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('name')->title('Kategori'),
            Column::make('status')->title('Status')->searchable(false),
            Column::make('reward_categories')->title('Reward')->searchable(false),
            Column::make('show_in_order_table')->title('Order Table')->searchable(false),
            Column::make('created_at')->title('Dibuat')->searchable(false)->addClass('ob-col-time'),
            Column::computed('action')->title('')->exportable(false)->printable(false)->width(60)->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Category_'.date('YmdHis');
    }
}

<?php

namespace App\DataTables;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class UserPermissionDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        // Diambil sekali per request; dulu Role::find per baris dan error saat role user sudah dihapus
        $roleNames = Role::pluck('name', 'id');

        return (new EloquentDataTable($query))
            ->editColumn('name', fn (User $user) => '<div class="ob-cell-stack">'
                .'<span class="ob-user-name">'.e($user->name).'</span>'
                .'<span class="ob-cell-meta">@'.e($user->username).'</span></div>')
            ->filterColumn('name', fn (QueryBuilder $query, string $keyword) => $query->where(
                fn (QueryBuilder $query) => $query->where('users.name', 'like', "%{$keyword}%")
                    ->orWhere('users.username', 'like', "%{$keyword}%")
            ))
            ->addColumn('role_name', fn (User $user) => isset($roleNames[$user->role])
                ? '<span class="badge badge-primary">'.e($roleNames[$user->role]).'</span>'
                : '<span class="ob-cell-meta">Tanpa role</span>')
            ->editColumn('permissions_count', fn (User $user) => $user->permissions_count > 0
                ? '<span class="badge badge-warning">+'.e($user->permissions_count).' akses</span>'
                : '<span class="ob-cell-meta">Mengikuti role</span>')
            ->addColumn('action', fn (User $user) => view('layouts.hak_akses.action', compact('user'))->render())
            ->rawColumns(['name', 'role_name', 'permissions_count', 'action'])
            ->setRowId('id');
    }

    public function query(User $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->withCount('permissions')
            ->where('role', '!=', 1)
            ->where('deleted', '!=', 1);

        if ($roleId = request()->integer('role_id')) {
            $query->where('role', $roleId);
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('user-permission-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, ['role_id' => "$('#role-filter').val()"])
            // Default Bootstrap 4 dom, but only the table scrolls horizontally so length/search/paging stay put.
            ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
            ->orderBy(0, 'asc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('name')->title('User'),
            Column::computed('role_name')->title('Role'),
            Column::make('permissions_count')->title('Akses Tambahan')->searchable(false),
            Column::computed('action')->title('')->exportable(false)->printable(false)->orderable(false)->searchable(false)->width(70)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'UserPermission_'.date('YmdHis');
    }
}

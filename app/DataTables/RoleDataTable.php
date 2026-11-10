<?php

namespace App\DataTables;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class RoleDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('name', fn (Role $role) => '<div class="ob-cell-stack">'
                .'<span class="ob-role-name">'.e($role->name).'</span>'
                .($role->isSystemRole() ? '<span class="ob-chip"><i class="fas fa-lock" aria-hidden="true"></i> Role sistem</span>' : '')
                .'</div>')
            ->editColumn('users_count', fn (Role $role) => $role->users_count > 0
                ? '<span class="ob-count-cell">'.e(number_format($role->users_count, 0, ',', '.')).' user</span>'
                : '<span class="ob-cell-meta">Belum dipakai</span>')
            ->editColumn('permissions_count', function (Role $role) {
                $label = $role->isSystemRole() && strtolower($role->name) === 'super admin'
                    ? 'Semua akses'
                    : number_format($role->permissions_count, 0, ',', '.').' permission';

                return '<span class="ob-count-cell">'.e($label).'</span>';
            })
            ->editColumn('updated_at', fn (Role $role) => $role->updated_at
                ? e($role->updated_at->translatedFormat('d M Y, H:i'))
                : '<span class="ob-cell-meta">-</span>')
            ->addColumn('action', fn (Role $role) => view('layouts.role.action', compact('role'))->render())
            ->rawColumns(['name', 'users_count', 'permissions_count', 'updated_at', 'action'])
            ->setRowId('id');
    }

    public function query(Role $model): QueryBuilder
    {
        return $model->newQuery()
            ->select('roles.*')
            // Dihitung dari kolom users.role, sumber yang sama dengan halaman Users
            ->selectSub(
                User::query()->selectRaw('count(*)')->whereColumn('users.role', 'roles.id')->where('deleted', '!=', 1),
                'users_count'
            )
            ->withCount('permissions');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('role-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            // Default Bootstrap 4 dom, but only the table scrolls horizontally so length/search/paging stay put.
            ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
            ->orderBy(0, 'asc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('name')->title('Role'),
            Column::make('users_count')->title('Dipakai')->searchable(false),
            Column::make('permissions_count')->title('Hak Akses')->searchable(false),
            Column::make('updated_at')->title('Terakhir Diubah')->searchable(false)->addClass('ob-col-time'),
            Column::computed('action')->title('')->exportable(false)->printable(false)->orderable(false)->searchable(false)->width(120)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'Role_'.date('YmdHis');
    }
}

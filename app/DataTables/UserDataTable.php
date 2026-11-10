<?php

namespace App\DataTables;

use App\Models\Outlets;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class UserDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        // Diambil sekali per request agar tidak query per baris
        $roleNames = Role::pluck('name', 'id');
        $outletNames = Outlets::pluck('name', 'id');

        return (new EloquentDataTable($query))
            ->editColumn('name', fn (User $user) => '<div class="ob-user-cell">'
                .'<span class="ob-user-avatar" aria-hidden="true">'.e($this->initials($user->name)).'</span>'
                .'<div class="ob-cell-stack"><span class="ob-user-name">'.e($user->name).'</span>'
                .'<span class="ob-cell-meta">@'.e($user->username).'</span></div></div>')
            // Kolom user menampilkan nama + username, jadi pencarian mencakup keduanya
            ->filterColumn('name', fn (QueryBuilder $query, string $keyword) => $query->where(
                fn (QueryBuilder $query) => $query->where('users.name', 'like', "%{$keyword}%")
                    ->orWhere('users.username', 'like', "%{$keyword}%")
            ))
            ->editColumn('email', fn (User $user) => filled($user->email)
                ? e($user->email)
                : '<span class="ob-cell-meta">-</span>')
            ->addColumn('role_name', fn (User $user) => isset($roleNames[$user->role])
                ? '<span class="badge badge-primary">'.e($roleNames[$user->role]).'</span>'
                : '<span class="ob-cell-meta">Tanpa role</span>')
            ->addColumn('outlets', function (User $user) use ($outletNames) {
                // Outlet yang sudah dihapus dilewati agar tabel tidak error
                $chips = collect($user->outletIds())
                    ->filter(fn ($id) => isset($outletNames[$id]))
                    ->map(fn ($id) => '<span class="ob-chip">'.e($outletNames[$id]).'</span>')
                    ->implode('');

                return $chips === ''
                    ? '<span class="ob-cell-meta">-</span>'
                    : '<div class="ob-chips">'.$chips.'</div>';
            })
            ->editColumn('status', fn (User $user) => (string) $user->status === '1'
                ? '<span class="badge badge-success">Aktif</span>'
                : '<span class="badge badge-muted">Nonaktif</span>')
            ->addColumn('action', fn (User $user) => view('layouts.users.action', compact('user'))->render())
            ->rawColumns(['name', 'email', 'role_name', 'outlets', 'status', 'action'])
            ->setRowId('id');
    }

    public function query(User $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->where('role', '!=', 1)
            ->where('deleted', '!=', 1);

        if ($roleId = request()->integer('role_id')) {
            $query->where('role', $roleId);
        }

        if (in_array(request('status'), ['0', '1'], true)) {
            $query->where('status', request('status'));
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('user-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'role_id' => "$('#role-filter').val()",
                'status' => "$('#status-filter').val()",
            ])
            // Default Bootstrap 4 dom, but only the table scrolls horizontally so length/search/paging stay put.
            ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
            ->orderBy(0, 'asc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('name')->title('User'),
            Column::make('email')->title('Email'),
            Column::computed('role_name')->title('Role'),
            Column::computed('outlets')->title('Outlet'),
            Column::make('status')->title('Status')->searchable(false),
            Column::computed('action')->title('')->exportable(false)->printable(false)->orderable(false)->searchable(false)->width(90)->addClass('text-center'),
        ];
    }

    private function initials(?string $name): string
    {
        $initials = collect(preg_split('/\s+/', trim((string) $name)))
            ->filter()
            ->take(2)
            ->map(fn ($word) => mb_substr($word, 0, 1))
            ->implode('');

        return mb_strtoupper($initials ?: '?');
    }

    protected function filename(): string
    {
        return 'User_'.date('YmdHis');
    }
}

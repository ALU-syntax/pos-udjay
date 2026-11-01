<?php

namespace App\DataTables;

use App\Models\ModifierGroup;
use App\Models\Modifiers;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ModifiersDatatables extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $canUpdate = auth()->user()->can('update library/modifiers');
        $canDelete = auth()->user()->can('delete library/modifiers');

        return datatables()
            ->eloquent($query)
            ->editColumn('name', fn (ModifierGroup $row) => $this->groupCell($row))
            ->addColumn('option_name', fn (ModifierGroup $row) => $this->optionChips($row))
            ->addColumn('outlet', fn (ModifierGroup $row) => $row->outlet
                ? '<span class="badge badge-primary rounded-pill">'.e($row->outlet->name).'</span>'
                : '<span class="text-muted">-</span>')
            ->editColumn('show_in_order_table', function (ModifierGroup $row) use ($canUpdate) {
                $shown = (bool) $row->show_in_order_table;
                $noVisibleOption = $row->modifier->where('show_in_order_table', true)->isEmpty();
                $notes = [];
                if ($shown && $noVisibleOption) {
                    $notes[] = 'Semua opsi disembunyikan';
                }
                // Grup wajib yang tidak tampil tidak diminta di Order Table (pesanan bisa masuk tanpa pilihan ini).
                if ($row->is_required && (! $shown || $noVisibleOption)) {
                    $notes[] = 'Grup wajib tidak diminta di Order Table';
                }

                return view('components.order-table-switch', [
                    'checked' => $shown,
                    'url' => route('library/modifiers/order-table', $row->id),
                    'canUpdate' => $canUpdate,
                    'note' => implode('. ', $notes) ?: null,
                ])->render();
            })
            ->addColumn('action', fn (ModifierGroup $row) => view('layouts.modifiers.action', [
                'group' => $row,
                'canUpdate' => $canUpdate,
                'canDelete' => $canDelete,
            ])->render())
            ->filterColumn('option_name', function (QueryBuilder $query, string $keyword) {
                $query->whereHas('modifier', fn (QueryBuilder $option) => $option->where('name', 'like', "%{$keyword}%"));
            })
            ->rawColumns(['name', 'option_name', 'outlet', 'show_in_order_table', 'action'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(ModifierGroup $model): QueryBuilder
    {
        $outletIds = auth()->user()->outletIds();
        // Selalu dibatasi outlet milik user (sebelumnya muatan pertama tanpa parameter outlet tidak disaring).
        $query = $model->newQuery()
            ->with(['modifier', 'outlet'])
            ->whereIn('outlet_id', array_map('strval', $outletIds));

        $outlet = $this->request()->get('outlet');
        if ($outlet !== null && $outlet !== '' && $outlet !== 'all' && in_array((int) $outlet, $outletIds, true)) {
            $query->where('outlet_id', (string) (int) $outlet);
        }

        $orderTable = $this->request()->get('order_table');
        if ($orderTable === '0' || $orderTable === '1') {
            $query->where('show_in_order_table', (int) $orderTable);
        }

        $required = $this->request()->get('required');
        if ($required === '0' || $required === '1') {
            $query->where('is_required', (int) $required);
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
            ->minifiedAjax('', null, [
                'outlet' => "$('#filter-outlet').val()",
                'required' => "$('#filter-required').val()",
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
            Column::make('name')->title('Grup Modifier'),
            Column::computed('option_name')->title('Opsi')->searchable(true),
            Column::computed('outlet')->title('Outlet'),
            Column::make('show_in_order_table')->title('Order Table')->searchable(false)->exportable(false)->printable(false),
            Column::computed('action')->title('')->exportable(false)->printable(false)->width(130)->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'ModifiersDatatables_'.date('YmdHis');
    }

    private function groupCell(ModifierGroup $row): string
    {
        $products = $this->productCount($row->product_id);
        $meta = [
            $row->is_required ? 'Wajib dipilih' : 'Opsional',
            $products > 0 ? $products.' produk' : 'Belum dipasang ke produk',
        ];

        return '<div class="ob-cell-stack ob-cell-stack--wrap">'
            .'<span class="ob-item-cell__title">'.e($row->name).'</span>'
            .'<span class="ob-cell-meta">'.e(implode(' · ', $meta)).'</span></div>';
    }

    private function optionChips(ModifierGroup $row): string
    {
        if ($row->modifier->isEmpty()) {
            return '<span class="text-muted">-</span>';
        }

        $chips = $row->modifier->map(function (Modifiers $option) {
            $price = (int) $option->harga > 0
                ? ' <span class="ob-chip__price">+'.number_format((int) $option->harga, 0, ',', '.').'</span>'
                : '';
            $hidden = ! $option->show_in_order_table;

            return '<span class="ob-chip'.($hidden ? ' ob-chip--hidden' : '').'"'
                .($hidden ? ' title="Disembunyikan dari Order Table"' : '').'>'
                .e($option->name).$price.'</span>';
        })->implode('');

        return '<div class="ob-chips">'.$chips.'</div>';
    }

    /** modifier_groups.product_id berisi array JSON id produk (teks, atau sudah ter-parse). */
    private function productCount(mixed $raw): int
    {
        $ids = is_string($raw) ? json_decode($raw, true) : $raw;

        return is_array($ids) ? count($ids) : 0;
    }
}

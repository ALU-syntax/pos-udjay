<?php

namespace App\DataTables\OrderTable;

use App\Models\OrderTable\PaymentMethod;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class PaymentMethodDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('outlet_name', fn (PaymentMethod $method) => e($method->outlet?->name ?? '-'))
            ->editColumn('label', fn (PaymentMethod $method) => '<div class="ob-cell-stack">'
                .'<span>'.e($method->label).'</span>'
                .'<span class="ob-method-code">'.e($method->code).'</span></div>')
            // Kolom metode menampilkan label + kode, jadi pencarian mencakup keduanya
            ->filterColumn('label', fn (QueryBuilder $query, string $keyword) => $query->where(
                fn (QueryBuilder $query) => $query->where('ot_payment_methods.label', 'like', "%{$keyword}%")
                    ->orWhere('ot_payment_methods.code', 'like', "%{$keyword}%")
            ))
            ->addColumn('master_mapping', function (PaymentMethod $method) {
                $payment = $method->payment?->name ?? 'Tanpa payment';
                $category = $method->categoryPayment?->name ?? 'Tanpa kategori';

                return '<div class="ob-cell-stack"><span>'.e($payment).'</span>'
                    .'<span class="ob-cell-meta">'.e($category).'</span></div>';
            })
            ->addColumn('deadline', function (PaymentMethod $method) {
                [$minutes, $description] = $method->code === 'qris'
                    ? [$method->qris_expiry_minutes, 'Masa berlaku QRIS']
                    : [$method->payment_due_minutes, 'Batas bayar di kasir'];

                return '<div class="ob-cell-stack"><span>'.e(($minutes ?? '-').' menit').'</span>'
                    .'<span class="ob-cell-meta">'.e($description).'</span></div>';
            })
            ->editColumn('enabled', fn (PaymentMethod $method) => $method->enabled
                ? '<span class="badge badge-success">Aktif</span>'
                : '<span class="badge badge-muted">Nonaktif</span>')
            ->addColumn('action', fn (PaymentMethod $method) => view(
                'layouts.order-table.payment-methods.action',
                compact('method')
            )->render())
            ->rawColumns(['label', 'master_mapping', 'deadline', 'enabled', 'action'])
            ->setRowId('id');
    }

    public function query(PaymentMethod $model): QueryBuilder
    {
        $outletIds = auth()->user()->outletIds();
        $query = $model->newQuery()
            // Pengurutan kolom outlet melakukan join ke outlets; tanpa ini outlets.id menimpa id metode
            ->select('ot_payment_methods.*')
            ->with(['outlet', 'payment', 'categoryPayment'])
            ->whereIn('outlet_id', $outletIds);

        $outletId = request()->integer('outlet_id');
        if ($outletId && in_array($outletId, $outletIds, true)) {
            $query->where('outlet_id', $outletId);
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('payment-method-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, ['outlet_id' => "$('#outlet-filter').val()"])
            // Default Bootstrap 4 dom, but only the table scrolls horizontally so length/search/paging stay put.
            ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
            // Per outlet lalu Urutan, sama seperti urutan metode yang dilihat pelanggan
            ->orders([[0, 'asc'], [5, 'asc']]);
    }

    public function getColumns(): array
    {
        return [
            Column::make('outlet_name')->title('Outlet')->name('outlet.name')->addClass('ob-col-name'),
            Column::make('label')->title('Metode'),
            Column::computed('master_mapping')->title('Mapping Master'),
            Column::computed('deadline')->title('Batas Waktu'),
            Column::make('enabled')->title('Status')->searchable(false),
            Column::make('sort_order')->title('Urutan')->searchable(false),
            Column::computed('action')->title('')->exportable(false)->printable(false)->orderable(false)->searchable(false)->width(90)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'PaymentMethod_'.date('YmdHis');
    }
}

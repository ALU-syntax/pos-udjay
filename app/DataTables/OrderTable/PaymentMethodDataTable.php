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
            ->editColumn('code', fn (PaymentMethod $method) => '<code>'.e($method->code).'</code>')
            ->addColumn('master_mapping', function (PaymentMethod $method) {
                $payment = $method->payment?->name ?? 'Tanpa payment';
                $category = $method->categoryPayment?->name ?? 'Tanpa kategori';

                return '<div>'.e($payment).'</div><small class="text-muted">'.e($category).'</small>';
            })
            ->addColumn('deadline', function (PaymentMethod $method) {
                if ($method->code === 'qris') {
                    return e(($method->qris_expiry_minutes ?? '-').' menit (QRIS)');
                }

                return e(($method->payment_due_minutes ?? '-').' menit');
            })
            ->editColumn('enabled', fn (PaymentMethod $method) => $method->enabled
                ? '<span class="badge badge-success">Aktif</span>'
                : '<span class="badge badge-secondary">Nonaktif</span>')
            ->addColumn('action', fn (PaymentMethod $method) => view(
                'layouts.order-table.payment-methods.action',
                compact('method')
            )->render())
            ->rawColumns(['code', 'master_mapping', 'enabled', 'action'])
            ->setRowId('id');
    }

    public function query(PaymentMethod $model): QueryBuilder
    {
        $outletIds = auth()->user()->outletIds();
        $query = $model->newQuery()
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
            ->responsive(true)
            ->orderBy(6);
    }

    public function getColumns(): array
    {
        return [
            Column::make('outlet_name')->title('Outlet')->name('outlet.name'),
            Column::make('code')->title('Kode'),
            Column::make('label')->title('Label'),
            Column::computed('master_mapping')->title('Mapping Master'),
            Column::computed('deadline')->title('Batas Waktu'),
            Column::make('enabled')->title('Status'),
            Column::make('sort_order')->title('Urutan'),
            Column::computed('action')->title('Aksi')->exportable(false)->printable(false)->orderable(false)->searchable(false)->width(120)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'PaymentMethod_'.date('YmdHis');
    }
}

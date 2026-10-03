<?php

namespace App\DataTables\OrderTable;

use App\Models\OrderTable\VoucherRedemption;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class VoucherRedemptionDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('created_at', fn (VoucherRedemption $redemption) => $redemption->created_at?->format('d M Y H:i:s') ?? '-')
            ->addColumn('outlet_name', fn (VoucherRedemption $redemption) => $redemption->order?->outlet?->name ?? '-')
            ->addColumn('voucher_code', fn (VoucherRedemption $redemption) => $redemption->voucher?->code ?? '-')
            ->addColumn('order_table', function (VoucherRedemption $redemption) {
                $order = $redemption->order?->order_no ?? '-';
                $table = $redemption->order?->table?->name ?? $redemption->order?->table?->code ?? '-';

                return $order.' / '.$table;
            })
            ->addColumn('session_reference', fn (VoucherRedemption $redemption) => '#'.$redemption->session_id)
            ->editColumn('device_id', fn (VoucherRedemption $redemption) => $this->mask($redemption->device_id, 8, 4))
            ->editColumn('discount_amount', fn (VoucherRedemption $redemption) => 'Rp '.number_format($redemption->discount_amount, 0, ',', '.'))
            ->editColumn('ip_address', fn (VoucherRedemption $redemption) => $this->mask($redemption->ip_address, 3, 2))
            ->editColumn('user_agent', fn (VoucherRedemption $redemption) => Str::limit((string) $redemption->user_agent, 70))
            ->removeColumn('voucher')
            ->removeColumn('order')
            ->removeColumn('session')
            ->setRowId('id');
    }

    public function query(VoucherRedemption $model): QueryBuilder
    {
        $outletIds = auth()->user()->outletIds();
        $query = $model->newQuery()
            ->with(['voucher', 'order.outlet', 'order.table', 'session'])
            ->whereHas('order', fn (QueryBuilder $order) => $order->whereIn('outlet_id', $outletIds));

        $outletId = request()->integer('outlet_id');
        if ($outletId && in_array($outletId, $outletIds, true)) {
            $query->whereHas('order', fn (QueryBuilder $order) => $order->where('outlet_id', $outletId));
        }
        if (request()->filled('voucher_id')) {
            $query->where('voucher_id', request()->integer('voucher_id'));
        }
        if (request()->filled('date_from')) {
            $query->whereDate('created_at', '>=', request('date_from'));
        }
        if (request()->filled('date_to')) {
            $query->whereDate('created_at', '<=', request('date_to'));
        }
        if (request()->filled('order')) {
            $term = request('order');
            $query->whereHas('order', fn (QueryBuilder $order) => $order->where('order_no', 'like', '%'.$term.'%'));
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('voucher-redemption-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'outlet_id' => "$('#outlet-filter').val()",
                'voucher_id' => "$('#voucher-filter').val()",
                'date_from' => "$('#date-from-filter').val()",
                'date_to' => "$('#date-to-filter').val()",
                'order' => "$('#order-filter').val()",
            ])
            ->responsive(true)
            ->orderBy(0, 'desc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('created_at')->title('Waktu'),
            Column::computed('outlet_name')->title('Outlet')->name('order.outlet.name'),
            Column::computed('voucher_code')->title('Voucher')->name('voucher.code'),
            Column::computed('order_table')->title('Order / Meja'),
            Column::computed('session_reference')->title('Sesi'),
            Column::make('device_id')->title('Perangkat'),
            Column::make('discount_amount')->title('Diskon'),
            Column::make('ip_address')->title('IP'),
            Column::make('user_agent')->title('User Agent'),
        ];
    }

    protected function filename(): string
    {
        return 'VoucherRedemption_'.date('YmdHis');
    }

    private function mask(?string $value, int $start, int $end): string
    {
        if (! $value) {
            return '-';
        }

        return strlen($value) <= $start + $end ? str_repeat('*', strlen($value)) : substr($value, 0, $start).'...'.substr($value, -$end);
    }
}

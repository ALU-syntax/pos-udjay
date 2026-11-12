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
        // Semua nilai di bawah di-escape manual karena kolomnya raw; user agent & device berasal dari browser pelanggan
        return (new EloquentDataTable($query))
            ->editColumn('created_at', fn (VoucherRedemption $redemption) => $redemption->created_at
                ? $this->stack(e($redemption->created_at->format('d M Y')), e($redemption->created_at->format('H:i:s')))
                : '<span class="text-muted">—</span>')
            ->addColumn('outlet_name', fn (VoucherRedemption $redemption) => e(
                ($redemption->isPos() ? $redemption->outlet?->name : $redemption->order?->outlet?->name) ?? '-'
            ))
            ->addColumn('voucher_code', fn (VoucherRedemption $redemption) => $redemption->voucher
                ? '<span class="ob-voucher-code">'.e(strtoupper($redemption->voucher->code)).'</span>'
                : '<span class="text-muted">—</span>')
            // Pemakaian di kasir (transaksi POS reguler) tidak punya order/meja: tampilkan referensi transaksinya.
            ->addColumn('order_table', fn (VoucherRedemption $redemption) => $redemption->isPos()
                ? $this->stack('Transaksi kasir', '<span title="'.e($redemption->pos_reference).'">'.e(Str::limit((string) $redemption->pos_reference, 18)).'</span>')
                : $this->stack(
                    e($redemption->order?->order_no ?? '-'),
                    e($redemption->order?->table?->name ?? $redemption->order?->table?->code ?? '-'),
                ))
            ->editColumn('discount_amount', fn (VoucherRedemption $redemption) => e('Rp '.number_format($redemption->discount_amount, 0, ',', '.')))
            ->editColumn('device_id', fn (VoucherRedemption $redemption) => $redemption->isPos()
                ? $this->stack('Kasir', 'Tanpa perangkat pelanggan')
                : $this->stack(
                    e($this->mask($redemption->device_id, 8, 4)),
                    e('Sesi #'.$redemption->session_id),
                ))
            ->editColumn('ip_address', fn (VoucherRedemption $redemption) => $this->stack(
                e($this->mask($redemption->ip_address, 3, 2)),
                $redemption->user_agent
                    ? '<span title="'.e($redemption->user_agent).'">'.e(Str::limit($redemption->user_agent, 48)).'</span>'
                    : '-',
            ))
            ->rawColumns(['created_at', 'outlet_name', 'voucher_code', 'order_table', 'discount_amount', 'device_id', 'ip_address'])
            ->removeColumn('voucher')
            ->removeColumn('order')
            ->removeColumn('session')
            ->removeColumn('outlet')
            ->removeColumn('user_agent')
            ->setRowId('id');
    }

    public function query(VoucherRedemption $model): QueryBuilder
    {
        $outletIds = auth()->user()->outletIds();
        // Pemakaian Order Table mengikuti outlet order-nya; pemakaian di kasir menyimpan outlet-nya sendiri.
        $inOutlets = fn (QueryBuilder $query, array $ids) => $query->where(fn (QueryBuilder $scope) => $scope
            ->whereHas('order', fn (QueryBuilder $order) => $order->whereIn('outlet_id', $ids))
            ->orWhere(fn (QueryBuilder $pos) => $pos->where('source', 'pos')->whereIn('outlet_id', $ids)));
        $query = $model->newQuery()->with(['voucher', 'order.outlet', 'order.table', 'session', 'outlet']);
        $inOutlets($query, $outletIds);

        $outletId = request()->integer('outlet_id');
        if ($outletId && in_array($outletId, $outletIds, true)) {
            $inOutlets($query, [$outletId]);
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
        // Bukan "order": nama itu dipakai DataTables untuk pengurutan kolom dan akan tertimpa
        if (request()->filled('order_no')) {
            $term = request('order_no');
            $query->where(fn (QueryBuilder $scope) => $scope
                ->whereHas('order', fn (QueryBuilder $order) => $order->where('order_no', 'like', '%'.$term.'%'))
                ->orWhere('pos_reference', 'like', '%'.$term.'%'));
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
                'order_no' => "$('#order-filter').val()",
            ])
            // Default Bootstrap 4 dom, but only the table scrolls horizontally so length/search/paging stay put.
            ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
            ->orderBy(0, 'desc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('created_at')->title('Waktu')->addClass('ob-col-time'),
            Column::computed('outlet_name')->title('Outlet')->name('order.outlet.name')->addClass('ob-col-name'),
            Column::computed('voucher_code')->title('Voucher')->name('voucher.code'),
            Column::computed('order_table')->title('Order / Meja'),
            Column::make('discount_amount')->title('Diskon')->addClass('ob-col-amount'),
            Column::make('device_id')->title('Perangkat / Sesi'),
            Column::make('ip_address')->title('IP / Browser'),
        ];
    }

    protected function filename(): string
    {
        return 'VoucherRedemption_'.date('YmdHis');
    }

    /** Sel dua baris; kedua argumen harus sudah di-escape. */
    private function stack(string $main, string $meta): string
    {
        return '<div class="ob-cell-stack"><span>'.$main.'</span><span class="ob-cell-meta">'.$meta.'</span></div>';
    }

    private function mask(?string $value, int $start, int $end): string
    {
        if (! $value) {
            return '-';
        }

        return strlen($value) <= $start + $end ? str_repeat('*', strlen($value)) : substr($value, 0, $start).'...'.substr($value, -$end);
    }
}

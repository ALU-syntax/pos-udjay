<?php

namespace App\DataTables\OrderTable;

use App\Models\OrderTable\Voucher;
use App\Services\OrderTable\CatalogSelectorService;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class VoucherDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('outlet_name', fn (Voucher $voucher) => $voucher->outlet?->name ?? 'Global')
            ->editColumn('code', fn (Voucher $voucher) => strtoupper($voucher->code))
            ->addColumn('type_value', fn (Voucher $voucher) => $voucher->type === 'percent'
                ? number_format($voucher->value).' %'
                : 'Rp '.number_format($voucher->value, 0, ',', '.'))
            ->editColumn('min_spend', fn (Voucher $voucher) => 'Rp '.number_format($voucher->min_spend, 0, ',', '.'))
            ->addColumn('quota', fn (Voucher $voucher) => number_format($voucher->quota_used).' / '.($voucher->quota_total === null ? 'Tak terbatas' : number_format($voucher->quota_total)))
            ->addColumn('effective_status', fn (Voucher $voucher) => $this->effectiveStatus($voucher))
            ->editColumn('scope', fn (Voucher $voucher) => match ($voucher->scope) {
                'category' => 'Kategori',
                'product' => 'Produk',
                default => 'Semua produk',
            })
            ->addColumn('valid_period', function (Voucher $voucher) {
                $from = $voucher->valid_from?->format('d M Y H:i') ?? 'Tanpa batas awal';
                $to = $voucher->valid_to?->format('d M Y H:i') ?? 'Tanpa batas akhir';

                return $from.' - '.$to;
            })
            ->addColumn('action', fn (Voucher $voucher) => view('layouts.order-table.vouchers.action', compact('voucher'))->render())
            ->rawColumns(['action'])
            ->setRowId('id');
    }

    public function query(Voucher $model): QueryBuilder
    {
        $outletIds = auth()->user()->outletIds();
        $canManageGlobal = app(CatalogSelectorService::class)->canUseGlobal(auth()->user());
        $query = $model->newQuery()->with('outlet');

        $query->where(function (QueryBuilder $query) use ($outletIds, $canManageGlobal) {
            $query->whereIn('outlet_id', $outletIds);
            if ($canManageGlobal) {
                $query->orWhereNull('outlet_id');
            }
        });

        if (request()->filled('outlet_id')) {
            if (request('outlet_id') === 'global' && $canManageGlobal) {
                $query->whereNull('outlet_id');
            } else {
                $outletId = request()->integer('outlet_id');
                if (in_array($outletId, $outletIds, true)) {
                    $query->where('outlet_id', $outletId);
                }
            }
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('voucher-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, ['outlet_id' => "$('#outlet-filter').val()"])
            ->responsive(true)
            ->orderBy(0);
    }

    public function getColumns(): array
    {
        return [
            Column::make('outlet_name')->title('Outlet / Global')->name('outlet.name'),
            Column::make('code')->title('Kode'),
            Column::computed('type_value')->title('Tipe / Nilai'),
            Column::make('min_spend')->title('Min. Belanja'),
            Column::computed('quota')->title('Kuota Terpakai / Total'),
            Column::computed('effective_status')->title('Status Efektif'),
            Column::make('scope')->title('Cakupan'),
            Column::computed('valid_period')->title('Periode Berlaku'),
            Column::computed('action')->title('Aksi')->exportable(false)->printable(false)->orderable(false)->searchable(false)->width(100)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'Voucher_'.date('YmdHis');
    }

    private function effectiveStatus(Voucher $voucher): string
    {
        if (! $voucher->status) {
            return 'Nonaktif';
        }
        if ($voucher->valid_from?->isFuture()) {
            return 'Terjadwal';
        }
        if ($voucher->valid_to?->isPast()) {
            return 'Kedaluwarsa';
        }
        if ($voucher->quota_total !== null && $voucher->quota_used >= $voucher->quota_total) {
            return 'Kuota habis';
        }

        return 'Aktif';
    }
}

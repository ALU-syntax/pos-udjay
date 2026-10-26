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
            ->editColumn('code', fn (Voucher $voucher) => $this->voucherCell($voucher))
            ->addColumn('outlet_name', fn (Voucher $voucher) => $voucher->outlet
                ? e($voucher->outlet->name)
                : '<span class="badge badge-primary rounded-pill">Global</span>')
            ->editColumn('min_spend', fn (Voucher $voucher) => $this->requirements($voucher))
            ->editColumn('quota_used', fn (Voucher $voucher) => $this->quota($voucher))
            ->addColumn('valid_period', fn (Voucher $voucher) => $this->period($voucher))
            ->addColumn('effective_status', fn (Voucher $voucher) => $this->statusBadge($voucher))
            ->addColumn('action', fn (Voucher $voucher) => view('layouts.order-table.vouchers.action', compact('voucher'))->render())
            ->rawColumns(['code', 'outlet_name', 'min_spend', 'quota_used', 'valid_period', 'effective_status', 'action'])
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
            // Default Bootstrap 4 dom, but only the table scrolls horizontally so length/search/paging stay put.
            ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
            ->orderBy(0, 'asc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('code')->title('Voucher'),
            Column::make('outlet_name')->title('Outlet')->name('outlet.name'),
            Column::make('min_spend')->title('Syarat')->searchable(false),
            Column::make('quota_used')->title('Kuota')->searchable(false),
            Column::computed('valid_period')->title('Periode Berlaku')->addClass('ob-col-time'),
            Column::computed('effective_status')->title('Status'),
            Column::computed('action')->title('')->exportable(false)->printable(false)->orderable(false)->searchable(false)->width(90)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'Voucher_'.date('YmdHis');
    }

    private function voucherCell(Voucher $voucher): string
    {
        $benefit = $voucher->type === 'percent'
            ? 'Diskon '.number_format($voucher->value).'%'
                .($voucher->max_discount ? ' · maks. '.$this->rupiah($voucher->max_discount) : '')
            : 'Potongan '.$this->rupiah($voucher->value);

        return '<div class="ob-voucher-cell">'
            .'<span class="ob-voucher-code">'.e(strtoupper($voucher->code)).'</span>'
            .'<span class="ob-cell-meta">'.e($benefit).'</span>'
            .'</div>';
    }

    private function requirements(Voucher $voucher): string
    {
        $count = count($voucher->scope_ids ?? []);
        $scope = match ($voucher->scope) {
            'category' => $count.' kategori',
            'product' => $count.' produk',
            default => 'Semua produk',
        };
        $minSpend = $voucher->min_spend > 0 ? 'Min. '.$this->rupiah($voucher->min_spend) : 'Tanpa minimum';

        return '<div class="ob-cell-stack"><span>'.e($minSpend).'</span>'
            .'<span class="ob-cell-meta">'.e($scope).'</span></div>';
    }

    private function quota(Voucher $voucher): string
    {
        $used = number_format($voucher->quota_used, 0, ',', '.');
        // "1× per perangkat" selalu berlaku: UNIQUE(voucher_id, device_id) di ot_voucher_redemptions.

        if ($voucher->quota_total === null) {
            return '<div class="ob-cell-stack"><span>'.$used.' terpakai</span>'
                .'<span class="ob-cell-meta">Total tanpa batas · 1× per perangkat</span></div>';
        }

        $percent = $voucher->quota_total > 0 ? min(100, (int) round($voucher->quota_used / $voucher->quota_total * 100)) : 100;
        $tone = match (true) {
            $percent >= 100 => 'danger',
            $percent >= 80 => 'warning',
            default => 'primary',
        };

        return '<div class="ob-cell-stack ob-quota">'
            .'<span>'.$used.' / '.number_format($voucher->quota_total, 0, ',', '.').'</span>'
            .'<span class="ob-quota__bar" role="img" aria-label="Kuota terpakai '.$percent.'%">'
            .'<span class="ob-quota__fill ob-quota__fill--'.$tone.'" style="width: '.$percent.'%"></span></span>'
            .'<span class="ob-cell-meta">1× per perangkat</span></div>';
    }

    private function period(Voucher $voucher): string
    {
        if (! $voucher->valid_from && ! $voucher->valid_to) {
            return '<span class="text-muted">Tanpa batas</span>';
        }

        $format = fn ($date) => $date ? $date->translatedFormat('d M Y, H:i') : '…';

        return e($format($voucher->valid_from).' – '.$format($voucher->valid_to));
    }

    private function statusBadge(Voucher $voucher): string
    {
        [$label, $variant] = match (true) {
            ! $voucher->status => ['Nonaktif', 'muted'],
            (bool) $voucher->valid_from?->isFuture() => ['Terjadwal', 'primary ob-badge-dot'],
            (bool) $voucher->valid_to?->isPast() => ['Kedaluwarsa', 'warning'],
            $voucher->quota_total !== null && $voucher->quota_used >= $voucher->quota_total => ['Kuota habis', 'danger'],
            default => ['Aktif', 'success'],
        };

        return '<span class="badge badge-'.$variant.'">'.$label.'</span>';
    }

    private function rupiah(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}

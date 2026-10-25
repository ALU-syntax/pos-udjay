<?php

namespace App\DataTables\OrderTable;

use App\Models\Category;
use App\Models\OrderTable\Banner;
use App\Models\OrderTable\Voucher;
use App\Models\Product;
use App\Services\OrderTable\CatalogSelectorService;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class BannerDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('title', fn (Banner $banner) => $this->bannerCell($banner))
            ->addColumn('outlet_name', fn (Banner $banner) => $banner->outlet
                ? e($banner->outlet->name)
                : '<span class="badge badge-primary rounded-pill">Global</span>')
            ->addColumn('action_target', fn (Banner $banner) => $this->actionTarget($banner))
            ->addColumn('period', fn (Banner $banner) => $this->period($banner))
            ->addColumn('effective_status', fn (Banner $banner) => $this->statusBadge($banner))
            ->addColumn('action', fn (Banner $banner) => view('layouts.order-table.banners.action', compact('banner'))->render())
            ->rawColumns(['title', 'outlet_name', 'action_target', 'period', 'effective_status', 'action'])
            ->setRowId('id');
    }

    public function query(Banner $model): QueryBuilder
    {
        $outletIds = auth()->user()->outletIds();
        $canManageGlobal = app(CatalogSelectorService::class)->canUseGlobal(auth()->user());
        $query = $model->newQuery()
            ->select('ot_banners.*')
            ->selectSub(Product::query()->select('name')->whereColumn('products.id', 'ot_banners.action_value')->limit(1), 'product_target_label')
            ->selectSub(Category::query()->select('name')->whereColumn('categories.id', 'ot_banners.action_value')->limit(1), 'category_target_label')
            ->selectSub(Voucher::query()->select('code')->whereColumn('ot_vouchers.id', 'ot_banners.action_value')->limit(1), 'voucher_target_label')
            ->with('outlet')->where(function (QueryBuilder $query) use ($outletIds, $canManageGlobal) {
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
        return $this->builder()->setTableId('banner-table')->columns($this->getColumns())
            ->minifiedAjax('', null, ['outlet_id' => "$('#outlet-filter').val()"])
            // Default Bootstrap 4 dom, but only the table scrolls horizontally so length/search/paging stay put.
            ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
            ->orderBy(5, 'asc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('title')->title('Banner'),
            Column::make('outlet_name')->title('Outlet')->name('outlet.name'),
            Column::computed('action_target')->title('Aksi / Target'),
            Column::computed('period')->title('Periode Tayang')->addClass('ob-col-time'),
            Column::computed('effective_status')->title('Status'),
            Column::make('sort_order')->title('Urutan')->addClass('ob-col-order'),
            Column::computed('action')->title('')->exportable(false)->printable(false)->orderable(false)->searchable(false)->width(90)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'Banner_'.date('YmdHis');
    }

    private function bannerCell(Banner $banner): string
    {
        $url = (string) $banner->image_url;
        $safeUrl = str_starts_with($url, '/')
            || filter_var($url, FILTER_VALIDATE_URL) && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true);

        $thumb = match (true) {
            $url !== '' && $safeUrl => '<img src="'.e($url).'" alt="" class="ob-banner-thumb" loading="lazy">',
            $banner->display_mode === 'text' && $url === '' => '<span class="ob-banner-thumb ob-banner-thumb--'.e($banner->tone ?? 'black').'" aria-hidden="true">Aa</span>',
            default => '<span class="ob-banner-thumb ob-banner-thumb--empty" title="Gambar tidak valid"><i class="fas fa-image" aria-hidden="true"></i></span>',
        };
        $meta = $banner->display_mode === 'text' ? 'Kartu teks' : 'Gambar';
        if ($banner->display_mode === 'text' && filled($banner->eyebrow)) {
            $meta .= ' · '.$banner->eyebrow;
        }

        return '<div class="ob-banner-cell">'.$thumb.'<div>'
            .'<div class="ob-banner-cell__title">'.e($banner->title).'</div>'
            .'<div class="ob-banner-cell__meta">'.e($meta).'</div>'
            .'</div></div>';
    }

    private function period(Banner $banner): string
    {
        if (! $banner->start_at && ! $banner->end_at) {
            return '<span class="text-muted">Tanpa batas</span>';
        }

        $format = fn ($date) => $date ? $date->translatedFormat('d M Y, H:i') : '…';

        return e($format($banner->start_at).' – '.$format($banner->end_at));
    }

    private function statusBadge(Banner $banner): string
    {
        [$label, $variant] = match (true) {
            ! $banner->status => ['Nonaktif', 'muted'],
            (bool) $banner->start_at?->isFuture() => ['Terjadwal', 'primary ob-badge-dot'],
            (bool) $banner->end_at?->isPast() => ['Berakhir', 'warning'],
            default => ['Aktif', 'success'],
        };

        return '<span class="badge badge-'.$variant.'">'.$label.'</span>';
    }

    private function actionTarget(Banner $banner): string
    {
        if ($banner->action_type === 'none') {
            return '<span class="text-muted">Tanpa aksi</span>';
        }

        $type = match ($banner->action_type) {
            'url' => 'URL',
            'product' => 'Produk',
            'category' => 'Kategori',
            'voucher' => 'Voucher',
            default => ucfirst($banner->action_type),
        };
        $label = match ($banner->action_type) {
            'product' => $banner->product_target_label,
            'category' => $banner->category_target_label,
            'voucher' => $banner->voucher_target_label,
            default => $banner->action_value,
        };

        return '<div class="ob-target"><span class="ob-target__type">'.e($type).'</span>'
            .'<span>'.e($label ?: '—').'</span></div>';
    }
}

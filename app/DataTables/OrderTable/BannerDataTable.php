<?php

namespace App\DataTables\OrderTable;

use App\Models\Category;
use App\Models\OrderTable\Banner;
use App\Models\OrderTable\Voucher;
use App\Models\Product;
use App\Models\Promo;
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
            ->addColumn('outlet_name', fn (Banner $banner) => $banner->outlet?->name ?? 'Global')
            ->addColumn('image', function (Banner $banner) {
                $url = (string) $banner->image_url;
                $safe = str_starts_with($url, '/') || filter_var($url, FILTER_VALIDATE_URL) && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true);

                return $safe
                    ? '<img src="'.e($url).'" alt="'.e($banner->title).'" class="order-table-thumbnail" loading="lazy">'
                    : '<span class="text-muted">Gambar tidak valid</span>';
            })
            ->addColumn('action_target', fn (Banner $banner) => $this->actionTargetLabel($banner))
            ->addColumn('effective_status', fn (Banner $banner) => $this->effectiveStatus($banner))
            ->addColumn('action', fn (Banner $banner) => view('layouts.order-table.banners.action', compact('banner'))->render())
            ->rawColumns(['image', 'action'])
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
            ->selectSub(Promo::query()->select('name')->whereColumn('promos.id', 'ot_banners.action_value')->limit(1), 'promo_target_label')
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
            ->responsive(true)->orderBy(6);
    }

    public function getColumns(): array
    {
        return [
            Column::make('outlet_name')->title('Outlet / Global')->name('outlet.name'),
            Column::make('title')->title('Judul'),
            Column::computed('image')->title('Gambar')->orderable(false)->searchable(false),
            Column::computed('action_target')->title('Aksi / Target'),
            Column::computed('effective_status')->title('Status Efektif'),
            Column::make('position')->title('Posisi'),
            Column::make('sort_order')->title('Urutan'),
            Column::computed('action')->title('Aksi')->exportable(false)->printable(false)->orderable(false)->searchable(false)->width(100)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'Banner_'.date('YmdHis');
    }

    private function effectiveStatus(Banner $banner): string
    {
        if (! $banner->status) {
            return 'Nonaktif';
        }
        if ($banner->start_at?->isFuture()) {
            return 'Terjadwal';
        }
        if ($banner->end_at?->isPast()) {
            return 'Berakhir';
        }

        return 'Aktif';
    }

    private function actionTargetLabel(Banner $banner): string
    {
        $type = ucfirst($banner->action_type);
        $label = match ($banner->action_type) {
            'product' => $banner->product_target_label,
            'category' => $banner->category_target_label,
            'voucher' => $banner->voucher_target_label,
            'promo' => $banner->promo_target_label,
            'internal' => match ($banner->action_value) {
                'home' => 'Beranda',
                'menu' => 'Daftar Menu',
                'cart' => 'Keranjang',
                'orders' => 'Status Pesanan',
                default => $banner->action_value,
            },
            default => $banner->action_value,
        };

        return $type.($label ? ': '.$label : '');
    }
}

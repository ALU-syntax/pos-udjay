<?php

namespace App\Services\B2b;

use App\Models\B2b\Customer;
use App\Models\B2b\Invoice;
use App\Models\B2b\Order;
use App\Models\B2b\Payment;
use App\Models\B2b\Refund;
use App\Models\Category;
use App\Models\Outlets;
use App\Support\B2b\ReportFilter;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Angka Laporan B2B. Definisi (disepakati 21 Nov 2026):
 * - Penjualan: grand_total pesanan Dikonfirmasi + Selesai menurut tanggal pesanan. Batal dan draft tidak dihitung.
 * - Kas: uang yang benar-benar diterima (pembayaran posted, atau reversed karena pesanan batal + refund) menurut tanggal bayar,
 *   dikurangi refund menurut tanggal refund. Pelunasan yang dibatalkan karena salah catat tidak dihitung sama sekali.
 * - Piutang: sisa invoice yang belum lunas per hari ini (tidak ikut periode), tetap ikut filter outlet dan customer.
 * Outlet = outlet pesanan.
 */
class ReportService
{
    /** Batas panjang periode (hari) supaya chart tetap terbaca: harian s.d. 92 batang, mingguan s.d. 53 batang. */
    public const MAX_DAYS_PER_GROUP = ['day' => 92, 'week' => 371];

    /** Penjualan per dimensi: customer / outlet (dari total pesanan) dan produk / varian / kategori (dari item pesanan). */
    public const BREAKDOWNS = ['customer', 'outlet', 'product', 'variant', 'category'];

    /** Jumlah baris teratas di chart peringkat; sisanya digabung jadi "Lainnya". */
    public const TOP_ITEMS = 10;

    public const ORDERS_PER_PAGE = 15;

    /** Singkatan bulan disamakan dengan JS B2B (Carbon id memakai "Agt"). */
    private const SHORT_MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    /** Kartu ringkasan di bagian atas halaman. */
    public function summary(ReportFilter $filter): array
    {
        $sales = $this->salesOrders($filter)
            ->selectRaw('count(*) as orders, count(distinct b2b_customer_id) as customers, coalesce(sum(grand_total), 0) as amount')
            ->selectRaw('coalesce(sum(case when status = ? then 1 else 0 end), 0) as completed', [Order::STATUS_COMPLETED])
            ->first();
        $cancelled = $this->periodOrders($filter)
            ->where('status', Order::STATUS_CANCELLED)
            ->selectRaw('count(*) as orders, coalesce(sum(grand_total), 0) as amount')
            ->first();

        $received = $this->receivedPayments($filter)->selectRaw('count(*) as total, coalesce(sum(amount), 0) as amount')->first();
        $refunded = $this->refunds($filter)->selectRaw('count(*) as total, coalesce(sum(amount), 0) as amount')->first();

        $outstanding = 'count(*) as invoices, count(distinct b2b_customer_id) as customers, coalesce(sum(total_to_pay - paid_amount), 0) as amount';
        $receivable = $this->openInvoices($filter)->selectRaw($outstanding)->first();
        $overdue = $this->openInvoices($filter)->whereDate('due_date', '<', today())->selectRaw($outstanding)->first();

        return [
            'as_of' => today()->toDateString(),
            'sales' => [
                'amount' => (int) $sales->amount,
                'orders' => (int) $sales->orders,
                'customers' => (int) $sales->customers,
                'completed' => (int) $sales->completed,
                'confirmed' => (int) $sales->orders - (int) $sales->completed,
            ],
            'cancelled' => ['amount' => (int) $cancelled->amount, 'orders' => (int) $cancelled->orders],
            'cash' => [
                'received' => (int) $received->amount,
                'received_count' => (int) $received->total,
                'refunded' => (int) $refunded->amount,
                'refund_count' => (int) $refunded->total,
                'net' => (int) $received->amount - (int) $refunded->amount,
            ],
            'receivable' => $this->outstanding($receivable),
            'overdue' => $this->outstanding($overdue),
        ];
    }

    /**
     * Tren penjualan per hari / minggu (Senin–Minggu) / bulan. Pengelompokan otomatis dari panjang periode kalau $group
     * kosong atau terlalu rapat. Periode tanpa penjualan tetap muncul dengan nilai 0, dan batas kelompok dipotong
     * mengikuti periode filter supaya drilldown memakai rentang tanggal yang tepat.
     */
    public function trend(ReportFilter $filter, ?string $group = null): array
    {
        $days = (int) $filter->from->diffInDays($filter->to) + 1;
        $groups = array_values(array_filter(
            ['day', 'week', 'month'],
            fn (string $name) => $days <= (self::MAX_DAYS_PER_GROUP[$name] ?? PHP_INT_MAX)
        ));
        if (! in_array($group, $groups, true)) {
            $group = $days <= 31 ? 'day' : ($days <= 184 ? 'week' : 'month');
        }

        $daily = $this->salesOrders($filter)
            ->selectRaw('date(order_date) as day, count(*) as orders, coalesce(sum(grand_total), 0) as amount')
            ->groupByRaw('date(order_date)')
            ->get()
            ->keyBy(fn ($row) => (string) $row->day);

        $buckets = [];
        for ($start = $filter->from; $start->lte($filter->to); $start = $end->addDay()) {
            $end = match ($group) {
                'day' => $start,
                'week' => $start->endOfWeek(CarbonInterface::SUNDAY)->startOfDay(),
                'month' => $start->endOfMonth()->startOfDay(),
            };
            $end = $end->min($filter->to);

            $amount = 0;
            $orders = 0;
            for ($day = $start; $day->lte($end); $day = $day->addDay()) {
                $row = $daily->get($day->toDateString());
                $amount += (int) ($row->amount ?? 0);
                $orders += (int) ($row->orders ?? 0);
            }

            $buckets[] = [
                'from' => $start->toDateString(),
                'to' => $end->toDateString(),
                'label' => $this->bucketLabel($group, $start, $end),
                'title' => $this->bucketTitle($group, $start, $end),
                'amount' => $amount,
                'orders' => $orders,
            ];
        }

        return [
            'group' => $group,
            'groups' => $groups,
            'total' => array_sum(array_column($buckets, 'amount')),
            'buckets' => $buckets,
        ];
    }

    /**
     * Penjualan per customer / outlet / produk / varian / kategori: 10 teratas + gabungan sisanya.
     * Customer dan outlet memakai total pesanan; produk, varian, kategori, dan semua drilldown yang memakai filter
     * produk / varian / kategori memakai nilai item (line_total) supaya angkanya sama dengan barang yang dipilih.
     * Produk dan varian dikelompokkan menurut nama di pesanan, jadi produk yang sama di beberapa outlet tergabung.
     */
    public function breakdown(ReportFilter $filter, string $by, int $limit = self::TOP_ITEMS): array
    {
        $fromItems = in_array($by, ['product', 'variant', 'category'], true) || $filter->hasItemFilter();
        $key = match ($by) {
            'customer' => 'b2b_orders.b2b_customer_id',
            'outlet' => 'b2b_orders.outlet_id',
            'product' => 'items.product_name',
            'variant' => 'items.variant_name',
            'category' => 'coalesce(products.category_id, 0)',
        };

        $query = $fromItems
            ? $this->salesItems($filter, $by === 'category')
                ->selectRaw("{$key} as group_key, count(distinct b2b_orders.id) as orders, coalesce(sum(items.qty), 0) as qty, coalesce(sum(items.line_total), 0) as amount")
            : $this->salesOrders($filter)
                ->selectRaw("{$key} as group_key, count(*) as orders, null as qty, coalesce(sum(b2b_orders.grand_total), 0) as amount");
        $rows = $query->groupByRaw($key)->orderByDesc('amount')->orderByRaw($key)->toBase()->get();

        $top = $rows->take($limit);
        $rest = $rows->slice($limit);
        $labels = $this->breakdownLabels($by, $top->pluck('group_key'));

        return [
            'by' => $by,
            'total' => (int) $rows->sum('amount'),
            'items' => $top->map(fn ($row) => [
                'key' => is_numeric($row->group_key) ? (int) $row->group_key : (string) $row->group_key,
                'label' => $labels[$row->group_key]['label'] ?? (string) $row->group_key,
                'sub' => $labels[$row->group_key]['sub'] ?? null,
                'amount' => (int) $row->amount,
                'orders' => (int) $row->orders,
                'qty' => $row->qty === null ? null : (int) $row->qty,
            ])->values()->all(),
            'others' => $rest->isEmpty() ? null : [
                'count' => $rest->count(),
                'amount' => (int) $rest->sum('amount'),
                'orders' => (int) $rest->sum('orders'),
            ],
        ];
    }

    /**
     * Daftar pesanan penjualan (dikonfirmasi + selesai) untuk modal detail, terbaru dulu.
     * Dengan filter produk / varian / kategori: hanya pesanan yang memuat barang itu, dan nilainya = nilai item tersebut.
     */
    public function orders(ReportFilter $filter, int $page = 1): array
    {
        $itemFilter = $filter->hasItemFilter();
        $totals = $itemFilter
            ? $this->salesItems($filter)->selectRaw('count(distinct b2b_orders.id) as orders, coalesce(sum(items.line_total), 0) as amount')->toBase()->first()
            : $this->salesOrders($filter)->selectRaw('count(*) as orders, coalesce(sum(grand_total), 0) as amount')->first();

        $paginator = $this->salesOrders($filter)
            ->when($itemFilter, fn (Builder $query) => $query
                ->whereHas('items', fn (Builder $items) => $this->applyItemFilter($items, $filter))
                ->withSum(['items as item_amount' => fn (Builder $items) => $this->applyItemFilter($items, $filter)], 'line_total'))
            ->with(['customer:id,code,name', 'outlet:id,name'])
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate(self::ORDERS_PER_PAGE, ['*'], 'page', max(1, $page));

        return [
            'item_filter' => $itemFilter,
            'totals' => ['amount' => (int) $totals->amount, 'orders' => (int) $totals->orders],
            'items' => collect($paginator->items())->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'date' => $this->shortDate($order->order_date).' '.$order->order_date->format('H:i'),
                'customer' => $order->customer?->name,
                'outlet' => $order->outlet?->name,
                'status' => $order->statusLabel(),
                'status_variant' => $order->statusVariant(),
                'payment_type' => $order->paymentTypeLabel(),
                'amount' => (int) ($itemFilter ? $order->item_amount : $order->grand_total),
                'order_total' => (int) $order->grand_total,
            ])->all(),
            'page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    /** Pesanan yang dihitung sebagai penjualan: dikonfirmasi + selesai. */
    private function salesOrders(ReportFilter $filter): Builder
    {
        return $this->periodOrders($filter)->whereIn('b2b_orders.status', [Order::STATUS_CONFIRMED, Order::STATUS_COMPLETED]);
    }

    /** Pesanan di periode, outlet, dan customer terpilih (semua status). */
    private function periodOrders(ReportFilter $filter): Builder
    {
        return Order::query()
            ->whereIn('b2b_orders.outlet_id', $filter->outletIds)
            ->whereBetween('b2b_orders.order_date', $filter->between())
            ->when($filter->customerId, fn (Builder $query, int $id) => $query->where('b2b_orders.b2b_customer_id', $id));
    }

    /** Item pesanan penjualan (alias items) dengan filter produk / varian / kategori. */
    private function salesItems(ReportFilter $filter, bool $withProducts = false): Builder
    {
        $withProducts = $withProducts || $filter->categoryId !== null;

        return $this->salesOrders($filter)
            ->join('b2b_order_items as items', 'items.b2b_order_id', '=', 'b2b_orders.id')
            ->when($withProducts, fn (Builder $query) => $query->leftJoin('products', 'products.id', '=', 'items.product_id'))
            ->when($filter->product !== null, fn (Builder $query) => $query->where('items.product_name', $filter->product))
            ->when($filter->variant !== null, fn (Builder $query) => $query->where('items.variant_name', $filter->variant))
            ->when($filter->categoryId !== null, fn (Builder $query) => $filter->categoryId === 0
                ? $query->whereNull('products.category_id')
                : $query->where('products.category_id', $filter->categoryId));
    }

    /** Filter produk / varian / kategori pada relasi items pesanan (untuk daftar pesanan). */
    private function applyItemFilter(Builder $items, ReportFilter $filter): Builder
    {
        return $items
            ->when($filter->product !== null, fn (Builder $query) => $query->where('product_name', $filter->product))
            ->when($filter->variant !== null, fn (Builder $query) => $query->where('variant_name', $filter->variant))
            ->when($filter->categoryId !== null, fn (Builder $query) => $filter->categoryId === 0
                ? $query->where(fn (Builder $none) => $none->whereNull('product_id')->orWhereHas('product', fn (Builder $product) => $product->whereNull('category_id')))
                : $query->whereHas('product', fn (Builder $product) => $product->where('category_id', $filter->categoryId)));
    }

    /**
     * Nama tampilan per kunci kelompok.
     *
     * @return array<int|string, array{label: string, sub: ?string}>
     */
    private function breakdownLabels(string $by, Collection $keys): array
    {
        $map = fn (Collection $models, callable $label, ?callable $sub = null) => $models
            ->mapWithKeys(fn ($model) => [$model->id => ['label' => $label($model), 'sub' => $sub ? $sub($model) : null]])
            ->all();

        return match ($by) {
            'customer' => $map(Customer::withTrashed()->whereIn('id', $keys)->get(['id', 'code', 'name']), fn ($c) => $c->name, fn ($c) => $c->code),
            'outlet' => $map(Outlets::query()->whereIn('id', $keys)->get(['id', 'name']), fn ($o) => $o->name),
            'category' => $map(Category::withTrashed()->whereIn('id', $keys)->get(['id', 'name']), fn ($c) => $c->name)
                + [0 => ['label' => 'Tanpa kategori', 'sub' => null]],
            default => [],
        };
    }

    /** Pembayaran yang uangnya benar-benar diterima di periode. */
    private function receivedPayments(ReportFilter $filter): Builder
    {
        return Payment::query()
            ->whereBetween('paid_at', $filter->between())
            ->where(fn (Builder $query) => $query
                ->where('status', Payment::STATUS_POSTED)
                ->orWhere(fn (Builder $reversed) => $reversed->where('status', Payment::STATUS_REVERSED)->whereHas('refunds')))
            ->whereHas('allocations.invoice.order', fn (Builder $order) => $order->withTrashed()->whereIn('outlet_id', $filter->outletIds))
            ->when($filter->customerId, fn (Builder $query, int $id) => $query->where('b2b_customer_id', $id));
    }

    /** Pengembalian dana di periode. */
    private function refunds(ReportFilter $filter): Builder
    {
        return Refund::query()
            ->whereBetween('refunded_at', $filter->between())
            ->whereHas('invoice', fn (Builder $invoice) => $invoice
                ->when($filter->customerId, fn (Builder $query, int $id) => $query->where('b2b_customer_id', $id))
                ->whereHas('order', fn (Builder $order) => $order->withTrashed()->whereIn('outlet_id', $filter->outletIds)));
    }

    /** Invoice yang belum lunas per hari ini, tanpa melihat periode. */
    private function openInvoices(ReportFilter $filter): Builder
    {
        return Invoice::query()
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_OVERDUE])
            ->whereHas('order', fn (Builder $order) => $order->whereIn('outlet_id', $filter->outletIds))
            ->when($filter->customerId, fn (Builder $query, int $id) => $query->where('b2b_customer_id', $id));
    }

    private function outstanding(object $row): array
    {
        return ['amount' => (int) $row->amount, 'invoices' => (int) $row->invoices, 'customers' => (int) $row->customers];
    }

    private function bucketLabel(string $group, CarbonImmutable $start, CarbonImmutable $end): string
    {
        if ($group === 'month') {
            return $this->shortMonth($start).' '.$start->year;
        }
        $day = fn (CarbonImmutable $date) => $date->day.' '.$this->shortMonth($date);
        if ($group === 'day' || $start->isSameDay($end)) {
            return $day($start);
        }

        // Minggu: "2–8 Nov", atau "28 Okt–1 Nov" kalau melewati pergantian bulan.
        return $start->isSameMonth($end) ? $start->day.'–'.$day($end) : $day($start).'–'.$day($end);
    }

    private function bucketTitle(string $group, CarbonImmutable $start, CarbonImmutable $end): string
    {
        return match ($group) {
            'day' => $start->locale('id')->translatedFormat('l, j F Y'),
            'week' => $start->isSameDay($end) ? $this->shortDate($start) : $this->shortDate($start).' – '.$this->shortDate($end),
            'month' => $start->locale('id')->translatedFormat('F Y'),
        };
    }

    private function shortDate(CarbonInterface $date): string
    {
        return $date->day.' '.$this->shortMonth($date).' '.$date->year;
    }

    private function shortMonth(CarbonInterface $date): string
    {
        return self::SHORT_MONTHS[$date->month - 1];
    }
}

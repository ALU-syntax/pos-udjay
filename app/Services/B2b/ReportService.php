<?php

namespace App\Services\B2b;

use App\Models\B2b\Contract;
use App\Models\B2b\Customer;
use App\Models\B2b\Invoice;
use App\Models\B2b\Order;
use App\Models\B2b\Payment;
use App\Models\B2b\Refund;
use App\Models\Category;
use App\Models\Outlets;
use App\Models\User;
use App\Support\B2b\ReportFilter;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Angka Laporan B2B. Definisi (disepakati 21 Nov 2026):
 * - Penjualan: grand_total pesanan Dikonfirmasi + Selesai menurut tanggal pesanan. Batal dan draft tidak dihitung.
 * - Kas: uang yang benar-benar diterima (pembayaran posted, atau reversed karena pesanan batal + refund) menurut tanggal bayar,
 *   dikurangi refund menurut tanggal refund. Pelunasan yang dibatalkan karena salah catat tidak dihitung sama sekali.
 * - Piutang: sisa invoice yang belum lunas per hari ini (tidak ikut periode), tetap ikut filter outlet dan customer.
 *   Umur piutang dari jatuh tempo: belum jatuh tempo, 1–30, 31–60, 61–90, > 90 hari.
 * Outlet = outlet pesanan. Rekap kas per outlet memakai outlet penerima pembayaran / refund (kosong = pusat, di luar kasir).
 */
class ReportService
{
    /** Batas panjang periode (hari) supaya chart tetap terbaca: harian s.d. 92 batang, mingguan s.d. 53 batang. */
    public const MAX_DAYS_PER_GROUP = ['day' => 92, 'week' => 371];

    /** Penjualan per dimensi: customer / outlet (dari total pesanan) dan produk / varian / kategori (dari item pesanan). */
    public const BREAKDOWNS = ['customer', 'outlet', 'product', 'variant', 'category'];

    /** Jumlah baris teratas di chart peringkat; sisanya digabung jadi "Lainnya". */
    public const TOP_ITEMS = 10;

    /** Baris per halaman di modal daftar pesanan / invoice. */
    public const PER_PAGE = 15;

    /** Kelompok umur piutang menurut hari lewat jatuh tempo (per hari ini). */
    public const AGING_BUCKETS = [
        'current' => 'Belum jatuh tempo',
        '1-30' => '1–30 hari',
        '31-60' => '31–60 hari',
        '61-90' => '61–90 hari',
        '90+' => '> 90 hari',
    ];

    /** Rekap kas per metode (nama di pembayaran), outlet penerima, atau kasir. */
    public const CASH_GROUPS = ['method', 'outlet', 'cashier'];

    /** Jumlah irisan donut kas; sisanya digabung jadi "Lainnya". */
    public const TOP_CASH_GROUPS = 7;

    /** Kontrak dianggap hampir berakhir bila berakhir dalam sekian hari (sama dengan statistik menu Kontrak). */
    public const CONTRACT_ENDING_DAYS = 30;

    /** Batas baris per daftar di export Excel supaya file tetap ringan; lebih dari itu filter harus dipersempit. */
    public const EXPORT_MAX_ROWS = 20000;

    /** Filter kelompok umur yang diterima daftar customer / invoice: kelompok di atas + semua yang lewat jatuh tempo. */
    public const AGING_FILTERS = ['current', '1-30', '31-60', '61-90', '90+', 'overdue'];

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
            ->paginate(self::PER_PAGE, ['*'], 'page', max(1, $page));

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

    /** Umur piutang per hari ini: sisa tagihan per kelompok umur (kelompok kosong tetap muncul dengan 0). */
    public function aging(ReportFilter $filter): array
    {
        $today = today();
        $bucket = 'case when due_date >= ? then ? when due_date >= ? then ? when due_date >= ? then ? when due_date >= ? then ? else ? end';
        $bindings = [
            $today->toDateString(), 'current',
            $today->copy()->subDays(30)->toDateString(), '1-30',
            $today->copy()->subDays(60)->toDateString(), '31-60',
            $today->copy()->subDays(90)->toDateString(), '61-90',
            '90+',
        ];
        $rows = $this->openInvoices($filter)
            ->selectRaw("{$bucket} as bucket", $bindings)
            ->selectRaw('count(*) as invoices, count(distinct b2b_customer_id) as customers, coalesce(sum(total_to_pay - paid_amount), 0) as amount')
            // Kelompokkan lewat alias: ekspresi dengan placeholder di GROUP BY tidak dianggap sama oleh ONLY_FULL_GROUP_BY.
            ->groupBy('bucket')
            ->toBase()
            ->get()
            ->keyBy('bucket');
        $totals = $this->openInvoices($filter)
            ->selectRaw('count(*) as invoices, count(distinct b2b_customer_id) as customers, coalesce(sum(total_to_pay - paid_amount), 0) as amount')
            ->first();

        return [
            'as_of' => $today->toDateString(),
            'total' => $this->outstanding($totals),
            'buckets' => collect(self::AGING_BUCKETS)->map(fn (string $label, string $key) => [
                'key' => $key,
                'label' => $label,
                'amount' => (int) ($rows[$key]->amount ?? 0),
                'invoices' => (int) ($rows[$key]->invoices ?? 0),
                'customers' => (int) ($rows[$key]->customers ?? 0),
            ])->values()->all(),
        ];
    }

    /** Customer dengan sisa tagihan terbesar (per hari ini), opsional untuk satu kelompok umur: 10 teratas + gabungan sisanya. */
    public function debtors(ReportFilter $filter, ?string $bucket = null, int $limit = self::TOP_ITEMS): array
    {
        $rows = $this->openInvoices($filter, $bucket)
            ->selectRaw('b2b_customer_id, count(*) as invoices, coalesce(sum(total_to_pay - paid_amount), 0) as amount, min(due_date) as oldest_due')
            ->groupBy('b2b_customer_id')
            ->orderByDesc('amount')
            ->orderBy('b2b_customer_id')
            ->toBase()
            ->get();

        $top = $rows->take($limit);
        $rest = $rows->slice($limit);
        $labels = $this->breakdownLabels('customer', $top->pluck('b2b_customer_id'));

        return [
            'bucket' => $bucket,
            'total' => (int) $rows->sum('amount'),
            'items' => $top->map(fn ($row) => [
                'key' => (int) $row->b2b_customer_id,
                'label' => $labels[$row->b2b_customer_id]['label'] ?? '-',
                'sub' => $labels[$row->b2b_customer_id]['sub'] ?? null,
                'amount' => (int) $row->amount,
                'invoices' => (int) $row->invoices,
                'days_overdue' => $this->daysOverdue(CarbonImmutable::parse($row->oldest_due)),
            ])->values()->all(),
            'others' => $rest->isEmpty() ? null : [
                'count' => $rest->count(),
                'amount' => (int) $rest->sum('amount'),
                'invoices' => (int) $rest->sum('invoices'),
            ],
        ];
    }

    /** Daftar invoice belum lunas (per hari ini) untuk modal detail, jatuh tempo terlama dulu. */
    public function invoices(ReportFilter $filter, ?string $bucket = null, int $page = 1): array
    {
        $totals = $this->openInvoices($filter, $bucket)
            ->selectRaw('count(*) as invoices, count(distinct b2b_customer_id) as customers, coalesce(sum(total_to_pay - paid_amount), 0) as amount')
            ->first();

        $paginator = $this->openInvoices($filter, $bucket)
            ->with(['customer:id,code,name', 'order:id,order_number,outlet_id', 'order.outlet:id,name'])
            ->orderBy('due_date')
            ->orderBy('id')
            ->paginate(self::PER_PAGE, ['*'], 'page', max(1, $page));

        return [
            'bucket' => $bucket,
            'totals' => $this->outstanding($totals),
            'items' => collect($paginator->items())->map(fn (Invoice $invoice) => $this->invoiceRow($invoice))->all(),
            'page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    /**
     * Rekap kas di periode per metode / outlet penerima / kasir: uang masuk, refund, dan bersihnya.
     * Uang masuk dan refund dikelompokkan dengan datanya masing-masing (metode, outlet, dan kasir refund bisa berbeda
     * dari pembayarannya), jadi angka bersih per kelompok = kas yang benar-benar dipegang kelompok itu.
     */
    public function cashflow(ReportFilter $filter, string $by, int $limit = self::TOP_CASH_GROUPS): array
    {
        [$paymentKey, $refundKey] = $this->cashKeys($by);
        $totals = fn (Builder $query, string $key) => $query
            ->selectRaw("{$key} as group_key, count(*) as total, coalesce(sum(amount), 0) as amount")
            ->groupByRaw($key)
            ->toBase()
            ->get()
            ->keyBy(fn ($row) => (string) $row->group_key);
        $received = $totals($this->receivedPayments($filter), $paymentKey);
        $refunded = $totals($this->refunds($filter), $refundKey);

        $rows = $received->keys()->merge($refunded->keys())->unique()->map(fn (string $key) => (object) [
            'key' => $key,
            'received' => (int) ($received[$key]->amount ?? 0),
            'received_count' => (int) ($received[$key]->total ?? 0),
            'refunded' => (int) ($refunded[$key]->amount ?? 0),
            'refund_count' => (int) ($refunded[$key]->total ?? 0),
        ])->sortBy([['received', 'desc'], ['refunded', 'desc'], ['key', 'asc']])->values();

        $top = $rows->take($limit);
        $rest = $rows->slice($limit);
        $labels = $this->cashLabels($by, $top->pluck('key'));
        $sum = fn (Collection $items) => [
            'received' => (int) $items->sum('received'),
            'received_count' => (int) $items->sum('received_count'),
            'refunded' => (int) $items->sum('refunded'),
            'refund_count' => (int) $items->sum('refund_count'),
            'net' => (int) $items->sum('received') - (int) $items->sum('refunded'),
        ];

        return [
            'by' => $by,
            'totals' => $sum($rows),
            'items' => $top->map(fn ($row) => [
                'key' => $by === 'method' ? $row->key : (int) $row->key,
                'label' => $labels[$row->key]['label'] ?? $row->key,
                'sub' => $labels[$row->key]['sub'] ?? null,
                'received' => $row->received,
                'received_count' => $row->received_count,
                'refunded' => $row->refunded,
                'refund_count' => $row->refund_count,
                'net' => $row->received - $row->refunded,
            ])->all(),
            'others' => $rest->isEmpty() ? null : ['count' => $rest->count()] + $sum($rest),
        ];
    }

    /**
     * Mutasi kas di periode untuk modal detail: pembayaran (+) dan refund (−) digabung, terbaru dulu.
     * Opsional hanya satu kelompok rekap kas ($by + $key, mis. outlet 0 = pusat / kasir 0 = tidak tercatat).
     */
    public function mutations(ReportFilter $filter, ?string $by = null, ?string $key = null, int $page = 1): array
    {
        $payments = $this->cashGroup($this->receivedPayments($filter), $by, $key, 'payment');
        $refunds = $this->cashGroup($this->refunds($filter), $by, $key, 'refund');
        $count = fn (Builder $query) => $query->clone()->selectRaw('count(*) as total, coalesce(sum(amount), 0) as amount')->toBase()->first();
        $received = $count($payments);
        $refunded = $count($refunds);
        $paginator = $this->mutationQuery($payments, $refunds)->paginate(self::PER_PAGE, ['*'], 'page', max(1, $page));

        return [
            'by' => $by,
            'totals' => [
                'received' => (int) $received->amount,
                'received_count' => (int) $received->total,
                'refunded' => (int) $refunded->amount,
                'refund_count' => (int) $refunded->total,
                'net' => (int) $received->amount - (int) $refunded->amount,
            ],
            'items' => $this->mutationItems(collect($paginator->items())),
            'page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    /**
     * Kontrak berjalan yang berakhir dalam 30 hari (per hari ini, tidak ikut periode dan outlet karena kontrak per customer),
     * berakhir paling dekat dulu. Disertai kontrak lanjutan customer itu (kalau sudah dibuat) dan penjualan selama kontrak
     * (pesanan dikonfirmasi + selesai di outlet terpilih, dari tanggal mulai sampai hari ini).
     */
    public function contracts(ReportFilter $filter): array
    {
        $today = today();
        $contracts = Contract::query()
            ->with('customer:id,code,name')
            ->effectiveOn($today)
            ->whereDate('end_date', '<=', $today->copy()->addDays(self::CONTRACT_ENDING_DAYS))
            ->when($filter->customerId, fn (Builder $query, int $id) => $query->where('b2b_customer_id', $id))
            ->orderBy('end_date')
            ->orderBy('id')
            ->get();

        // Kontrak lanjutan = kontrak aktif berikutnya milik customer yang sama (periode kontrak tidak boleh tumpang tindih).
        $successors = Contract::query()
            ->where('status', Contract::STATUS_ACTIVE)
            ->whereIn('b2b_customer_id', $contracts->pluck('b2b_customer_id')->unique())
            ->whereDate('start_date', '>', $today)
            ->orderBy('start_date')
            ->get(['id', 'b2b_customer_id', 'contract_number', 'start_date', 'end_date'])
            ->groupBy('b2b_customer_id');

        $items = $contracts->map(function (Contract $contract) use ($filter, $successors, $today) {
            $next = $successors->get($contract->b2b_customer_id)?->first(fn (Contract $next) => $next->start_date->gt($contract->end_date));
            $sales = Order::query()
                ->whereIn('outlet_id', $filter->outletIds)
                ->where('b2b_customer_id', $contract->b2b_customer_id)
                ->whereIn('status', [Order::STATUS_CONFIRMED, Order::STATUS_COMPLETED])
                ->whereBetween('order_date', [$contract->start_date->copy()->startOfDay(), $today->copy()->endOfDay()])
                ->selectRaw('count(*) as orders, coalesce(sum(grand_total), 0) as amount')
                ->first();

            return [
                'id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'customer_id' => $contract->b2b_customer_id,
                'customer' => $contract->customer?->name,
                'customer_code' => $contract->customer?->code,
                'start_date' => $this->shortDate($contract->start_date),
                'end_date' => $this->shortDate($contract->end_date),
                'end_at' => $contract->end_date->toDateString(),
                'days_left' => (int) $today->diffInDays($contract->end_date, false),
                'successor' => $next ? [
                    'id' => $next->id,
                    'contract_number' => $next->contract_number,
                    'start_date' => $this->shortDate($next->start_date),
                ] : null,
                'sales' => ['amount' => (int) $sales->amount, 'orders' => (int) $sales->orders],
            ];
        });

        return [
            'as_of' => $today->toDateString(),
            'days' => self::CONTRACT_ENDING_DAYS,
            'total' => $items->count(),
            'without_successor' => $items->whereNull('successor')->count(),
            'items' => $items->values()->all(),
        ];
    }

    /**
     * Kartu piutang satu customer ($filter->customerId wajib): ringkasan, invoice belum lunas per hari ini,
     * dan mutasi tagihan di periode dengan saldo berjalan.
     */
    public function customerCard(ReportFilter $filter): array
    {
        $outstanding = 'count(*) as invoices, count(distinct b2b_customer_id) as customers, coalesce(sum(total_to_pay - paid_amount), 0) as amount';
        $sales = $this->salesOrders($filter)->selectRaw('count(*) as orders, coalesce(sum(grand_total), 0) as amount')->first();
        $received = (int) $this->receivedPayments($filter)->sum('amount');
        $refunded = (int) $this->refunds($filter)->sum('amount');
        $open = $this->openInvoices($filter)
            ->with(['customer:id,code,name', 'order:id,order_number,outlet_id', 'order.outlet:id,name'])
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        return [
            'as_of' => today()->toDateString(),
            'receivable' => $this->outstanding($this->openInvoices($filter)->selectRaw($outstanding)->first()),
            'overdue' => $this->outstanding($this->openInvoices($filter, 'overdue')->selectRaw($outstanding)->first()),
            'oldest_days_overdue' => $open->isEmpty() ? null : max(0, $this->daysOverdue(CarbonImmutable::parse($open->first()->due_date))),
            'sales' => ['amount' => (int) $sales->amount, 'orders' => (int) $sales->orders],
            'cash' => ['received' => $received, 'refunded' => $refunded, 'net' => $received - $refunded],
            'open_invoices' => $open->map(fn (Invoice $invoice) => $this->invoiceRow($invoice))->all(),
            'ledger' => $this->ledger($filter),
        ];
    }

    /**
     * Mutasi piutang customer di periode. Tagihan (+): invoice terbit, refund (uang dikembalikan). Pembayaran (−): uang diterima,
     * invoice dibatalkan. Pelunasan salah catat yang dibatalkan tidak dihitung sama sekali (sama dengan definisi kas),
     * jadi saldo per hari ini = sisa invoice yang belum lunas.
     */
    private function ledger(ReportFilter $filter): array
    {
        [$start, $end] = $filter->between();
        $invoices = fn () => Invoice::query()
            ->where('b2b_customer_id', $filter->customerId)
            ->whereHas('order', fn (Builder $order) => $order->whereIn('outlet_id', $filter->outletIds));
        $payments = fn () => Payment::query()
            ->where('b2b_customer_id', $filter->customerId)
            ->where(fn (Builder $query) => $query
                ->where('status', Payment::STATUS_POSTED)
                ->orWhere(fn (Builder $reversed) => $reversed->where('status', Payment::STATUS_REVERSED)->whereHas('refunds')))
            ->whereHas('allocations.invoice.order', fn (Builder $order) => $order->withTrashed()->whereIn('outlet_id', $filter->outletIds));
        $refunds = fn () => Refund::query()
            ->whereHas('invoice', fn (Builder $invoice) => $invoice
                ->where('b2b_customer_id', $filter->customerId)
                ->whereHas('order', fn (Builder $order) => $order->withTrashed()->whereIn('outlet_id', $filter->outletIds)));
        $voided = fn () => $invoices()->where('status', Invoice::STATUS_VOID)->whereNotNull('voided_at');

        $opening = (int) $invoices()->whereDate('issue_date', '<', $start)->sum('total_to_pay')
            - (int) $voided()->where('voided_at', '<', $start)->sum('total_to_pay')
            - (int) $payments()->where('paid_at', '<', $start)->sum('amount')
            + (int) $refunds()->where('refunded_at', '<', $start)->sum('amount');

        // [waktu urut, urutan jenis pada hari yang sama, baris]
        $entries = collect();
        foreach ($invoices()->with('order:id,order_number')->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])->get() as $invoice) {
            $entries->push([CarbonImmutable::parse($invoice->issue_date), 0, [
                'type' => 'invoice', 'number' => $invoice->invoice_number, 'note' => $invoice->order?->order_number,
                'order_id' => $invoice->b2b_order_id, 'debit' => (int) $invoice->total_to_pay, 'credit' => 0,
            ]]);
        }
        foreach ($payments()->with('allocations.invoice:id,invoice_number,b2b_order_id')->whereBetween('paid_at', [$start, $end])->get() as $payment) {
            $invoice = $payment->allocations->first()?->invoice;
            $entries->push([CarbonImmutable::parse($payment->paid_at), 1, [
                'type' => 'payment', 'number' => $payment->payment_number,
                'note' => trim($payment->method_name.' · '.($invoice?->invoice_number ?? ''), ' ·'),
                'order_id' => $invoice?->b2b_order_id, 'debit' => 0, 'credit' => (int) $payment->amount,
            ]]);
        }
        foreach ($voided()->whereBetween('voided_at', [$start, $end])->get() as $invoice) {
            $entries->push([CarbonImmutable::parse($invoice->voided_at), 2, [
                'type' => 'void', 'number' => $invoice->invoice_number, 'note' => $invoice->void_reason,
                'order_id' => $invoice->b2b_order_id, 'debit' => 0, 'credit' => (int) $invoice->total_to_pay,
            ]]);
        }
        foreach ($refunds()->with(['payment:id,payment_number', 'invoice:id,b2b_order_id'])->whereBetween('refunded_at', [$start, $end])->get() as $refund) {
            $entries->push([CarbonImmutable::parse($refund->refunded_at), 3, [
                'type' => 'refund', 'number' => $refund->payment?->payment_number, 'note' => $refund->method_name,
                'order_id' => $refund->invoice?->b2b_order_id, 'debit' => (int) $refund->amount, 'credit' => 0,
            ]]);
        }

        $balance = $opening;
        $rows = $entries
            ->sortBy([fn ($a, $b) => $a[0]->toDateString() <=> $b[0]->toDateString(), fn ($a, $b) => $a[1] <=> $b[1], fn ($a, $b) => $a[0] <=> $b[0]])
            ->map(function (array $entry) use (&$balance) {
                $balance += $entry[2]['debit'] - $entry[2]['credit'];

                return ['date' => $this->shortDate($entry[0]), 'at' => $entry[0]->toDateString()] + $entry[2] + ['balance' => $balance];
            })
            ->values();

        return [
            'from' => $this->shortDate($filter->from),
            'to' => $this->shortDate($filter->to),
            'from_at' => $filter->from->toDateString(),
            'to_at' => $filter->to->toDateString(),
            'opening' => $opening,
            'debit' => (int) $rows->sum('debit'),
            'credit' => (int) $rows->sum('credit'),
            'closing' => $balance,
            'rows' => $rows->all(),
        ];
    }

    private function invoiceRow(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'order_id' => $invoice->b2b_order_id,
            'order_number' => $invoice->order?->order_number,
            'issue_date' => $this->shortDate($invoice->issue_date),
            'due_date' => $this->shortDate($invoice->due_date),
            'issue_at' => $invoice->issue_date->toDateString(),
            'due_at' => $invoice->due_date->toDateString(),
            'days_overdue' => $this->daysOverdue(CarbonImmutable::parse($invoice->due_date)),
            'customer' => $invoice->customer?->name,
            'customer_code' => $invoice->customer?->code,
            'outlet' => $invoice->order?->outlet?->name,
            'status' => $invoice->statusLabel(),
            'status_variant' => $invoice->statusVariant(),
            'total' => (int) $invoice->total_to_pay,
            'paid' => (int) $invoice->paid_amount,
            'outstanding' => $invoice->outstandingAmount(),
        ];
    }

    /**
     * Data lengkap untuk export Excel Laporan B2B (filter sama dengan halaman): ringkasan + daftar mentah tanpa batas
     * "10 teratas" atau halaman, supaya finance bisa membuat pivot sendiri. Daftar yang melebihi EXPORT_MAX_ROWS
     * dikembalikan dengan satu baris lebih (penanda) dan ditolak oleh controller.
     */
    public function exportData(ReportFilter $filter): array
    {
        $limit = self::EXPORT_MAX_ROWS + 1;
        $orders = $this->salesOrders($filter)
            ->with(['customer:id,code,name', 'outlet:id,name'])
            ->orderBy('order_date')
            ->orderBy('id')
            ->limit($limit)
            ->get();
        $items = $this->salesItems($filter, true)
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('b2b_customers', 'b2b_customers.id', '=', 'b2b_orders.b2b_customer_id')
            ->leftJoin('outlets', 'outlets.id', '=', 'b2b_orders.outlet_id')
            ->select([
                'b2b_orders.order_number', 'b2b_orders.order_date', 'b2b_customers.name as customer', 'outlets.name as outlet',
                'items.product_name', 'items.variant_name', 'categories.name as category', 'items.qty', 'items.base_price',
                'items.final_price', 'items.b2b_contract_item_id', 'items.line_total',
            ])
            ->orderBy('b2b_orders.order_date')
            ->orderBy('b2b_orders.id')
            ->orderBy('items.id')
            ->limit($limit)
            ->toBase()
            ->get();
        // Rekap item terjual: dikelompokkan seperti tab Produk (nama produk + varian di pesanan) ditambah kategori produk saat ini.
        $category = "coalesce(categories.name, 'Tanpa kategori')";
        $soldItems = $this->salesItems($filter, true)
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw("items.product_name, items.variant_name, {$category} as category")
            ->selectRaw('coalesce(sum(items.qty), 0) as qty, count(distinct b2b_orders.id) as orders, count(distinct b2b_orders.b2b_customer_id) as customers')
            ->selectRaw('coalesce(sum(items.line_total), 0) as amount')
            ->groupBy('items.product_name', 'items.variant_name')
            ->groupByRaw($category)
            ->orderByDesc('amount')
            ->orderBy('items.product_name')
            ->orderBy('items.variant_name')
            ->toBase()
            ->get();
        $receivables = $this->openInvoices($filter)
            ->with(['customer:id,code,name', 'order:id,order_number,outlet_id', 'order.outlet:id,name'])
            ->orderBy('due_date')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn (Invoice $invoice) => $this->invoiceRow($invoice) + ['bucket' => $this->agingBucketLabel($invoice)]);
        $mutations = $this->mutationQuery($this->receivedPayments($filter), $this->refunds($filter), oldestFirst: true)->limit($limit)->get();

        return [
            'summary' => $this->summary($filter),
            'aging' => $this->aging($filter),
            'cash' => collect(self::CASH_GROUPS)->mapWithKeys(fn (string $by) => [$by => $this->cashflow($filter, $by, PHP_INT_MAX)])->all(),
            'orders' => $orders->map(fn (Order $order) => [
                'order_number' => $order->order_number,
                'order_at' => $order->order_date->toDateTimeString(),
                'customer' => $order->customer?->name,
                'customer_code' => $order->customer?->code,
                'outlet' => $order->outlet?->name,
                'payment_type' => $order->paymentTypeLabel(),
                'status' => $order->statusLabel(),
                'total' => (int) $order->grand_total,
            ])->all(),
            'sold_items' => $soldItems->map(fn ($item) => [
                'product' => $item->product_name,
                'variant' => $item->variant_name,
                'category' => $item->category,
                'qty' => (int) $item->qty,
                'orders' => (int) $item->orders,
                'customers' => (int) $item->customers,
                'average_price' => $item->qty ? (int) round($item->amount / $item->qty) : 0,
                'amount' => (int) $item->amount,
            ])->all(),
            'items' => $items->map(fn ($item) => [
                'order_number' => $item->order_number,
                'order_at' => (string) $item->order_date,
                'customer' => $item->customer,
                'outlet' => $item->outlet,
                'product' => $item->product_name,
                'variant' => $item->variant_name,
                'category' => $item->category ?? 'Tanpa kategori',
                'qty' => (int) $item->qty,
                'base_price' => (int) $item->base_price,
                'price' => (int) $item->final_price,
                'contract_price' => $item->b2b_contract_item_id !== null,
                'total' => (int) $item->line_total,
            ])->all(),
            'receivables' => $receivables->all(),
            'mutations' => $this->mutationItems($mutations),
            'contracts' => $this->contracts($filter)['items'],
        ];
    }

    /** Label kelompok umur satu invoice per hari ini (lihat AGING_BUCKETS). */
    private function agingBucketLabel(Invoice $invoice): string
    {
        $days = $this->daysOverdue(CarbonImmutable::parse($invoice->due_date));

        return self::AGING_BUCKETS[match (true) {
            $days <= 0 => 'current',
            $days <= 30 => '1-30',
            $days <= 60 => '31-60',
            $days <= 90 => '61-90',
            default => '90+',
        }];
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

    /** Pembayaran (+) dan refund (−) digabung jadi satu daftar mutasi: terbaru dulu (modal) atau terlama dulu (export). */
    private function mutationQuery(Builder $payments, Builder $refunds, bool $oldestFirst = false): QueryBuilder
    {
        $union = $payments->clone()
            ->selectRaw("'payment' as type, id, paid_at as at, amount, b2b_customer_id as customer_id, method_name, outlet_id, received_by as user_id, payment_number as number, status")
            ->selectRaw('(select min(a.b2b_invoice_id) from b2b_payment_allocations a where a.b2b_payment_id = b2b_payments.id) as invoice_id')
            ->toBase()
            ->unionAll($refunds->clone()
                ->selectRaw("'refund' as type, id, refunded_at as at, -amount as amount")
                ->selectRaw('(select i.b2b_customer_id from b2b_invoices i where i.id = b2b_refunds.b2b_invoice_id) as customer_id')
                ->selectRaw('method_name, outlet_id, refunded_by as user_id')
                ->selectRaw('(select p.payment_number from b2b_payments p where p.id = b2b_refunds.b2b_payment_id) as number')
                ->selectRaw('null as status, b2b_invoice_id as invoice_id')
                ->toBase());

        $direction = $oldestFirst ? 'asc' : 'desc';

        return DB::query()->fromSub($union, 'mutations')->orderBy('at', $direction)->orderBy('type', $direction)->orderBy('id', $direction);
    }

    /** Baris mutasi kas siap tampil (nama customer, invoice, outlet, kasir). */
    private function mutationItems(Collection $rows): array
    {
        $customers = Customer::withTrashed()->whereIn('id', $rows->pluck('customer_id')->filter())->pluck('name', 'id');
        $invoices = Invoice::withTrashed()->whereIn('id', $rows->pluck('invoice_id')->filter())->get(['id', 'invoice_number', 'b2b_order_id'])->keyBy('id');
        $outlets = $this->cashLabels('outlet', $rows->pluck('outlet_id')->map(fn ($id) => (string) (int) $id));
        $users = $this->cashLabels('cashier', $rows->pluck('user_id')->map(fn ($id) => (string) (int) $id));

        return $rows->map(function ($row) use ($customers, $invoices, $outlets, $users) {
            $at = CarbonImmutable::parse($row->at);
            $invoice = $invoices[$row->invoice_id] ?? null;

            return [
                'type' => $row->type,
                'id' => (int) $row->id,
                'number' => $row->number,
                'date' => $this->shortDate($at).' '.$at->format('H:i'),
                'at' => $at->toDateTimeString(),
                'customer' => $customers[$row->customer_id] ?? null,
                'invoice_number' => $invoice?->invoice_number,
                'order_id' => $invoice?->b2b_order_id,
                'method' => $row->method_name,
                'outlet' => $outlets[(int) $row->outlet_id]['label'] ?? null,
                'cashier' => $users[(int) $row->user_id]['label'] ?? null,
                'amount' => (int) $row->amount,
                // Pembayaran yang uangnya sudah dikembalikan (pesanan batal + refund) tetap terhitung sebagai uang masuk.
                'refunded' => $row->type === 'payment' && $row->status === Payment::STATUS_REVERSED,
            ];
        })->values()->all();
    }

    /**
     * Kolom pengelompokan rekap kas untuk [pembayaran, refund]. Outlet / kasir kosong digabung ke 0.
     *
     * @return array{0: string, 1: string}
     */
    private function cashKeys(string $by): array
    {
        return match ($by) {
            'method' => ['method_name', 'method_name'],
            'outlet' => ['coalesce(outlet_id, 0)', 'coalesce(outlet_id, 0)'],
            'cashier' => ['coalesce(received_by, 0)', 'coalesce(refunded_by, 0)'],
        };
    }

    /** Batasi pembayaran / refund ke satu kelompok rekap kas. */
    private function cashGroup(Builder $query, ?string $by, ?string $key, string $type): Builder
    {
        if ($by === null || $key === null) {
            return $query;
        }
        $column = match ($by) {
            'method' => 'method_name',
            'outlet' => 'outlet_id',
            'cashier' => $type === 'payment' ? 'received_by' : 'refunded_by',
        };
        if ($by === 'method') {
            return $query->where($column, $key);
        }

        return (int) $key === 0 ? $query->whereNull($column) : $query->where($column, (int) $key);
    }

    /**
     * Nama tampilan kelompok rekap kas; outlet 0 = pusat (pelunasan di luar kasir), kasir 0 = tidak tercatat.
     *
     * @return array<int|string, array{label: string, sub: ?string}>
     */
    private function cashLabels(string $by, Collection $keys): array
    {
        $ids = $keys->map(fn ($key) => (int) $key)->filter()->unique()->values();

        return match ($by) {
            'outlet' => Outlets::query()->whereIn('id', $ids)->pluck('name', 'id')->map(fn ($name) => ['label' => $name, 'sub' => null])->all()
                + [0 => ['label' => 'Pusat', 'sub' => 'Pelunasan di luar kasir']],
            'cashier' => User::query()->whereIn('id', $ids)->pluck('name', 'id')->map(fn ($name) => ['label' => $name, 'sub' => null])->all()
                + [0 => ['label' => 'Tidak tercatat', 'sub' => null]],
            default => [],
        };
    }

    /** Invoice yang belum lunas per hari ini, tanpa melihat periode; opsional hanya satu kelompok umur (lihat AGING_FILTERS). */
    private function openInvoices(ReportFilter $filter, ?string $bucket = null): Builder
    {
        $today = today();
        $daysAgo = fn (int $days) => $today->copy()->subDays($days)->toDateString();

        return Invoice::query()
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_OVERDUE])
            ->whereHas('order', fn (Builder $order) => $order->whereIn('outlet_id', $filter->outletIds))
            ->when($filter->customerId, fn (Builder $query, int $id) => $query->where('b2b_customer_id', $id))
            ->when($bucket, fn (Builder $query, string $bucket) => match ($bucket) {
                'current' => $query->whereDate('due_date', '>=', $today),
                'overdue' => $query->whereDate('due_date', '<', $today),
                '1-30' => $query->whereBetween('due_date', [$daysAgo(30), $daysAgo(1)]),
                '31-60' => $query->whereBetween('due_date', [$daysAgo(60), $daysAgo(31)]),
                '61-90' => $query->whereBetween('due_date', [$daysAgo(90), $daysAgo(61)]),
                '90+' => $query->whereDate('due_date', '<', $daysAgo(90)),
            });
    }

    /** Hari lewat jatuh tempo per hari ini: positif = terlambat, 0 = jatuh tempo hari ini, negatif = sisa hari. */
    private function daysOverdue(CarbonInterface $due): int
    {
        return (int) $due->startOfDay()->diffInDays(today(), false);
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

<?php

namespace Tests\Feature\B2b;

use App\Models\B2b\Customer;
use App\Models\B2b\Order;
use App\Models\CategoryPayment;
use App\Models\Menu;
use App\Models\Outlets;
use App\Models\User;
use App\Models\VariantProduct;
use App\Services\B2b\OrderService;
use App\Services\B2b\PaymentService;
use Database\Seeders\B2bMenuSeeder;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;

class ReportHttpTest extends B2bTestCase
{
    private Outlets $outlet;

    private Outlets $otherOutlet;

    private User $staff;

    private CategoryPayment $cash;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outlet = $this->outlet('A');
        $this->otherOutlet = $this->outlet('B');
        $this->staff = $this->userFor($this->outlet, ['read b2b/reports']);
        [$this->cash] = $this->paymentCategory('Cash');
    }

    private function place(Customer $customer, int $price, array $overrides = [], ?Outlets $outlet = null): Order
    {
        $outlet ??= $this->outlet;

        return app(OrderService::class)->place($this->staff, array_merge([
            'outlet_id' => $outlet->id,
            'b2b_customer_id' => $customer->id,
            'items' => [['variant_id' => $this->variant($outlet, $price)->id, 'qty' => 1]],
            'delivery_method' => 'pickup',
            'due_date' => today()->addDays(7)->toDateString(),
        ], $overrides))->load('invoice');
    }

    private function summary(array $query = []): array
    {
        return $this->section('summary', $query);
    }

    private function section(string $section, array $query = []): array
    {
        return $this->actingAs($this->staff)
            ->getJson(route('b2b/reports/data', ['section' => $section] + $query))
            ->assertOk()
            ->json('data');
    }

    /**
     * Data contoh: X punya pesanan tempo (sebagian dibayar, lewat jatuh tempo), pesanan bulan lalu, dan pesanan batal;
     * Y bayar langsung dan punya pesanan lunas yang dibatalkan dengan refund. Outlet lain tidak boleh ikut terhitung.
     *
     * @return array{0: Customer, 1: Customer}
     */
    private function seedOrders(): array
    {
        $x = $this->customer(['name' => 'PT X']);
        $y = $this->customer(['name' => 'PT Y']);
        $payments = app(PaymentService::class);

        // X: tempo 20.000 masih dikonfirmasi, dibayar 8.000; pelunasan 5.000 salah catat lalu dibatalkan.
        $credit = $this->place($x, 20000);
        $credit->forceFill(['status' => Order::STATUS_CONFIRMED, 'completed_at' => null])->save();
        $wrong = $payments->record($this->staff, $credit->invoice, ['category_payment_id' => $this->cash->id, 'amount' => 5000]);
        $payments->reverse($this->staff, $wrong, 'Salah catat');
        $payments->record($this->staff, $credit->invoice, ['category_payment_id' => $this->cash->id, 'amount' => 8000]);
        $credit->invoice->forceFill(['due_date' => today()->subDay()])->save();

        // X: pesanan dua bulan lalu (di luar periode default), belum dibayar dan belum jatuh tempo.
        $old = $this->place($x, 40000);
        $old->forceFill(['order_date' => today()->subMonthsNoOverflow(2)->setTime(10, 0)])->save();
        $old->invoice->forceFill(['due_date' => today()->addDays(3)])->save();

        // X: pesanan tempo dibatalkan (tanpa pembayaran).
        app(OrderService::class)->cancel($this->staff, $this->place($x, 10000), 'Salah input');

        // Y: bayar langsung 30.000.
        $this->place($y, 30000, ['payment_type' => Order::PAYMENT_IMMEDIATE, 'payment' => ['category_payment_id' => $this->cash->id]]);

        // Y: bayar langsung 15.000 lalu dibatalkan dengan refund.
        $refunded = $this->place($y, 15000, ['payment_type' => Order::PAYMENT_IMMEDIATE, 'payment' => ['category_payment_id' => $this->cash->id]]);
        app(OrderService::class)->cancel($this->staff, $refunded, 'Customer batal', ['category_payment_id' => $this->cash->id]);

        // Outlet lain (user tidak punya akses).
        $this->place($x, 50000, ['payment_type' => Order::PAYMENT_IMMEDIATE, 'payment' => ['category_payment_id' => $this->cash->id]], $this->otherOutlet);
        $this->place($x, 70000, [], $this->otherOutlet);

        return [$x, $y];
    }

    public function test_seeder_registers_report_menu_and_permission(): void
    {
        $this->seed(B2bMenuSeeder::class);

        $this->assertSame(1, Menu::where('url', 'b2b/reports')->count());
        $this->assertDatabaseHas('permissions', ['name' => 'read b2b/reports']);
    }

    public function test_page_and_data_require_read_permission(): void
    {
        $guest = $this->userFor($this->outlet);
        $this->actingAs($guest)->get(route('b2b/reports'))->assertForbidden();
        $this->actingAs($guest)->getJson(route('b2b/reports/data', ['section' => 'summary']))->assertForbidden();

        $this->actingAs($this->staff)->get(route('b2b/reports'))
            ->assertOk()
            ->assertSee('Laporan B2B')
            ->assertSee($this->outlet->name)
            ->assertDontSee($this->otherOutlet->name);
    }

    public function test_page_keeps_valid_filters_from_url_and_ignores_invalid_ones(): void
    {
        $customer = $this->customer(['name' => 'PT Dari URL']);

        $this->actingAs($this->staff)
            ->get(route('b2b/reports', ['from' => '2026-10-01', 'to' => '2026-10-31', 'customer_id' => $customer->id, 'outlet_id' => $this->otherOutlet->id]))
            ->assertOk()
            ->assertSee('PT Dari URL')
            ->assertSee('"from":"2026-10-01","to":"2026-10-31","customer_id":'.$customer->id, false);

        // Tanggal rusak / outlet tanpa akses kembali ke nilai bawaan, halaman tetap terbuka.
        $this->actingAs($this->staff)
            ->get(route('b2b/reports', ['from' => '2026-02-31', 'outlet_id' => $this->otherOutlet->id]))
            ->assertOk()
            ->assertSee('"from":"'.today()->startOfMonth()->toDateString().'"', false);
    }

    public function test_summary_follows_agreed_definitions(): void
    {
        $this->seedOrders();

        $data = $this->summary();

        // Penjualan: pesanan dikonfirmasi + selesai di periode; batal, bulan lalu, dan outlet lain tidak ikut.
        $this->assertSame(['amount' => 50000, 'orders' => 2, 'customers' => 2, 'completed' => 1, 'confirmed' => 1], $data['sales']);
        $this->assertSame(['amount' => 25000, 'orders' => 2], $data['cancelled']);
        // Kas: 30.000 + 15.000 (diterima lalu direfund) + 8.000; pelunasan salah catat tidak ikut. Refund mengurangi sekali saja.
        $this->assertSame(['received' => 53000, 'received_count' => 3, 'refunded' => 15000, 'refund_count' => 1, 'net' => 38000], $data['cash']);
        // Piutang per hari ini: sisa 12.000 + pesanan bulan lalu 40.000; invoice batal dan outlet lain tidak ikut.
        $this->assertSame(['amount' => 52000, 'invoices' => 2, 'customers' => 1], $data['receivable']);
        $this->assertSame(['amount' => 12000, 'invoices' => 1, 'customers' => 1], $data['overdue']);
    }

    public function test_summary_filters_by_period_customer_and_outlet(): void
    {
        [$x, $y] = $this->seedOrders();
        $lastMonths = [
            'from' => today()->subMonthsNoOverflow(2)->startOfMonth()->toDateString(),
            'to' => today()->subMonthsNoOverflow(2)->endOfMonth()->toDateString(),
        ];

        // Periode hanya mengubah penjualan/kas; piutang tetap per hari ini.
        $old = $this->summary($lastMonths);
        $this->assertSame(40000, $old['sales']['amount']);
        $this->assertSame(0, $old['cash']['received']);
        $this->assertSame(52000, $old['receivable']['amount']);

        $onlyY = $this->summary(['customer_id' => $y->id]);
        $this->assertSame(30000, $onlyY['sales']['amount']);
        $this->assertSame(15000, $onlyY['cancelled']['amount']);
        $this->assertSame(30000, $onlyY['cash']['net']);
        $this->assertSame(0, $onlyY['receivable']['amount']);

        $this->assertSame(20000, $this->summary(['customer_id' => $x->id, 'outlet_id' => $this->outlet->id])['sales']['amount']);
    }

    public function test_trend_groups_sales_by_day_week_and_month(): void
    {
        $this->travelTo(Carbon::parse('2026-11-21 10:00'));
        $this->seedOrders();

        // Periode bawaan 1–21 Nov (21 hari) otomatis per hari; hari tanpa penjualan tetap ada dengan nilai 0.
        $daily = $this->section('trend');
        $this->assertSame('day', $daily['group']);
        $this->assertSame(['day', 'week', 'month'], $daily['groups']);
        $this->assertCount(21, $daily['buckets']);
        $this->assertSame(['from' => '2026-11-01', 'to' => '2026-11-01', 'label' => '1 Nov', 'title' => 'Minggu, 1 November 2026', 'amount' => 0, 'orders' => 0], $daily['buckets'][0]);
        $this->assertSame(['amount' => 50000, 'orders' => 2], array_intersect_key($daily['buckets'][20], ['amount' => 0, 'orders' => 0]));
        $this->assertSame(50000, $daily['total']);

        // Minggu Senin–Minggu, dipotong mengikuti periode.
        $weekly = $this->section('trend', ['group' => 'week']);
        $this->assertSame('week', $weekly['group']);
        $this->assertSame(
            [['2026-11-01', '2026-11-01', '1 Nov'], ['2026-11-02', '2026-11-08', '2–8 Nov'], ['2026-11-09', '2026-11-15', '9–15 Nov'], ['2026-11-16', '2026-11-21', '16–21 Nov']],
            array_map(fn ($bucket) => [$bucket['from'], $bucket['to'], $bucket['label']], $weekly['buckets'])
        );
        $this->assertSame(50000, $weekly['buckets'][3]['amount']);
        $this->assertSame('28 Okt–1 Nov', $this->section('trend', ['group' => 'week', 'from' => '2026-10-28', 'to' => '2026-11-03'])['buckets'][0]['label']);

        // Periode > 1 tahun hanya bisa per bulan; permintaan per hari kembali ke per bulan.
        $monthly = $this->section('trend', ['group' => 'day', 'from' => '2025-11-01', 'to' => '2026-11-21']);
        $this->assertSame('month', $monthly['group']);
        $this->assertSame(['month'], $monthly['groups']);
        $this->assertCount(13, $monthly['buckets']);
        $this->assertSame(['Nov 2025', 'November 2025'], [$monthly['buckets'][0]['label'], $monthly['buckets'][0]['title']]);
        $this->assertSame(40000, $monthly['buckets'][10]['amount']); // pesanan 21 Sep 2026
        $this->assertSame(['2026-11-01', '2026-11-21', 50000], [$monthly['buckets'][12]['from'], $monthly['buckets'][12]['to'], $monthly['buckets'][12]['amount']]);
        $this->assertSame(90000, $monthly['total']);
    }

    public function test_breakdown_by_customer_lists_top_customers_and_groups_the_rest(): void
    {
        [$x, $y] = $this->seedOrders();

        $data = $this->section('breakdown', ['by' => 'customer']);
        $this->assertSame(['customer', 50000], [$data['by'], $data['total']]);
        $this->assertSame([[$y->id, 'PT Y', 30000, 1, null], [$x->id, 'PT X', 20000, 1, null]], array_map(
            fn ($item) => [$item['key'], $item['label'], $item['amount'], $item['orders'], $item['qty']], $data['items']
        ));
        $this->assertNull($data['others']);

        // Lebih dari 10 customer: sisanya digabung jadi "Lainnya".
        foreach (range(1, 10) as $i) {
            $this->place($this->customer(['name' => "PT Kecil {$i}"]), 1000 + $i);
        }
        $data = $this->section('breakdown', ['by' => 'customer']);
        $this->assertCount(10, $data['items']);
        $this->assertSame(['count' => 2, 'amount' => 2003, 'orders' => 2], $data['others']);
    }

    /**
     * Produk "Kopi Susu" ada di outlet A (dua varian) dan outlet C; "Roti" tanpa kategori.
     * X: Kopi Susu ×2 (20.000) + Kopi Susu 1L ×1 (50.000) + Roti ×3 (24.000) di A. Y: Kopi Susu ×1 (12.000) di C.
     *
     * @return array{0: Customer, 1: Customer, 2: \App\Models\Category, 3: Outlets}
     */
    private function seedProducts(): array
    {
        $outletC = $this->outlet('C');
        $this->staff = User::factory()->create(['outlet_id' => [$this->outlet->id, $outletC->id]]);
        $this->staff->givePermissionTo(Permission::firstOrCreate(['name' => 'read b2b/reports', 'guard_name' => 'web']));

        $coffee = $this->category('Kopi');
        $kopiA = $this->variant($this->outlet, 10000, 100, $coffee, 'Kopi Susu');
        $kopiLiter = VariantProduct::create(['product_id' => $kopiA->product_id, 'name' => 'Kopi Susu 1L', 'harga' => 50000, 'stok' => 100]);
        $kopiC = $this->variant($outletC, 12000, 100, $coffee, 'Kopi Susu');
        $roti = $this->variant($this->outlet, 8000, 100, null, 'Roti');

        $x = $this->customer(['name' => 'PT X']);
        $y = $this->customer(['name' => 'PT Y']);
        $orders = app(OrderService::class);
        $base = ['delivery_method' => 'pickup', 'due_date' => today()->addDays(7)->toDateString()];
        $orders->place($this->staff, $base + ['outlet_id' => $this->outlet->id, 'b2b_customer_id' => $x->id, 'items' => [
            ['variant_id' => $kopiA->id, 'qty' => 2], ['variant_id' => $kopiLiter->id, 'qty' => 1], ['variant_id' => $roti->id, 'qty' => 3],
        ]]);
        $orders->place($this->staff, $base + ['outlet_id' => $outletC->id, 'b2b_customer_id' => $y->id, 'items' => [['variant_id' => $kopiC->id, 'qty' => 1]]]);

        return [$x, $y, $coffee, $outletC];
    }

    public function test_breakdown_by_product_variant_category_and_outlet(): void
    {
        [$x, $y, $coffee, $outletC] = $this->seedProducts();
        $rows = fn (array $query) => array_map(
            fn ($item) => [$item['key'], $item['label'], $item['amount'], $item['orders'], $item['qty']],
            $this->section('breakdown', $query)['items']
        );

        // Produk dikelompokkan menurut nama, jadi Kopi Susu di outlet A dan C tergabung.
        $this->assertSame([['Kopi Susu', 'Kopi Susu', 82000, 2, 4], ['Roti', 'Roti', 24000, 1, 3]], $rows(['by' => 'product']));
        $this->assertSame([['Kopi Susu 1L', 'Kopi Susu 1L', 50000, 1, 1], ['Kopi Susu', 'Kopi Susu', 32000, 2, 3]], $rows(['by' => 'variant', 'product' => 'Kopi Susu']));
        $this->assertSame([[$x->id, 'PT X', 20000, 1, 2], [$y->id, 'PT Y', 12000, 1, 1]], $rows(['by' => 'customer', 'product' => 'Kopi Susu', 'variant' => 'Kopi Susu']));

        $this->assertSame([[$coffee->id, $coffee->name, 82000, 2, 4], [0, 'Tanpa kategori', 24000, 1, 3]], $rows(['by' => 'category']));
        $this->assertSame([['Roti', 'Roti', 24000, 1, 3]], $rows(['by' => 'product', 'category_id' => 0]));
        $this->assertSame([['Kopi Susu', 'Kopi Susu', 70000, 1, 3], ['Roti', 'Roti', 24000, 1, 3]], $rows(['by' => 'product', 'customer_id' => $x->id]));

        // Outlet memakai total pesanan; drilldown outlet → customer.
        $this->assertSame([[$this->outlet->id, $this->outlet->name, 94000, 1, null], [$outletC->id, $outletC->name, 12000, 1, null]], $rows(['by' => 'outlet']));
        $this->assertSame([[$y->id, 'PT Y', 12000, 1, null]], $rows(['by' => 'customer', 'outlet_id' => $outletC->id]));
    }

    public function test_orders_list_with_item_filter_shows_item_value(): void
    {
        [$x] = $this->seedProducts();

        $data = $this->section('orders', ['product' => 'Kopi Susu']);
        $this->assertTrue($data['item_filter']);
        $this->assertSame(['amount' => 82000, 'orders' => 2], $data['totals']);
        $this->assertSame([['PT Y', 12000, 12000], ['PT X', 70000, 94000]], array_map(
            fn ($order) => [$order['customer'], $order['amount'], $order['order_total']], $data['items']
        ));

        $roti = $this->section('orders', ['category_id' => 0, 'customer_id' => $x->id]);
        $this->assertSame(['amount' => 24000, 'orders' => 1], $roti['totals']);
        $this->assertSame([24000], array_column($roti['items'], 'amount'));
        $this->assertFalse($this->section('orders')['item_filter']);
    }

    public function test_orders_list_is_paginated_and_follows_overrides(): void
    {
        [$x] = $this->seedOrders();
        $this->staff->givePermissionTo(Permission::firstOrCreate(['name' => 'read b2b/orders', 'guard_name' => 'web']));

        $data = $this->section('orders');
        $this->assertSame(['amount' => 50000, 'orders' => 2], $data['totals']);
        $this->assertSame([30000, 20000], array_column($data['items'], 'amount')); // terbaru dulu
        $this->assertSame(['PT Y', 'Selesai', 'success', 'Bayar langsung'], [
            $data['items'][0]['customer'], $data['items'][0]['status'], $data['items'][0]['status_variant'], $data['items'][0]['payment_type'],
        ]);
        $this->assertSame([1, 1], [$data['page'], $data['last_page']]);

        // Drilldown customer + rentang batang.
        $onlyX = $this->section('orders', ['customer_id' => $x->id, 'from' => today()->toDateString(), 'to' => today()->toDateString()]);
        $this->assertSame(['amount' => 20000, 'orders' => 1], $onlyX['totals']);

        foreach (range(1, 15) as $i) {
            $this->place($x, 1000);
        }
        $page2 = $this->section('orders', ['page' => 2]);
        $this->assertSame([2, 2], [$page2['page'], $page2['last_page']]);
        $this->assertCount(2, $page2['items']);
    }

    public function test_summary_rejects_invalid_filters(): void
    {
        $this->actingAs($this->staff)
            ->getJson(route('b2b/reports/data', ['section' => 'summary', 'outlet_id' => $this->otherOutlet->id]))
            ->assertUnprocessable()->assertJsonValidationErrors('outlet_id');

        $this->actingAs($this->staff)
            ->getJson(route('b2b/reports/data', ['section' => 'summary', 'from' => '2026-11-10', 'to' => '2026-11-01']))
            ->assertUnprocessable()->assertJsonValidationErrors('to');

        $this->actingAs($this->staff)
            ->getJson(route('b2b/reports/data', ['section' => 'trend', 'group' => 'year']))
            ->assertUnprocessable()->assertJsonValidationErrors('group');

        $this->actingAs($this->staff)
            ->getJson(route('b2b/reports/data', ['section' => 'breakdown']))
            ->assertUnprocessable()->assertJsonValidationErrors('by');

        $this->actingAs($this->staff)->getJson('/b2b/reports/data/unknown')->assertNotFound();
    }
}

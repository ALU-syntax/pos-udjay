<?php

namespace Tests\Feature\B2b;

use App\Models\B2b\Customer;
use App\Models\B2b\Order;
use App\Models\CategoryPayment;
use App\Models\Menu;
use App\Models\Outlets;
use App\Models\User;
use App\Services\B2b\OrderService;
use App\Services\B2b\PaymentService;
use Database\Seeders\B2bMenuSeeder;

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
        return $this->actingAs($this->staff)
            ->getJson(route('b2b/reports/data', ['section' => 'summary'] + $query))
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

    public function test_summary_rejects_invalid_filters(): void
    {
        $this->actingAs($this->staff)
            ->getJson(route('b2b/reports/data', ['section' => 'summary', 'outlet_id' => $this->otherOutlet->id]))
            ->assertUnprocessable()->assertJsonValidationErrors('outlet_id');

        $this->actingAs($this->staff)
            ->getJson(route('b2b/reports/data', ['section' => 'summary', 'from' => '2026-11-10', 'to' => '2026-11-01']))
            ->assertUnprocessable()->assertJsonValidationErrors('to');

        $this->actingAs($this->staff)->getJson('/b2b/reports/data/unknown')->assertNotFound();
    }
}

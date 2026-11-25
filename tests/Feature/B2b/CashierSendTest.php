<?php

namespace Tests\Feature\B2b;

use App\Jobs\B2b\SendReminderJob;
use App\Models\B2b\Customer;
use App\Models\B2b\Order;
use App\Models\B2b\ReminderLog;
use App\Models\Outlets;
use App\Models\User;
use App\Models\VariantProduct;
use App\Services\B2b\B2bSettings;
use Illuminate\Support\Facades\Bus;

/** Kirim invoice (tempo) / kwitansi (bayar langsung) ke customer langsung dari dialog kasir. */
class CashierSendTest extends B2bTestCase
{
    private Outlets $outlet;

    private User $cashier;

    private VariantProduct $variant;

    private Customer $customer;

    /** send_note dari respons simpan pesanan terakhir. */
    private ?string $lastNote = null;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([SendReminderJob::class]);
        config(['services.b2b_wa_gateway.url' => 'http://wa-gateway.test', 'services.b2b_wa_gateway.key' => 'kunci-rahasia-gateway-minimal-32-karakter']);
        $this->outlet = $this->outlet();
        // Kasir tanpa izin menu Reminder tetap boleh mengirim dokumen pesanan yang ia buat.
        $this->cashier = $this->userFor($this->outlet, ['read b2b/cashier', 'create b2b/cashier']);
        $this->variant = $this->variant($this->outlet, 10000, 50);
        $this->customer = $this->customer(['email' => 'finance@pelanggan.test', 'phone' => '0812-3456-7890', 'reminder_enabled' => false]);
    }

    private function store(array $attributes): Order
    {
        $response = $this->actingAs($this->cashier)->postJson(route('b2b/cashier/store'), array_merge([
            'outlet_id' => $this->outlet->id,
            'b2b_customer_id' => $this->customer->id,
            'items' => [['variant_id' => $this->variant->id, 'qty' => 2]],
            'delivery_method' => 'pickup',
            'due_date' => today()->addDays(7)->toDateString(),
        ], $attributes))->assertOk();
        $this->lastNote = $response->json('data.send_note');

        return Order::findOrFail($response->json('data.id'));
    }

    public function test_customer_summary_lists_send_channels(): void
    {
        $this->actingAs($this->cashier)->getJson(route('b2b/cashier/customer', $this->customer->id))
            ->assertOk()
            ->assertJsonPath('data.send_channels.0.value', 'email')
            ->assertJsonPath('data.send_channels.0.contact', 'finance@pelanggan.test')
            ->assertJsonPath('data.send_channels.0.blocked', null)
            ->assertJsonPath('data.send_channels.1.contact', '+6281234567890');
    }

    public function test_credit_order_sends_invoice_on_checked_channels(): void
    {
        $order = $this->store(['send_channels' => ['email', 'whatsapp']]);

        $logs = ReminderLog::query()->orderBy('channel')->get();
        $this->assertCount(2, $logs);
        $this->assertSame([ReminderLog::KIND_INVOICE], $logs->pluck('kind')->unique()->all());
        $this->assertSame($this->cashier->id, $logs->first()->sent_by);
        $this->assertSame([$order->invoice->id], $logs->first()->invoices()->pluck('b2b_invoices.id')->all());
        $this->assertStringStartsWith('Invoice: Email ke finance@pelanggan.test masuk antrean', $this->lastNote);
        Bus::assertDispatchedTimes(SendReminderJob::class, 2);
    }

    public function test_immediate_order_sends_receipt(): void
    {
        [$cash] = $this->paymentCategory('Cash');

        $this->store(['payment_type' => 'immediate', 'payment' => ['category_payment_id' => $cash->id], 'send_channels' => ['email']]);

        $log = ReminderLog::query()->sole();
        $this->assertSame(ReminderLog::KIND_RECEIPT, $log->kind);
        $this->assertSame('Kwitansi: Email ke finance@pelanggan.test masuk antrean.', $this->lastNote);
    }

    public function test_nothing_is_sent_without_checked_channels(): void
    {
        $this->store([]);
        $this->store(['send_channels' => []]);

        $this->assertNull($this->lastNote);
        $this->assertSame(0, ReminderLog::query()->count());
        Bus::assertNotDispatched(SendReminderJob::class);
    }

    public function test_send_problems_do_not_cancel_the_order(): void
    {
        app(B2bSettings::class)->setReminderChannelEnabled('whatsapp', false);

        $order = $this->store(['send_channels' => ['email', 'whatsapp']]);

        $this->assertNotNull($order->invoice);
        $this->assertSame(0, ReminderLog::query()->count());
        $this->assertStringStartsWith('Invoice tidak dikirim: ', $this->lastNote);
        $this->assertStringContainsString('saklar darurat', $this->lastNote);

        // Kontak kosong: pesanan tetap tersimpan, kiriman dicatat sebagai dilewati.
        $this->customer->update(['email' => null]);
        $this->store(['send_channels' => ['email']]);
        $this->assertSame(ReminderLog::STATUS_SKIPPED, ReminderLog::query()->sole()->status);
        $this->assertStringContainsString('Email dilewati', $this->lastNote);

        $this->actingAs($this->cashier)->postJson(route('b2b/cashier/store'), [
            'outlet_id' => $this->outlet->id,
            'b2b_customer_id' => $this->customer->id,
            'items' => [['variant_id' => $this->variant->id, 'qty' => 1]],
            'delivery_method' => 'pickup',
            'due_date' => today()->addDays(7)->toDateString(),
            'send_channels' => ['sms'],
        ])->assertUnprocessable()->assertJsonValidationErrors('send_channels.0');
    }
}

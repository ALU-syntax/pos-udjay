<?php

namespace Tests\Feature\B2b;

use App\Jobs\B2b\SendReminderJob;
use App\Models\B2b\Customer;
use App\Models\B2b\Invoice;
use App\Models\B2b\ReminderLog;
use App\Models\Outlets;
use App\Models\User;
use App\Models\VariantProduct;
use App\Services\B2b\B2bSettings;
use App\Services\B2b\OrderService;
use App\Services\B2b\ReminderService;
use App\Support\B2b\Phone;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

class WhatsAppReminderTest extends B2bTestCase
{
    private const GATEWAY = 'http://wa-gateway.test';

    private const KEY = 'kunci-rahasia-gateway-minimal-32-karakter';

    private Outlets $outlet;

    private User $user;

    private VariantProduct $variant;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.b2b_wa_gateway.url' => self::GATEWAY, 'services.b2b_wa_gateway.key' => self::KEY]);
        $this->outlet = $this->outlet();
        $this->user = $this->userFor($this->outlet);
        $this->variant = $this->variant($this->outlet, 10000, 100);
        $this->travelTo(today()->setTime(9, 0));
    }

    private function waCustomer(array $attributes = []): Customer
    {
        return $this->customer(array_merge([
            'contact_name' => 'Bu Rina',
            'phone' => '0812-3456-7890',
            'email' => 'finance@pelanggan.test',
            'reminder_enabled' => true,
            'reminder_channels' => ['whatsapp'],
            'reminder_days' => [-3, 0],
            'reminder_overdue_daily' => true,
        ], $attributes));
    }

    private function invoiceDueIn(Customer $customer, int $days): Invoice
    {
        return app(OrderService::class)->place($this->user, [
            'outlet_id' => $this->outlet->id,
            'b2b_customer_id' => $customer->id,
            'items' => [['variant_id' => $this->variant->id, 'qty' => 3]],
            'delivery_method' => 'pickup',
            'due_date' => today()->addDays($days)->toDateString(),
        ])->invoice;
    }

    private function queuedLog(Customer $customer, string $channel = 'whatsapp'): ReminderLog
    {
        Bus::fake([SendReminderJob::class]);
        app(ReminderService::class)->queueDailyReminders();

        return ReminderLog::query()->where('b2b_customer_id', $customer->id)->where('channel', $channel)->firstOrFail();
    }

    private function runJob(ReminderLog $log): void
    {
        (new SendReminderJob($log->id))->handle(app(B2bSettings::class));
    }

    public function test_phone_is_normalized_like_the_gateway(): void
    {
        $this->assertSame('6281234567890', Phone::toWhatsApp('0812-3456-7890'));
        $this->assertSame('6281234567890', Phone::toWhatsApp('+62 812 3456 7890'));
        $this->assertSame('6281234567890', Phone::toWhatsApp('81234567890'));
        $this->assertNull(Phone::toWhatsApp('0812'));
        $this->assertNull(Phone::toWhatsApp(null));
    }

    public function test_whatsapp_is_supported_only_when_gateway_is_configured(): void
    {
        $this->assertSame(['email', 'whatsapp'], app(ReminderService::class)->supportedChannels());

        config(['services.b2b_wa_gateway.key' => null]);
        $this->assertSame(['email'], app(ReminderService::class)->supportedChannels());
    }

    public function test_daily_reminders_queue_whatsapp_with_normalized_number_and_skip_invalid_phone(): void
    {
        Bus::fake([SendReminderJob::class]);
        $both = $this->waCustomer(['reminder_channels' => ['email', 'whatsapp']]);
        $this->invoiceDueIn($both, 0);
        $invalid = $this->waCustomer(['phone' => '12345']);
        $this->invoiceDueIn($invalid, 0);

        $result = app(ReminderService::class)->queueDailyReminders();

        $this->assertSame(2, $result['queued']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame('6281234567890', ReminderLog::query()->where('b2b_customer_id', $both->id)->where('channel', 'whatsapp')->value('recipient'));
        $this->assertSame('finance@pelanggan.test', ReminderLog::query()->where('b2b_customer_id', $both->id)->where('channel', 'email')->value('recipient'));
        $skipped = ReminderLog::query()->where('b2b_customer_id', $invalid->id)->firstOrFail();
        $this->assertSame(ReminderLog::STATUS_SKIPPED, $skipped->status);
        $this->assertStringContainsString('Nomor WhatsApp customer kosong atau tidak valid', $skipped->error);
        Bus::assertDispatchedTimes(SendReminderJob::class, 2);
    }

    public function test_job_sends_summary_text_then_one_pdf_per_invoice(): void
    {
        $customer = $this->waCustomer();
        $soon = $this->invoiceDueIn($customer, 3);
        $today = $this->invoiceDueIn($customer, 0);
        $log = $this->queuedLog($customer);
        Http::fake([self::GATEWAY.'/send' => Http::sequence()->push(['id' => 'TEXT1'])->push(['id' => 'DOC1'])->push(['id' => 'DOC2'])]);

        $this->runJob($log);

        $requests = Http::recorded()->map(fn ($pair) => $pair[0]);
        $this->assertCount(3, $requests);
        $requests->each(fn (Request $request) => $this->assertTrue($request->hasHeader('Authorization', 'Bearer '.self::KEY)));
        $text = $requests[0]->data();
        $this->assertSame('6281234567890', $text['to']);
        $this->assertArrayNotHasKey('document', $text);
        $this->assertStringContainsString('Yth. Bu Rina', $text['text']);
        $this->assertStringContainsString('*'.$soon->invoice_number.'*', $text['text']);
        $this->assertStringContainsString('3 hari lagi', $text['text']);
        $this->assertStringContainsString('jatuh tempo hari ini', $text['text']);
        $this->assertStringContainsString('*Total sisa tagihan: Rp 60.000*', $text['text']);
        // Urut jatuh tempo terdekat dulu: yang jatuh tempo hari ini, lalu yang 3 hari lagi.
        foreach ([1 => $today, 2 => $soon] as $index => $invoice) {
            $document = $requests[$index]->data()['document'];
            $this->assertSame('application/pdf', $document['mimetype']);
            $this->assertStringStartsWith('%PDF', base64_decode($document['base64']));
            $this->assertStringContainsString(str_replace('/', '-', $invoice->invoice_number), $document['filename']);
        }

        $log->refresh();
        $this->assertSame(ReminderLog::STATUS_SENT, $log->status);
        $this->assertSame('TEXT1', $log->provider_message_id);
        $this->assertSame($text['text'], $log->body);
        $this->assertNull($log->subject);
        $this->assertCount(2, $log->attachments);
    }

    public function test_number_not_on_whatsapp_fails_without_retry(): void
    {
        $customer = $this->waCustomer();
        $this->invoiceDueIn($customer, 0);
        $log = $this->queuedLog($customer);
        Http::fake([self::GATEWAY.'/send' => Http::response(['error' => 'Nomor 6281234567890 tidak terdaftar di WhatsApp.', 'code' => 'not_on_whatsapp'], 422)]);

        $this->runJob($log); // tidak melempar exception = tidak dicoba ulang

        $this->assertSame(ReminderLog::STATUS_FAILED, $log->fresh()->status);
        $this->assertStringContainsString('tidak terdaftar di WhatsApp', $log->fresh()->error);
        Http::assertSentCount(1);
    }

    public function test_partial_send_stops_without_retry_to_avoid_duplicates(): void
    {
        $customer = $this->waCustomer();
        $this->invoiceDueIn($customer, 0);
        $log = $this->queuedLog($customer);
        Http::fake([self::GATEWAY.'/send' => Http::sequence()
            ->push(['id' => 'TEXT1'])
            ->push(['error' => 'WhatsApp belum terhubung.', 'code' => 'not_connected'], 409)]);

        $this->runJob($log);

        $this->assertSame(ReminderLog::STATUS_FAILED, $log->fresh()->status);
        $this->assertStringContainsString('Terkirim sebagian (1 dari 2 pesan)', $log->fresh()->error);
    }

    public function test_gateway_down_before_anything_sent_is_retried(): void
    {
        $customer = $this->waCustomer();
        $this->invoiceDueIn($customer, 0);
        $log = $this->queuedLog($customer);

        foreach ([
            fn () => Http::response(['error' => 'WhatsApp belum terhubung.', 'code' => 'not_connected'], 409),
            fn () => throw new ConnectionException('Connection refused'),
        ] as $response) {
            Http::fake([self::GATEWAY.'/send' => $response]);
            try {
                $this->runJob($log);
                $this->fail('Job seharusnya melempar exception supaya dicoba ulang.');
            } catch (\RuntimeException $e) {
                $this->assertSame(ReminderLog::STATUS_QUEUED, $log->fresh()->status);
                $this->assertNotEmpty($log->fresh()->error);
            }
        }
    }

    public function test_failed_whatsapp_can_be_resent_only_while_gateway_is_configured(): void
    {
        $customer = $this->waCustomer();
        $invoice = $this->invoiceDueIn($customer, 0);
        $failed = ReminderLog::create([
            'b2b_customer_id' => $customer->id, 'channel' => 'whatsapp', 'recipient' => '6281234567890',
            'reminder_date' => today()->toDateString(), 'trigger' => 'auto', 'status' => 'failed', 'error' => 'x',
        ]);
        $failed->invoices()->attach($invoice->id, ['offset_days' => 0]);
        Bus::fake([SendReminderJob::class]);

        config(['services.b2b_wa_gateway.url' => null]);
        $sender = $this->userFor(null, ['send b2b/reminders']);
        $this->actingAs($sender)->postJson(route('b2b/reminders/resend', $failed->id))->assertUnprocessable()->assertJsonValidationErrors('channel');

        config(['services.b2b_wa_gateway.url' => self::GATEWAY]);
        $this->actingAs($sender)->postJson(route('b2b/reminders/resend', $failed->id))->assertOk();
        $this->assertSame('whatsapp', ReminderLog::query()->latest('id')->value('channel'));
        Bus::assertDispatchedTimes(SendReminderJob::class, 1);
    }
}

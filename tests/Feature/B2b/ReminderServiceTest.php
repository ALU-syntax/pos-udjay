<?php

namespace Tests\Feature\B2b;

use App\Jobs\B2b\SendReminderJob;
use App\Mail\B2b\InvoiceReminderMail;
use App\Models\B2b\Customer;
use App\Models\B2b\Invoice;
use App\Models\B2b\ReminderLog;
use App\Models\Config;
use App\Models\Outlets;
use App\Models\User;
use App\Models\VariantProduct;
use App\Services\B2b\B2bSettings;
use App\Services\B2b\InvoicePdfService;
use App\Services\B2b\OrderService;
use App\Services\B2b\PaymentService;
use App\Services\B2b\Reminder\EmailReminderChannel;
use App\Services\B2b\ReminderService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Mockery;

class ReminderServiceTest extends B2bTestCase
{
    private Outlets $outlet;

    private User $user;

    private VariantProduct $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->outlet = $this->outlet();
        $this->user = $this->userFor($this->outlet);
        $this->variant = $this->variant($this->outlet, 10000, 100);
        $this->travelTo(today()->setTime(9, 0));
    }

    private function reminderCustomer(array $attributes = []): Customer
    {
        return $this->customer(array_merge([
            'email' => 'finance@pelanggan.test',
            'contact_name' => 'Bu Rina',
            'reminder_enabled' => true,
            'reminder_channels' => ['email'],
            'reminder_days' => [-3, 0],
            'reminder_overdue_daily' => true,
        ], $attributes));
    }

    /** Invoice tempo 30.000 dengan jatuh tempo $days hari dari hari ini. */
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

    public function test_schedule_follows_customer_days_then_daily_until_h7_then_weekly(): void
    {
        $service = app(ReminderService::class);
        $customer = $this->reminderCustomer();

        $matches = collect(range(-7, 30))->filter(fn ($offset) => $service->matchesSchedule($customer, $offset))->values()->all();
        $this->assertSame([-3, 0, 1, 2, 3, 4, 5, 6, 7, 14, 21, 28], $matches);

        $customer->reminder_overdue_daily = false;
        $this->assertFalse($service->matchesSchedule($customer, 1));
        $this->assertFalse($service->matchesSchedule($customer, 14));
    }

    public function test_daily_reminders_are_combined_per_customer_and_not_duplicated(): void
    {
        Bus::fake([SendReminderJob::class]);
        [$cash] = $this->paymentCategory('Cash');
        $customer = $this->reminderCustomer();
        $dueIn3 = $this->invoiceDueIn($customer, 3);
        $dueToday = $this->invoiceDueIn($customer, 0);
        $notScheduled = $this->invoiceDueIn($customer, 5);
        $paid = $this->invoiceDueIn($customer, 0);
        app(PaymentService::class)->record($this->user, $paid, ['category_payment_id' => $cash->id, 'amount' => 30000]);
        $this->invoiceDueIn($this->reminderCustomer(['reminder_enabled' => false]), 0);

        $this->artisan('b2b:send-due-reminders')->expectsOutputToContain('Diantrekan: 1')->assertSuccessful();
        $this->artisan('b2b:send-due-reminders')->expectsOutputToContain('sudah ada: 1')->assertSuccessful();

        $logs = ReminderLog::query()->with('invoices')->get();
        $this->assertCount(1, $logs);
        $log = $logs->first();
        $this->assertSame($customer->id, $log->b2b_customer_id);
        $this->assertSame('finance@pelanggan.test', $log->recipient);
        $this->assertSame(ReminderLog::TRIGGER_AUTO, $log->trigger);
        $this->assertSame(ReminderLog::STATUS_QUEUED, $log->status);
        $this->assertEqualsCanonicalizing([$dueIn3->id, $dueToday->id], $log->invoices->pluck('id')->all());
        $this->assertSame(-3, (int) $log->invoices->firstWhere('id', $dueIn3->id)->pivot->offset_days);
        $this->assertNotContains($notScheduled->id, $log->invoices->pluck('id'));
        Bus::assertDispatchedTimes(SendReminderJob::class, 1);
    }

    public function test_overdue_invoice_is_reminded_daily_then_weekly(): void
    {
        Bus::fake([SendReminderJob::class]);
        $customer = $this->reminderCustomer(['reminder_days' => []]);
        $this->invoiceDueIn($customer, 0);
        $service = app(ReminderService::class);

        foreach ([7 => 1, 8 => 0, 14 => 1] as $daysLate => $expected) {
            $this->assertSame($expected, $service->queueDailyReminders(today()->addDays($daysLate))['queued'], "H+{$daysLate}");
        }
    }

    public function test_missing_email_is_logged_as_skipped_without_job(): void
    {
        Bus::fake([SendReminderJob::class]);
        $customer = $this->reminderCustomer(['email' => null]);
        $this->invoiceDueIn($customer, 0);

        $result = app(ReminderService::class)->queueDailyReminders();

        $this->assertSame(1, $result['skipped']);
        $log = ReminderLog::query()->firstOrFail();
        $this->assertSame(ReminderLog::STATUS_SKIPPED, $log->status);
        $this->assertNull($log->recipient);
        $this->assertStringContainsString('Email customer kosong', $log->error);
        Bus::assertNotDispatched(SendReminderJob::class);
    }

    public function test_emergency_switch_and_unsupported_channel_queue_nothing(): void
    {
        Bus::fake([SendReminderJob::class]);
        $this->invoiceDueIn($this->reminderCustomer(), 0);
        $this->invoiceDueIn($this->reminderCustomer(['reminder_channels' => ['whatsapp'], 'phone' => '08123']), 0);
        Config::updateOrCreate(['name' => B2bSettings::REMINDER_SWITCH_KEYS['email']], ['type' => 'boolean', 'value' => '0']);

        $result = app(ReminderService::class)->queueDailyReminders();

        $this->assertSame(1, $result['disabled']);
        $this->assertSame(0, ReminderLog::query()->count());
        Bus::assertNotDispatched(SendReminderJob::class);
    }

    private function queuedLog(Customer $customer): ReminderLog
    {
        Bus::fake([SendReminderJob::class]);
        app(ReminderService::class)->queueDailyReminders();

        return ReminderLog::query()->where('b2b_customer_id', $customer->id)->firstOrFail();
    }

    public function test_job_sends_combined_email_and_stores_content(): void
    {
        Mail::fake();
        $customer = $this->reminderCustomer();
        $first = $this->invoiceDueIn($customer, 3);
        $second = $this->invoiceDueIn($customer, 0);
        $log = $this->queuedLog($customer);

        (new SendReminderJob($log->id))->handle(app(B2bSettings::class));

        Mail::assertSent(InvoiceReminderMail::class, function (InvoiceReminderMail $mail) use ($first, $second) {
            return $mail->hasTo('finance@pelanggan.test')
                && $mail->hasReplyTo(app(B2bSettings::class)->invoiceProfile()['email'])
                && $mail->items->count() === 2
                && count($mail->attachments()) === 2
                && $mail->items->pluck('invoice.id')->sort()->values()->all() === collect([$first->id, $second->id])->sort()->values()->all();
        });
        $log->refresh();
        $this->assertSame(ReminderLog::STATUS_SENT, $log->status);
        $this->assertNotNull($log->sent_at);
        $this->assertSame(1, $log->attempts);
        $this->assertStringContainsString('Tagihan jatuh tempo hari ini', $log->subject);
        $this->assertStringContainsString($first->invoice_number, $log->body);
        $this->assertStringContainsString('Bu Rina', $log->body);
        $this->assertCount(2, $log->attachments);
        $this->assertStringEndsWith('.pdf', $log->attachments[0]);
    }

    public function test_job_skips_invoices_paid_after_queueing(): void
    {
        Mail::fake();
        [$cash] = $this->paymentCategory('Cash');
        $customer = $this->reminderCustomer();
        $invoice = $this->invoiceDueIn($customer, 0);
        $log = $this->queuedLog($customer);
        app(PaymentService::class)->record($this->user, $invoice, ['category_payment_id' => $cash->id, 'amount' => 30000]);

        (new SendReminderJob($log->id))->handle(app(B2bSettings::class));

        Mail::assertNothingSent();
        $this->assertSame(ReminderLog::STATUS_SKIPPED, $log->fresh()->status);
    }

    public function test_auto_reminder_is_not_sent_after_retry_deadline(): void
    {
        Mail::fake();
        $customer = $this->reminderCustomer();
        $this->invoiceDueIn($customer, 0);
        $log = $this->queuedLog($customer);

        $this->travelTo(today()->setTime(17, 1));
        (new SendReminderJob($log->id))->handle(app(B2bSettings::class));

        Mail::assertNothingSent();
        $this->assertSame(ReminderLog::STATUS_FAILED, $log->fresh()->status);
        $this->assertStringContainsString('17:00', $log->fresh()->error);
    }

    public function test_send_error_is_recorded_and_marked_failed_after_last_attempt(): void
    {
        $customer = $this->reminderCustomer();
        $this->invoiceDueIn($customer, 0);
        $log = $this->queuedLog($customer);
        $channel = Mockery::mock(EmailReminderChannel::class);
        $channel->shouldReceive('send')->andThrow(new \RuntimeException('SMTP timeout'));
        $this->app->instance(EmailReminderChannel::class, $channel);
        $job = new SendReminderJob($log->id);

        try {
            $job->handle(app(B2bSettings::class));
            $this->fail('Job seharusnya melempar exception supaya dicoba ulang.');
        } catch (\RuntimeException) {
            $this->assertSame(ReminderLog::STATUS_QUEUED, $log->fresh()->status);
            $this->assertSame('SMTP timeout', $log->fresh()->error);
        }

        $job->failed(new \RuntimeException('SMTP timeout'));
        $this->assertSame(ReminderLog::STATUS_FAILED, $log->fresh()->status);
    }

    public function test_invoice_pdf_is_generated_and_downloadable(): void
    {
        $invoice = $this->invoiceDueIn($this->reminderCustomer(), 7);

        $this->assertStringStartsWith('%PDF', app(InvoicePdfService::class)->output($invoice));

        $user = $this->userFor($this->outlet, ['read b2b/invoices']);
        $this->actingAs($user)->get(route('b2b/invoices/pdf', $invoice->id))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}

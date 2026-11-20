<?php

namespace Tests\Feature\B2b;

use App\Jobs\B2b\SendReminderJob;
use App\Models\B2b\ActivityLog;
use App\Models\B2b\Customer;
use App\Models\B2b\Invoice;
use App\Models\B2b\ReminderLog;
use App\Models\Outlets;
use App\Models\User;
use App\Services\B2b\B2bSettings;
use App\Services\B2b\OrderService;
use App\Services\B2b\ReminderService;
use Database\Seeders\B2bMenuSeeder;
use Illuminate\Support\Facades\Bus;

class ReminderHttpTest extends B2bTestCase
{
    private Outlets $outlet;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        Bus::fake([SendReminderJob::class]);
        $this->outlet = $this->outlet();
        $this->staff = $this->userFor($this->outlet);
    }

    private function customerWithInvoice(array $attributes = []): array
    {
        $customer = $this->customer(array_merge([
            'email' => 'finance@pelanggan.test',
            'reminder_enabled' => true,
            'reminder_channels' => ['email'],
            'reminder_days' => [0],
        ], $attributes));
        $invoice = app(OrderService::class)->place($this->staff, [
            'outlet_id' => $this->outlet->id,
            'b2b_customer_id' => $customer->id,
            'items' => [['variant_id' => $this->variant($this->outlet, 10000)->id, 'qty' => 2]],
            'delivery_method' => 'pickup',
            'due_date' => today()->toDateString(),
        ])->invoice;

        return [$customer, $invoice];
    }

    private function log(Customer $customer, Invoice $invoice, array $attributes = []): ReminderLog
    {
        $log = ReminderLog::create(array_merge([
            'b2b_customer_id' => $customer->id,
            'channel' => ReminderLog::CHANNEL_EMAIL,
            'recipient' => $customer->email,
            'reminder_date' => today()->toDateString(),
            'trigger' => ReminderLog::TRIGGER_AUTO,
            'status' => ReminderLog::STATUS_FAILED,
            'error' => 'SMTP timeout',
            'subject' => '[Budiman Roastery] Tagihan jatuh tempo hari ini',
            'body' => '<p>Isi email reminder</p>',
            'attachments' => ['Invoice 01-INV-BGR-11-2026.pdf'],
            'attempts' => 4,
        ], $attributes));
        $log->invoices()->attach($invoice->id, ['offset_days' => 0]);

        return $log;
    }

    public function test_seeder_registers_reminder_menu_and_permissions(): void
    {
        $this->seed(B2bMenuSeeder::class);

        $this->assertSame(1, \App\Models\Menu::where('url', 'b2b/reminders')->count());
        foreach (['read', 'send', 'toggle'] as $action) {
            $this->assertDatabaseHas('permissions', ['name' => "{$action} b2b/reminders"]);
        }
    }

    public function test_page_and_activity_list_require_read_permission(): void
    {
        [$customer, $invoice] = $this->customerWithInvoice(['name' => 'PT Reminder Satu']);
        $this->log($customer, $invoice);

        $this->actingAs($this->userFor())->get(route('b2b/reminders'))->assertForbidden();

        $reader = $this->userFor(null, ['read b2b/reminders']);
        $this->actingAs($reader)->get(route('b2b/reminders'))
            ->assertOk()
            ->assertSee('Reminder Tagihan')
            ->assertSee('Saklar darurat')
            ->assertDontSee('Hentikan</a>', false); // tanpa permission toggle

        // Yajra menyimpan request pertama di singleton; di production tiap request memakai container baru.
        $this->app->forgetInstance('datatables.request');
        $rows = $this->actingAs($reader)
            ->getJson(route('b2b/reminders', ['draw' => 1, 'start' => 0, 'length' => 10, 'status' => 'failed']), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->json('data');
        $this->assertCount(1, $rows);
        $this->assertStringContainsString('PT Reminder Satu', $rows[0]['customer_name']);
        $this->assertStringContainsString($invoice->invoice_number, $rows[0]['invoice_list']);
        $this->assertStringContainsString('Gagal', $rows[0]['status']);
        $this->assertStringNotContainsString('Kirim ulang', $rows[0]['action']); // tanpa permission send
    }

    public function test_detail_returns_email_log(): void
    {
        [$customer, $invoice] = $this->customerWithInvoice();
        $log = $this->log($customer, $invoice);
        $reader = $this->userFor(null, ['read b2b/reminders']);

        $this->actingAs($reader)->getJson(route('b2b/reminders/show', $log->id))
            ->assertOk()
            ->assertJsonPath('data.subject', '[Budiman Roastery] Tagihan jatuh tempo hari ini')
            ->assertJsonPath('data.body', '<p>Isi email reminder</p>')
            ->assertJsonPath('data.attachments.0', 'Invoice 01-INV-BGR-11-2026.pdf')
            ->assertJsonPath('data.error', 'SMTP timeout')
            ->assertJsonPath('data.invoices.0.number', $invoice->invoice_number)
            ->assertJsonPath('data.invoices.0.offset', 'Hari H');
    }

    public function test_failed_reminder_can_be_resent_as_manual_send(): void
    {
        [$customer, $invoice] = $this->customerWithInvoice();
        $failed = $this->log($customer, $invoice);
        $customer->update(['email' => 'tagihan@pelanggan.test']); // email diperbaiki setelah gagal

        $this->actingAs($this->userFor(null, ['read b2b/reminders']))
            ->postJson(route('b2b/reminders/resend', $failed->id))->assertForbidden();

        $sender = $this->userFor(null, ['read b2b/reminders', 'send b2b/reminders']);
        $this->actingAs($sender)->postJson(route('b2b/reminders/resend', $failed->id))->assertOk();

        $new = ReminderLog::query()->whereKeyNot($failed->id)->firstOrFail();
        $this->assertSame(ReminderLog::TRIGGER_MANUAL, $new->trigger);
        $this->assertSame(ReminderLog::STATUS_QUEUED, $new->status);
        $this->assertSame('tagihan@pelanggan.test', $new->recipient);
        $this->assertSame($sender->id, $new->sent_by);
        $this->assertSame([$invoice->id], $new->invoices()->pluck('b2b_invoices.id')->all());
        $this->assertSame(ReminderLog::STATUS_FAILED, $failed->fresh()->status);
        Bus::assertDispatchedTimes(SendReminderJob::class, 1);

        // Manual boleh berulang (tidak kena anti dobel otomatis).
        $this->actingAs($sender)->postJson(route('b2b/reminders/resend', $failed->id))->assertOk();
        $this->assertSame(3, ReminderLog::query()->count());
        // Kiriman yang terkirim tidak bisa dikirim ulang.
        $this->actingAs($sender)->postJson(route('b2b/reminders/resend', $new->id))->assertUnprocessable();
    }

    public function test_resend_is_rejected_when_invoices_are_settled_or_channel_is_off(): void
    {
        [$customer, $invoice] = $this->customerWithInvoice();
        $failed = $this->log($customer, $invoice);
        $sender = $this->userFor(null, ['send b2b/reminders']);

        app(B2bSettings::class)->setReminderChannelEnabled('email', false);
        $this->actingAs($sender)->postJson(route('b2b/reminders/resend', $failed->id))
            ->assertUnprocessable()->assertJsonValidationErrors('channel');

        app(B2bSettings::class)->setReminderChannelEnabled('email', true);
        $invoice->update(['paid_amount' => $invoice->total_to_pay, 'status' => Invoice::STATUS_PAID]);
        $this->actingAs($sender)->postJson(route('b2b/reminders/resend', $failed->id))
            ->assertUnprocessable()->assertJsonValidationErrors('status');

        $this->assertSame(1, ReminderLog::query()->count());
    }

    public function test_emergency_switch_needs_permission_and_reason_to_stop(): void
    {
        $url = fn (string $state) => route('b2b/reminders/toggle', ['email', $state]);
        $settings = app(B2bSettings::class);

        $this->actingAs($this->userFor(null, ['read b2b/reminders']))->postJson($url('off'), ['reason' => 'x'])->assertForbidden();

        $admin = $this->userFor(null, ['read b2b/reminders', 'toggle b2b/reminders']);
        $this->actingAs($admin)->postJson($url('off'))->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->assertTrue($settings->reminderChannelEnabled('email'));

        $this->actingAs($admin)->postJson($url('off'), ['reason' => 'SMTP sedang bermasalah'])->assertOk();
        $this->assertFalse($settings->reminderChannelEnabled('email'));
        $this->assertTrue($settings->reminderChannelEnabled('whatsapp'));
        $log = ActivityLog::query()->where('action', 'reminder.channel_disabled')->firstOrFail();
        $this->assertSame('SMTP sedang bermasalah', $log->changes['reason']);
        $this->actingAs($admin)->get(route('b2b/reminders'))->assertOk()->assertSee('Dihentikan')->assertSee('Aktifkan</a>', false);

        // Saat dihentikan, reminder harian tidak diantrekan.
        $this->customerWithInvoice();
        $this->assertSame(1, app(ReminderService::class)->queueDailyReminders()['disabled']);

        $this->actingAs($admin)->postJson($url('on'))->assertOk();
        $this->assertTrue($settings->reminderChannelEnabled('email'));
        $this->actingAs($admin)->postJson(route('b2b/reminders/toggle', ['sms', 'off']), ['reason' => 'x'])->assertNotFound();
    }

    public function test_invoice_reminder_can_be_sent_now_from_order_and_invoice_pages(): void
    {
        [$customer, $invoice] = $this->customerWithInvoice(['reminder_enabled' => false]); // manual tidak bergantung jadwal
        $url = route('b2b/reminders/send-invoice', $invoice->id);

        $this->actingAs($this->userFor($this->outlet, ['read b2b/orders']))->postJson($url)->assertForbidden();
        $this->actingAs($this->userFor($this->outlet('lain'), ['send b2b/reminders']))->postJson($url)->assertNotFound();

        $sender = $this->userFor($this->outlet, ['send b2b/reminders', 'read b2b/orders', 'read b2b/invoices']);
        $this->actingAs($sender)->get(route('b2b/orders/show', $invoice->b2b_order_id))
            ->assertOk()->assertSee('Kirim reminder')->assertSee('Belum ada reminder untuk invoice ini.');

        $this->actingAs($sender)->postJson($url)->assertOk()->assertJsonPath('message', fn ($message) => str_contains($message, 'finance@pelanggan.test'));

        $log = ReminderLog::query()->firstOrFail();
        $this->assertSame(ReminderLog::TRIGGER_MANUAL, $log->trigger);
        $this->assertSame(ReminderLog::STATUS_QUEUED, $log->status);
        $this->assertSame($sender->id, $log->sent_by);
        $this->assertSame([$invoice->id], $log->invoices()->pluck('b2b_invoices.id')->all());
        $this->assertSame(0, (int) $log->invoices()->first()->pivot->offset_days);
        Bus::assertDispatchedTimes(SendReminderJob::class, 1);
        $this->assertDatabaseHas('b2b_activity_logs', ['action' => 'reminder.manual', 'subject_id' => $log->id]);

        // Riwayat muncul di detail pesanan; tombol lonceng ada di daftar invoice.
        $this->actingAs($sender)->get(route('b2b/orders/show', $invoice->b2b_order_id))
            ->assertOk()->assertSee('Riwayat reminder')->assertSee('Manual oleh '.$sender->name)->assertSee('Antre');
        $this->app->forgetInstance('datatables.request');
        $rows = $this->actingAs($sender)
            ->getJson(route('b2b/invoices', ['draw' => 1, 'start' => 0, 'length' => 10]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data');
        $this->assertStringContainsString($url, $rows[0]['action']);
    }

    public function test_send_now_is_rejected_for_settled_invoice_or_stopped_channel_and_skips_missing_email(): void
    {
        $sender = $this->userFor($this->outlet, ['send b2b/reminders']);
        [, $invoice] = $this->customerWithInvoice();

        app(B2bSettings::class)->setReminderChannelEnabled('email', false);
        $this->actingAs($sender)->postJson(route('b2b/reminders/send-invoice', $invoice->id))->assertUnprocessable()->assertJsonValidationErrors('channel');
        app(B2bSettings::class)->setReminderChannelEnabled('email', true);

        $invoice->update(['paid_amount' => $invoice->total_to_pay, 'status' => Invoice::STATUS_PAID]);
        $this->actingAs($sender)->postJson(route('b2b/reminders/send-invoice', $invoice->id))->assertUnprocessable()->assertJsonValidationErrors('invoice');
        $this->assertSame(0, ReminderLog::query()->count());

        [, $noEmail] = $this->customerWithInvoice(['email' => null]);
        $this->actingAs($sender)->postJson(route('b2b/reminders/send-invoice', $noEmail->id))
            ->assertOk()->assertJsonPath('message', fn ($message) => str_contains($message, 'dilewati'));
        $this->assertSame(ReminderLog::STATUS_SKIPPED, ReminderLog::query()->firstOrFail()->status);
        Bus::assertNotDispatched(SendReminderJob::class);
    }

    public function test_customer_tab_lists_channels_and_contact_issues(): void
    {
        [$ready, $readyInvoice] = $this->customerWithInvoice(['name' => 'PT Siap Kirim']);
        $this->log($ready, $readyInvoice, ['status' => ReminderLog::STATUS_SENT, 'error' => null]);
        $this->customerWithInvoice(['name' => 'CV Tanpa Email', 'email' => null]);
        $this->customerWithInvoice(['name' => 'PT Belum Diatur', 'reminder_enabled' => false]);
        $this->customer(['name' => 'PT Tanpa Tagihan', 'reminder_enabled' => false]);
        $this->customer(['name' => 'PT WhatsApp Saja', 'reminder_enabled' => true, 'reminder_channels' => ['whatsapp'], 'reminder_days' => [0]]);

        $page = $this->actingAs($this->userFor(null, ['read b2b/reminders']))
            ->get(route('b2b/reminders', ['tab' => 'customers']))
            ->assertOk()
            ->assertSee('Pengaturan reminder per customer')
            ->assertSee('PT Siap Kirim')
            ->assertSee('Terkirim')
            ->assertSee('PT Belum Diatur')
            ->assertSee('Belum diatur')
            ->assertSee('Email kosong atau tidak valid')
            ->assertSee('Nomor WhatsApp kosong')
            ->assertSee('WhatsApp belum dikirim')
            ->assertDontSee('PT Tanpa Tagihan')
            ->assertDontSee('Aktivitas kiriman');

        // Yang perlu dilengkapi tampil paling atas, lalu yang belum diatur.
        $html = $page->getContent();
        $this->assertLessThan(strpos($html, 'PT Belum Diatur'), strpos($html, 'CV Tanpa Email'));
        $this->assertLessThan(strpos($html, 'PT Siap Kirim'), strpos($html, 'PT Belum Diatur'));
    }
}

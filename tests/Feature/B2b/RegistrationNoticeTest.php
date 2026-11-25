<?php

namespace Tests\Feature\B2b;

use App\Jobs\B2b\SendReminderJob;
use App\Mail\B2b\CustomerRegisteredMail;
use App\Models\B2b\Customer;
use App\Models\B2b\ReminderLog;
use App\Models\Config;
use App\Services\B2b\B2bSettings;
use App\Services\B2b\OrderService;
use App\Services\B2b\ReminderService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/** Konfirmasi pendaftaran customer B2B lewat form umum, dikirim lewat jalur kiriman reminder (email/WhatsApp). */
class RegistrationNoticeTest extends B2bTestCase
{
    private const GATEWAY = 'http://wa-gateway.test';

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.b2b_wa_gateway.url' => self::GATEWAY, 'services.b2b_wa_gateway.key' => 'kunci-rahasia-gateway-minimal-32-karakter']);
        Config::setValue(B2bSettings::ORDER_CONTACT_PHONE_KEY, '0811-1111-2222', 'Admin pemesanan', 'string');
        $this->customer = $this->customer([
            'name' => 'Kafe Senja',
            'contact_name' => 'Bu Rina',
            'email' => 'rina@kafesenja.test',
            'phone' => '0812-3456-7890',
        ]);
    }

    /** Antrekan konfirmasi (job dipalsukan) lalu kembalikan kiriman per channel. */
    private function queue(string $channel): ReminderLog
    {
        Bus::fake([SendReminderJob::class]);
        app(ReminderService::class)->queueRegistrationNotice($this->customer);

        return ReminderLog::query()->where('channel', $channel)->sole();
    }

    private function runJob(ReminderLog $log): void
    {
        (new SendReminderJob($log->id))->handle(app(B2bSettings::class));
    }

    public function test_notice_is_queued_once_per_filled_contact(): void
    {
        Bus::fake([SendReminderJob::class]);
        $service = app(ReminderService::class);

        $logs = $service->queueRegistrationNotice($this->customer);

        $this->assertSame(['email', 'whatsapp'], $logs->pluck('channel')->sort()->values()->all());
        $this->assertSame([ReminderLog::KIND_REGISTRATION], $logs->pluck('kind')->unique()->all());
        $this->assertSame(0, $logs->first()->invoices()->count());
        $this->assertSame('registration:'.$this->customer->id.':email', $logs->firstWhere('channel', 'email')->dedupe_key);
        Bus::assertDispatchedTimes(SendReminderJob::class, 2);

        // Dipanggil lagi: tidak dobel.
        $this->assertCount(0, $service->queueRegistrationNotice($this->customer));
        $this->assertSame(2, ReminderLog::query()->count());

        // Email kosong: hanya WhatsApp.
        $other = $this->customer(['email' => null, 'phone' => '0813-0000-1111']);
        $this->assertSame(['whatsapp'], $service->queueRegistrationNotice($other)->pluck('channel')->all());
    }

    public function test_email_contains_code_and_admin_contact_without_attachment(): void
    {
        Mail::fake();
        $log = $this->queue('email');

        $this->runJob($log);

        Mail::assertSent(CustomerRegisteredMail::class, fn (CustomerRegisteredMail $mail) => $mail->hasTo('rina@kafesenja.test')
            && $mail->attachments === []);
        $log->refresh();
        $this->assertSame(ReminderLog::STATUS_SENT, $log->status);
        $this->assertStringContainsString('Pendaftaran customer B2B '.$this->customer->code, $log->subject);
        $this->assertStringContainsString('Yth. Bu Rina', $log->body);
        $this->assertStringContainsString('0811-1111-2222', $log->body);
        $this->assertStringContainsString('https://wa.me/6281111112222', $log->body);
        $this->assertSame([], $log->attachments);
    }

    public function test_whatsapp_sends_one_text_with_admin_number(): void
    {
        $log = $this->queue('whatsapp');
        Http::fake([self::GATEWAY.'/send' => Http::response(['id' => 'TEXT1'])]);

        $this->runJob($log);

        Http::assertSentCount(1);
        $text = Http::recorded()->first()[0]->data()['text'];
        $this->assertStringContainsString('Yth. Bu Rina (Kafe Senja)', $text);
        $this->assertStringContainsString('Kode customer: *'.$this->customer->code.'*', $text);
        $this->assertStringContainsString('hubungi admin kami di *0811-1111-2222*', $text);
        $this->assertStringContainsString('Jika Anda tidak merasa mendaftar', $text);
        $this->assertSame(ReminderLog::STATUS_SENT, $log->fresh()->status);
    }

    public function test_admin_number_falls_back_to_billing_phone_then_email(): void
    {
        $settings = app(B2bSettings::class);
        Config::setValue(B2bSettings::ORDER_CONTACT_PHONE_KEY, '', 'Admin pemesanan', 'string');
        Config::setValue('b2b.invoice.phone', '0251-123456', 'Telepon penagihan', 'string');
        $this->assertSame('0251-123456', $settings->orderContactPhone());

        Config::setValue('b2b.invoice.phone', '', 'Telepon penagihan', 'string');
        $this->assertNull($settings->orderContactPhone());

        Mail::fake();
        $log = $this->queue('email');
        $this->runJob($log);
        $this->assertStringContainsString('silakan balas email ini', $log->fresh()->body);
    }

    public function test_deleted_customer_or_kill_switch_skips_the_notice(): void
    {
        Mail::fake();
        $email = $this->queue('email');
        $whatsapp = ReminderLog::query()->where('channel', 'whatsapp')->sole();

        app(B2bSettings::class)->setReminderChannelEnabled('whatsapp', false);
        $this->customer->delete();
        $this->runJob($email);
        $this->runJob($whatsapp);

        Mail::assertNothingSent();
        $this->assertSame('Customer sudah dihapus.', $email->fresh()->error);
        $this->assertSame(ReminderLog::STATUS_SKIPPED, $whatsapp->fresh()->status);
    }

    public function test_failed_notice_can_be_resent_but_not_sent_as_invoice_kind(): void
    {
        $outlet = $this->outlet();
        $user = $this->userFor($outlet, ['read b2b/reminders', 'send b2b/reminders']);
        $log = $this->queue('email');
        $log->forceFill(['status' => ReminderLog::STATUS_FAILED, 'error' => 'SMTP error'])->save();

        $this->actingAs($user)->postJson(route('b2b/reminders/resend', $log->id))
            ->assertOk()
            ->assertJsonPath('message', 'Pendaftaran dikirim ulang dan masuk antrean.');
        $new = ReminderLog::query()->latest('id')->first();
        $this->assertSame(ReminderLog::KIND_REGISTRATION, $new->kind);
        $this->assertSame(ReminderLog::TRIGGER_MANUAL, $new->trigger);
        $this->assertNull($new->dedupe_key);

        // Jenis pendaftaran tidak bisa dipilih di tombol "Kirim ke customer" invoice.
        $variant = $this->variant($outlet);
        $order = app(OrderService::class)->place($user, [
            'outlet_id' => $outlet->id,
            'b2b_customer_id' => $this->customer(['email' => 'finance@pelanggan.test'])->id,
            'items' => [['variant_id' => $variant->id, 'qty' => 1]],
            'delivery_method' => 'pickup',
            'due_date' => today()->addDays(7)->toDateString(),
        ]);
        $this->actingAs($user)->postJson(route('b2b/reminders/send-invoice', $order->invoice->id), ['kind' => 'registration', 'channels' => ['email']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('kind');
    }

    /** Customer dari form pendaftaran umum (registered_via portal) dengan waktu daftar tertentu. */
    private function portalCustomer(array $attributes = [], ?string $registeredAt = null): Customer
    {
        $customer = $this->customer(array_merge(['email' => 'baru.'.uniqid().'@pelanggan.test', 'phone' => '0812-9999-0000'], $attributes));
        $customer->forceFill(['registered_via' => Customer::REGISTERED_VIA_PORTAL, 'created_at' => $registeredAt ?? now()])->save();

        return $customer;
    }

    public function test_scheduled_command_notifies_new_portal_customers_once(): void
    {
        Bus::fake([SendReminderJob::class]);
        $new = $this->portalCustomer();
        $emailOnly = $this->portalCustomer(['phone' => null]);
        $this->portalCustomer([], now()->subDays(ReminderService::REGISTRATION_NOTICE_WINDOW_DAYS + 1)->toDateTimeString());
        $this->portalCustomer(['is_active' => false]);
        $this->portalCustomer()->delete();
        // $this->customer dari setUp = input backoffice: tidak dikirimi.

        $this->artisan('b2b:send-registration-notices')
            ->expectsOutput('Customer: 2, diantrekan: 3, dilewati: 0')
            ->assertSuccessful();

        $this->assertSame(
            [$new->id, $new->id, $emailOnly->id],
            ReminderLog::query()->orderBy('b2b_customer_id')->orderBy('channel')->pluck('b2b_customer_id')->all()
        );
        $this->assertSame(['email'], ReminderLog::query()->where('b2b_customer_id', $emailOnly->id)->pluck('channel')->all());

        // Menit berikutnya: tidak dobel.
        $this->artisan('b2b:send-registration-notices')->expectsOutput('Customer: 0, diantrekan: 0, dilewati: 0');
        $this->assertSame(3, ReminderLog::query()->count());
        Bus::assertDispatchedTimes(SendReminderJob::class, 3);
    }
}

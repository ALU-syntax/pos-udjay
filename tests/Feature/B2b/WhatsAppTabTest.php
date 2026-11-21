<?php

namespace Tests\Feature\B2b;

use App\Jobs\B2b\SendReminderJob;
use App\Models\B2b\ActivityLog;
use App\Models\B2b\Invoice;
use App\Models\B2b\ReminderLog;
use App\Models\Outlets;
use App\Models\User;
use App\Services\B2b\OrderService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

class WhatsAppTabTest extends B2bTestCase
{
    private const GATEWAY = 'http://wa-gateway.test';

    private Outlets $outlet;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.b2b_wa_gateway.url' => self::GATEWAY, 'services.b2b_wa_gateway.key' => str_repeat('k', 40)]);
        $this->outlet = $this->outlet();
        $this->admin = $this->userFor($this->outlet, ['read b2b/reminders', 'manage-whatsapp b2b/reminders', 'send b2b/reminders', 'read b2b/orders', 'read b2b/invoices']);
    }

    private function gatewayStatus(string $state = 'connected'): array
    {
        return ['state' => $state, 'me' => $state === 'connected' ? ['id' => '6281111111111:5@s.whatsapp.net', 'name' => 'Penagihan'] : null,
            'qr_available' => $state === 'qr', 'last_connected_at' => null, 'last_disconnect' => null, 'queue_pending' => 0];
    }

    private function invoice(array $customer = []): Invoice
    {
        $customer = $this->customer(array_merge(['email' => 'finance@pelanggan.test', 'phone' => '0812-3456-7890'], $customer));

        return app(OrderService::class)->place($this->admin, [
            'outlet_id' => $this->outlet->id, 'b2b_customer_id' => $customer->id, 'delivery_method' => 'pickup',
            'due_date' => today()->toDateString(), 'items' => [['variant_id' => $this->variant($this->outlet, 10000)->id, 'qty' => 1]],
        ])->invoice;
    }

    public function test_whatsapp_tab_renders_panel_or_setup_hint(): void
    {
        $this->actingAs($this->admin)->get(route('b2b/reminders', ['tab' => 'whatsapp']))
            ->assertOk()->assertSee('Nomor penagihan')->assertSee('Tautkan nomor')->assertSee('Pesan uji')->assertSee(route('b2b/reminders/whatsapp/status'));

        $reader = $this->userFor(null, ['read b2b/reminders']);
        $this->actingAs($reader)->get(route('b2b/reminders', ['tab' => 'whatsapp']))
            ->assertOk()->assertSee('Kelola WhatsApp')->assertDontSee('id="wa-test-form"', false)->assertDontSee('id="wa-connect"', false);

        config(['services.b2b_wa_gateway.url' => null]);
        $this->actingAs($this->admin)->get(route('b2b/reminders', ['tab' => 'whatsapp']))
            ->assertOk()->assertSee('Gateway WhatsApp belum dikonfigurasi')->assertDontSee('Nomor penagihan');
    }

    public function test_status_is_proxied_and_gateway_errors_are_readable(): void
    {
        // Satu fake dengan respons yang bisa diganti; Http::fake berulang tidak menimpa stub sebelumnya.
        $respond = fn () => Http::response($this->gatewayStatus());
        Http::fake(function () use (&$respond) {
            return $respond();
        });
        $this->actingAs($this->userFor(null))->getJson(route('b2b/reminders/whatsapp/status'))->assertForbidden();
        $this->actingAs($this->userFor(null, ['read b2b/reminders']))->getJson(route('b2b/reminders/whatsapp/status'))
            ->assertOk()->assertJsonPath('data.state', 'connected')->assertJsonPath('data.me.name', 'Penagihan');

        $respond = fn () => throw new ConnectionException('Connection refused');
        $this->actingAs($this->admin)->getJson(route('b2b/reminders/whatsapp/status'))
            ->assertStatus(503)->assertJsonPath('code', 'unreachable');

        $respond = fn () => Http::response(['error' => 'API key salah atau tidak ada.', 'code' => 'unauthorized'], 401);
        $this->actingAs($this->admin)->getJson(route('b2b/reminders/whatsapp/status'))
            ->assertStatus(503)->assertJsonPath('message', 'API key salah atau tidak ada.');
    }

    public function test_connect_qr_and_pairing_need_manage_permission(): void
    {
        Http::fake([
            self::GATEWAY.'/connect' => Http::response($this->gatewayStatus('connecting')),
            self::GATEWAY.'/qr' => Http::response(['qr' => 'data:image/png;base64,AAAA']),
            self::GATEWAY.'/pairing-code' => Http::response(['code' => 'ABCD1234']),
        ]);
        $reader = $this->userFor(null, ['read b2b/reminders', 'send b2b/reminders']);
        $this->actingAs($reader)->postJson(route('b2b/reminders/whatsapp/connect'))->assertForbidden();
        $this->actingAs($reader)->getJson(route('b2b/reminders/whatsapp/qr'))->assertForbidden();

        $this->actingAs($this->admin)->postJson(route('b2b/reminders/whatsapp/connect'))->assertOk()->assertJsonPath('data.state', 'connecting');
        $this->assertDatabaseHas('b2b_activity_logs', ['action' => 'whatsapp.connect', 'user_id' => $this->admin->id]);
        $this->actingAs($this->admin)->getJson(route('b2b/reminders/whatsapp/qr'))->assertOk()->assertJsonPath('data.qr', 'data:image/png;base64,AAAA');

        $this->actingAs($this->admin)->postJson(route('b2b/reminders/whatsapp/pairing-code'), ['phone' => '12'])->assertUnprocessable()->assertJsonValidationErrors('phone');
        $this->actingAs($this->admin)->postJson(route('b2b/reminders/whatsapp/pairing-code'), ['phone' => '0811-1111-1111'])
            ->assertOk()->assertJsonPath('data.code', 'ABCD1234');
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/pairing-code') && $request['phone'] === '6281111111111');
    }

    public function test_logout_requires_reason_and_is_logged(): void
    {
        Http::fake([
            self::GATEWAY.'/status' => Http::response($this->gatewayStatus()),
            self::GATEWAY.'/logout' => Http::response($this->gatewayStatus('logged_out')),
        ]);

        $this->actingAs($this->admin)->postJson(route('b2b/reminders/whatsapp/logout'))->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->actingAs($this->admin)->postJson(route('b2b/reminders/whatsapp/logout'), ['reason' => 'Ganti nomor penagihan'])
            ->assertOk()->assertJsonPath('data.state', 'logged_out');

        $log = ActivityLog::query()->where('action', 'whatsapp.logout')->firstOrFail();
        $this->assertSame('Ganti nomor penagihan', $log->changes['reason']);
        $this->assertSame('6281111111111:5@s.whatsapp.net', $log->changes['number']);
    }

    public function test_test_message_is_sent_to_normalized_number(): void
    {
        Http::fake([self::GATEWAY.'/send' => Http::response(['id' => 'MSG1', 'jid' => '6281234567890@s.whatsapp.net', 'to' => '6281234567890'])]);

        $this->actingAs($this->admin)->postJson(route('b2b/reminders/whatsapp/test'), ['phone' => 'abc'])->assertUnprocessable();
        $this->actingAs($this->admin)->postJson(route('b2b/reminders/whatsapp/test'), ['phone' => '0812 3456 7890'])
            ->assertOk()->assertJsonPath('data.to', '6281234567890');

        Http::assertSent(fn (Request $request) => $request['to'] === '6281234567890' && str_contains($request['text'], 'Pesan uji') && ! isset($request['document']));
        $this->assertDatabaseHas('b2b_activity_logs', ['action' => 'whatsapp.test']);
    }

    public function test_manual_send_can_pick_email_and_whatsapp(): void
    {
        Bus::fake([SendReminderJob::class]);
        $invoice = $this->invoice();

        $this->actingAs($this->admin)->get(route('b2b/orders/show', $invoice->b2b_order_id))
            ->assertOk()->assertSee('data-send-reminder', false)->assertSee('+6281234567890');

        $this->actingAs($this->admin)->postJson(route('b2b/reminders/send-invoice', $invoice->id), ['channels' => []])
            ->assertUnprocessable()->assertJsonValidationErrors('channels');
        $this->actingAs($this->admin)->postJson(route('b2b/reminders/send-invoice', $invoice->id))
            ->assertOk(); // tanpa parameter = email (perilaku lama)
        $this->actingAs($this->admin)->postJson(route('b2b/reminders/send-invoice', $invoice->id), ['channels' => ['email', 'whatsapp']])
            ->assertOk()->assertJsonPath('message', fn ($message) => str_contains($message, 'WhatsApp ke 6281234567890'));

        $this->assertSame(['email', 'email', 'whatsapp'], ReminderLog::query()->orderBy('id')->pluck('channel')->all());
        Bus::assertDispatchedTimes(SendReminderJob::class, 3);

        $this->actingAs($this->admin)->postJson(route('b2b/reminders/send-invoice', $invoice->id), ['channels' => ['sms']])
            ->assertUnprocessable()->assertJsonValidationErrors('channels.0');
        config(['services.b2b_wa_gateway.url' => null]);
        $this->actingAs($this->admin)->postJson(route('b2b/reminders/send-invoice', $invoice->id), ['channels' => ['whatsapp']])
            ->assertUnprocessable()->assertJsonValidationErrors('channels');
    }
}

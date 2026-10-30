<?php

namespace Tests\Feature\OrderTable;

use App\Jobs\SendOrderTablePush;
use App\Services\OrderTable\FcmSender;
use App\Services\OrderTable\PushRecipients;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

class OrderTableFcmTest extends CashierHttpTestCase
{
    public function test_qris_bridge_and_status_updates_after_paid_create_distinct_refresh_events(): void
    {
        $this->register();
        DB::table('ot_orders')->where('id', 1)->update(['payment_mode' => 'qris', 'payment_status' => 'paid']);
        foreach (['order.paid', 'order.bridge_updated', 'order.status_changed'] as $type) {
            $event = array_merge($this->event(), ['type' => $type]);
            $this->ingest($event)->assertStatus(202);
            $this->ingest($event)->assertStatus(202);
            $payload = DB::table('order_table_events')->where('event_id', $event['eventId'])->value('payload');
            $this->assertSame($type, json_decode($payload, true)['type']);
            $this->assertSame('qris', json_decode($payload, true)['paymentMode']);
        }
        $this->assertDatabaseCount('order_table_events', 3);
        $this->assertDatabaseCount('order_table_push_deliveries', 3);
    }

    public function test_event_and_deliveries_are_one_atomic_commit(): void
    {
        $this->register();
        DB::statement("CREATE TRIGGER fail_delivery BEFORE INSERT ON order_table_push_deliveries BEGIN SELECT RAISE(ABORT, 'private outbox failure'); END");
        $event = $this->event();
        $this->ingest($event)->assertStatus(503)->assertDontSee('private outbox');
        $this->assertDatabaseCount('order_table_events', 0);
        $this->assertDatabaseCount('order_table_push_deliveries', 0);
        DB::statement('DROP TRIGGER fail_delivery');
        $this->ingest($event)->assertStatus(202);
        $this->assertDatabaseCount('order_table_events', 1);
        $this->assertDatabaseCount('order_table_push_deliveries', 1);
    }

    public function test_expired_token_or_removed_outlet_skips_previously_queued_delivery(): void
    {
        $this->register();
        $this->ingest($this->event());
        DB::table('personal_access_tokens')->update(['expires_at' => now()->subMinute()]);
        $this->runDelivery();
        $this->assertDatabaseHas('order_table_push_deliveries', ['status' => 'skipped']);
        Http::assertNothingSent();
    }

    public function test_empty_recipient_event_is_not_retargeted_on_replay(): void
    {
        $event = $this->event();
        $this->ingest($event)->assertStatus(202);
        $this->register();
        $this->ingest($event)->assertStatus(202);
        $this->assertDatabaseCount('order_table_push_deliveries', 0);
    }

    public function test_delivery_rechecks_shift_and_recovers_abandoned_lease(): void
    {
        $this->register();
        $this->ingest($this->event());
        DB::table('order_table_push_deliveries')->update(['status' => 'sending', 'locked_until' => now()->addMinute()]);
        $this->runDelivery();
        $this->assertDatabaseHas('order_table_push_deliveries', ['status' => 'sending', 'attempts' => 0]);
        DB::table('shift_sessions')->update(['status' => 'CLOSED']);
        $this->travel(2)->minutes();
        $this->runDelivery();
        $this->assertDatabaseHas('order_table_push_deliveries', ['status' => 'skipped', 'attempts' => 1]);
        Http::assertNothingSent();
    }

    public function test_disabled_ingestion_does_not_accept_event(): void
    {
        config(['order-table.fcm.enabled' => false]);
        $this->ingest($this->event())->assertStatus(503)->assertJsonPath('code', 'FCM_DISABLED');
        $this->assertDatabaseCount('order_table_events', 0);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['order-table.fcm.enabled' => true, 'order-table.fcm.event_key' => 'node-test-key',
            'order-table.fcm.project_id' => 'test-project', 'order-table.fcm.credentials' => '/not-used']);
        Http::preventStrayRequests();
    }

    private function register(): void
    {
        $this->postJson('/api/v1/order-table/device/fcm-token', ['fcmToken' => 'test-fcm-token'])->assertOk();
    }

    private function event(?string $id = null): array
    {
        return ['eventId' => $id ?? (string) Str::uuid(), 'orderId' => 1, 'outletId' => 11, 'type' => 'order.placed'];
    }

    private function ingest(array $event)
    {
        return $this->withHeader('X-Internal-Api-Key', 'node-test-key')->postJson('/api/v1/internal/order-table/events', $event);
    }

    private function runDelivery(): void
    {
        $id = DB::table('order_table_push_deliveries')->value('id');
        (new SendOrderTablePush($id))->handle(app(FcmSender::class), app(PushRecipients::class));
    }

    private function oauth(): void
    {
        Cache::put('order-table-fcm-oauth-'.hash('sha256', '/not-used|test-project'), 'test-oauth', 3000);
    }

    public function test_ingestion_auth_dedup_conflict_and_encrypted_registration(): void
    {
        $event = $this->event();
        $this->postJson('/api/v1/internal/order-table/events', $event)->assertUnauthorized();
        $this->register();
        $stored = DB::table('order_table_devices')->value('fcm_token');
        $this->assertNotSame('test-fcm-token', $stored);
        $this->assertSame('test-fcm-token', Crypt::decryptString($stored));
        $this->ingest($event)->assertStatus(202);
        $this->ingest($event)->assertStatus(202);
        $this->assertDatabaseCount('order_table_events', 1);
        $this->assertDatabaseCount('order_table_push_deliveries', 1);
        $event['type'] = 'order.paid';
        $this->ingest($event)->assertStatus(409)->assertJsonPath('code', 'EVENT_ID_CONFLICT');
    }

    public function test_dispatch_sender_success_and_duplicate_job_are_safe(): void
    {
        $this->register();
        $this->ingest($this->event())->assertStatus(202);
        $this->oauth();
        Queue::fake();
        $this->artisan('order-table:dispatch-push')->assertSuccessful();
        Queue::assertPushed(SendOrderTablePush::class);
        Http::fake(['https://fcm.googleapis.com/*' => Http::response(['name' => 'projects/test/messages/1'])]);
        $this->runDelivery();
        $this->runDelivery();
        Http::assertSentCount(1);
        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer test-oauth')
            && $r['message']['token'] === 'test-fcm-token' && $r['message']['data']['outletId'] === '11');
        $this->assertDatabaseHas('order_table_push_deliveries', ['status' => 'sent', 'attempts' => 1]);
    }

    public function test_retry_and_unregistered_token_cleanup(): void
    {
        $this->register();
        $this->ingest($this->event());
        $this->oauth();
        Http::fake(['*' => Http::sequence()->push(['error' => 'private upstream'], 503)->push(['error' => ['details' => [[
            '@type' => 'type.googleapis.com/google.firebase.fcm.v1.FcmError', 'errorCode' => 'UNREGISTERED',
        ]]]], 404)]);
        $this->runDelivery();
        $this->assertDatabaseHas('order_table_push_deliveries', ['status' => 'pending', 'last_error' => 'FCM_DELIVERY_RETRY']);
        $this->assertNotNull(DB::table('order_table_devices')->value('fcm_token'));
        $this->travel(2)->minutes();
        $this->runDelivery();
        $this->assertDatabaseHas('order_table_push_deliveries', ['status' => 'invalid', 'attempts' => 2]);
        $this->assertNull(DB::table('order_table_devices')->value('fcm_token'));
    }

    public function test_invalid_argument_does_not_delete_registration_and_retries_exhaust(): void
    {
        $this->register();
        $this->ingest($this->event());
        $this->oauth();
        DB::table('order_table_push_deliveries')->update(['attempts' => 9]);
        Http::fake(['*' => Http::response(['error' => ['status' => 'INVALID_ARGUMENT']], 400)]);
        $this->runDelivery();
        $this->assertDatabaseHas('order_table_push_deliveries', ['status' => 'failed', 'attempts' => 10]);
        $this->assertNotNull(DB::table('order_table_devices')->value('fcm_token'));
        Queue::fake();
        $this->artisan('order-table:dispatch-push --retry-failed')->assertSuccessful();
        Queue::assertPushed(SendOrderTablePush::class);
    }

    public function test_logout_revokes_delivery_and_token_rotation_skips_stale_outbox(): void
    {
        $this->register();
        $this->ingest($this->event());
        $this->postJson('/api/v1/order-table/device/fcm-token', ['fcmToken' => 'new-token'])->assertOk();
        $this->runDelivery();
        $this->assertDatabaseHas('order_table_push_deliveries', ['status' => 'skipped']);
        $this->postJson('/api/v1/logout')->assertOk();
        $this->assertDatabaseCount('order_table_devices', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        Http::assertNothingSent();
    }

    public function test_closed_shift_and_other_outlet_do_not_receive_push(): void
    {
        $this->register();
        DB::table('shift_sessions')->update(['status' => 'CLOSED']);
        $this->ingest($this->event())->assertStatus(202);
        $this->assertDatabaseCount('order_table_push_deliveries', 0);
        $event = $this->event();
        $event['outletId'] = 22;
        $this->ingest($event)->assertNotFound();
        $this->deleteJson('/api/v1/order-table/device/fcm-token')->assertOk();
    }

    public function test_relogin_same_device_transfers_token_without_duplicate_recipients(): void
    {
        $this->register();
        $new = $this->cashier->createToken('relogin', ['*'], now()->addDay())->plainTextToken;
        $this->deviceHeaders($new);
        $this->register();
        $this->assertSame(1, DB::table('order_table_devices')->whereNotNull('fcm_token')->count());
        $this->ingest($this->event())->assertStatus(202);
        $this->assertDatabaseCount('order_table_push_deliveries', 1);
    }

    public function test_oauth_signs_rs256_assertion_and_sends_http_v1(): void
    {
        // Runtime-generated throwaway key lives only in memory, never in fixtures or repository.
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $private);
        $json = json_encode(['client_email' => 'test@example.invalid', 'private_key' => $private]);
        $path = tempnam(sys_get_temp_dir(), 'fcm-credentials-');
        file_put_contents($path, $json);
        config(['order-table.fcm.credentials' => $path]);
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'signed-oauth', 'expires_in' => 3600]),
            'https://fcm.googleapis.com/*' => Http::response(['name' => 'accepted']),
        ]);
        $this->assertSame('sent', app(FcmSender::class)->send('device-token', ['eventId' => 'test']));
        Http::assertSent(function ($request) use ($key) {
            if ($request->url() !== 'https://oauth2.googleapis.com/token') {
                return false;
            }
            [$header, $claims, $signature] = explode('.', $request['assertion']);

            return openssl_verify($header.'.'.$claims, base64_decode(strtr($signature, '-_', '+/')),
                openssl_pkey_get_details($key)['key'], OPENSSL_ALGO_SHA256) === 1;
        });
        @unlink($path);
    }
}

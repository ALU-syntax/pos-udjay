<?php

namespace Tests\Feature\OrderTable;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CashierHttpTest extends CashierHttpTestCase
{
    public function test_release_claim_and_disabled_method_are_safe(): void
    {
        $claim = $this->claimToken();
        DB::table('payments')->update(['status' => false]);
        $this->postJson('/api/v1/order-table/orders/1/pay', ['claimToken' => $claim, 'paymentId' => 1, 'nominalBayar' => 22000])
            ->assertStatus(422)->assertJsonPath('code', 'PAYMENT_INPUT_INVALID');
        $this->postJson('/api/v1/order-table/orders/1/claim', ['release' => true, 'claimToken' => $claim])
            ->assertOk()->assertJsonPath('data.collectionAllowed', false);
        $this->assertNotSame($claim, $this->claimToken());
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_delta_sync_includes_terminal_updates_and_rejects_invalid_cursor(): void
    {
        DB::table('ot_orders')->update(['status' => 'expired', 'updated_at' => now()->subMinute()]);
        $since = urlencode(now()->subMinutes(2)->toIso8601String());
        $this->getJson('/api/v1/order-table/orders?updated_since='.$since)->assertOk()->assertJsonPath('data.0.status', 'expired');
        $this->getJson('/api/v1/order-table/orders?cursor=not-a-cursor')->assertStatus(422)->assertJsonValidationErrors('cursor');
    }

    public function test_failed_payment_rolls_back_items_stock_and_commit_evidence(): void
    {
        $claim = $this->claimToken();
        DB::statement("CREATE TRIGGER fail_pos_item BEFORE INSERT ON transaction_items BEGIN SELECT RAISE(ABORT, 'private SQL failure'); END");
        $this->postJson('/api/v1/order-table/orders/1/pay', ['claimToken' => $claim, 'paymentId' => null, 'nominalBayar' => 22000])
            ->assertStatus(503)->assertJsonPath('code', 'ORDER_TABLE_RETRY_REQUIRED')->assertDontSee('private SQL');
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseHas('variant_products', ['id' => 1, 'stok' => 20]);
        $this->assertNull(DB::table('order_table_payment_claims')->value('transaction_id'));
        DB::statement('DROP TRIGGER fail_pos_item');
        $this->postJson('/api/v1/order-table/orders/1/pay', ['claimToken' => $claim, 'paymentId' => null, 'nominalBayar' => 22000])->assertOk();
    }

    public function test_disabled_feature_and_device_binding_cannot_be_bypassed(): void
    {
        config(['order-table.pay_at_cashier_enabled' => false]);
        $this->postJson('/api/v1/order-table/orders/1/claim')->assertStatus(503)->assertJsonPath('code', 'CASHIER_PAYMENT_DISABLED');
        DB::table('shift_sessions')->insert(['id' => 2, 'outlet_id' => 11, 'user_id' => $this->cashier->id,
            'petty_cash_id' => 1, 'android_id' => 'device-b', 'status' => 'ACTIVE']);
        $this->deviceHeaders(null, 'device-b', 2);
        $this->getJson('/api/v1/order-table/orders/1')->assertForbidden()->assertJsonPath('code', 'DEVICE_BINDING_MISMATCH');
    }

    public function test_snapshot_changes_after_claim_are_validated_before_payment(): void
    {
        $claim = $this->claimToken();
        DB::table('ot_order_items')->update(['variant_id' => null]);
        $this->postJson('/api/v1/order-table/orders/1/pay', ['claimToken' => $claim, 'paymentId' => null, 'nominalBayar' => 22000])
            ->assertStatus(422)->assertJsonPath('code', 'SNAPSHOT_MAPPING_INVALID');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_changed_amount_after_claim_requires_reconfirmation_without_collection(): void
    {
        $claim = $this->claimToken();
        DB::table('ot_orders')->where('id', 1)->update(['grand_total' => 23000]);
        DB::table('ot_order_payments')->update(['amount' => 23000]);
        $this->postJson('/api/v1/order-table/orders/1/pay', ['claimToken' => $claim, 'paymentId' => null, 'nominalBayar' => 25000])
            ->assertStatus(409)->assertJsonPath('code', 'SNAPSHOT_CHANGED');
        $this->postJson('/api/v1/order-table/orders/1/claim/heartbeat', ['claimToken' => $claim])
            ->assertStatus(409)->assertJsonPath('code', 'SNAPSHOT_CHANGED');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_generic_guard_is_executed_before_checkout_and_preserves_normal_validation(): void
    {
        $this->postJson('/api/v1/transactions/pay', ['reference_id' => 'OT-TEST-1'])
            ->assertStatus(409)->assertJsonPath('code', 'ORDER_TABLE_PAYMENT_REQUIRED');
        $this->postJson('/api/v1/transactions/pay', ['reference_id' => 'normal-pos'])
            ->assertStatus(422)->assertJsonValidationErrors('items');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_successful_snapshot_payment_cash_replay_and_expired_claim_recovery(): void
    {
        $token = $this->claimToken();
        $data = ['claimToken' => $token, 'paymentId' => null, 'nominalBayar' => 25000,
            'total' => 1, 'items' => [], 'customer_id' => 123, 'bill_id' => 999];
        $response = $this->postJson('/api/v1/order-table/orders/1/pay', $data)->assertOk()
            ->assertJsonPath('data.state', 'pos_committed')->assertJsonPath('data.collectionAllowed', false);
        $id = $response->json('data.posTransactionId');
        $this->assertDatabaseHas('transactions', ['id' => $id, 'total' => 22000, 'change' => 3000,
            'customer_id' => null, 'tipe_pembayaran' => null, 'nama_tipe_pembayaran' => 'Cash', 'total_modifier' => 2000,
            'atas_nama' => 'Budi']);
        $this->assertDatabaseCount('transaction_items', 2);
        $this->assertDatabaseHas('transaction_items', ['harga' => 10000, 'variant_id' => 1]);
        $this->assertDatabaseHas('variant_products', ['id' => 1, 'stok' => 18]);
        $this->travel(3)->minutes();
        $this->postJson('/api/v1/order-table/orders/1/pay', $data)->assertOk()->assertJsonPath('data.posTransactionId', $id);
        $this->postJson('/api/v1/order-table/orders/1/claim')->assertOk()->assertJsonPath('data.collectionAllowed', false);
        $this->getJson('/api/v1/order-table/orders/1/payment-state')->assertOk()->assertJsonPath('data.settleRequired', true);
        $this->getJson('/api/v1/order-table/orders/1/receipt')->assertOk()->assertJsonPath('data.transaction.id', $id)
            ->assertJsonPath('data.transaction.atas_nama', 'Budi')->assertJsonPath('data.order.atasNama', 'Budi');
        $this->getJson('/api/v1/order-table/orders/1/co')->assertStatus(409);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseHas('variant_products', ['id' => 1, 'stok' => 18]);
    }

    public function test_non_cash_payment_and_exclusive_tax_snapshot_are_not_repriced(): void
    {
        DB::table('ot_orders')->where('id', 1)->update(['grand_total' => 24000]);
        DB::table('ot_order_payments')->update(['amount' => 24000]);
        $this->postJson('/api/v1/order-table/orders/1/pay', ['claimToken' => $this->claimToken(), 'paymentId' => 1, 'nominalBayar' => 24000])->assertOk();
        $this->assertDatabaseHas('transactions', ['total' => 24000, 'tipe_pembayaran' => 1]);
    }

    public function test_competing_device_heartbeat_expiry_and_stale_token(): void
    {
        $claim = $this->claimToken();
        $second = $this->cashier->createToken('second', ['*'], now()->addDay())->plainTextToken;
        DB::table('shift_sessions')->insert(['id' => 2, 'outlet_id' => 11, 'user_id' => $this->cashier->id,
            'petty_cash_id' => 1, 'android_id' => 'device-b', 'status' => 'ACTIVE']);
        $this->deviceHeaders($second, 'device-b', 2);
        $this->postJson('/api/v1/order-table/orders/1/claim')->assertStatus(409)->assertJsonPath('code', 'ORDER_CLAIMED');
        $this->postJson('/api/v1/order-table/orders/1/pay', ['claimToken' => $claim, 'paymentId' => null, 'nominalBayar' => 22000])->assertStatus(409);
        $this->deviceHeaders();
        $this->travel(60)->seconds();
        $this->postJson('/api/v1/order-table/orders/1/claim/heartbeat', ['claimToken' => $claim])->assertOk()->assertJsonPath('data.claimToken', $claim);
        $this->travel(130)->seconds();
        $this->postJson('/api/v1/order-table/orders/1/claim/heartbeat', ['claimToken' => $claim])->assertStatus(409);
        $this->deviceHeaders($second, 'device-b', 2);
        $new = $this->claimToken();
        $this->assertNotSame($claim, $new);
    }

    public function test_snapshot_mapping_and_arithmetic_fail_without_pos_writes(): void
    {
        DB::table('ot_order_items')->update(['variant_id' => null]);
        $this->postJson('/api/v1/order-table/orders/1/claim')->assertStatus(422)->assertJsonPath('code', 'SNAPSHOT_MAPPING_INVALID');
        DB::table('ot_order_items')->update(['variant_id' => 1, 'modifier_total' => 2000]);
        $this->postJson('/api/v1/order-table/orders/1/claim')->assertStatus(422)->assertJsonPath('code', 'SNAPSHOT_INVALID');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_http_auth_outlet_and_closed_shift_gates(): void
    {
        $this->withHeader('Authorization', '')->getJson('/api/v1/order-table/orders')->assertUnauthorized();
        $this->deviceHeaders();
        $this->makeOrder(2, ['outlet_id' => 22]);
        $this->getJson('/api/v1/order-table/orders/2')->assertNotFound();
        DB::table('shift_sessions')->where('id', 1)->update(['status' => 'CLOSED']);
        $this->getJson('/api/v1/order-table/orders')->assertForbidden();
    }

    public function test_both_modes_pagination_and_paid_output_gates(): void
    {
        $this->makeOrder(2, ['payment_mode' => 'qris']);
        $this->travel(3)->seconds();
        DB::table('ot_orders')->update(['updated_at' => now()->subMinute()]);
        $first = $this->getJson('/api/v1/order-table/orders?limit=1')->assertOk();
        $this->assertTrue($first->json('meta.has_more'), $first->getContent());
        $this->getJson('/api/v1/order-table/orders?limit=1&cursor='.urlencode($first->json('meta.next_cursor')))
            ->assertOk()->assertJsonPath('data.0.paymentMode', 'qris');
        $this->postJson('/api/v1/order-table/orders/2/claim')->assertStatus(409)->assertJsonPath('code', 'CASHIER_MODE_REQUIRED');
        $this->getJson('/api/v1/order-table/orders/2/receipt')->assertStatus(409);
        $this->postJson('/api/v1/order-table/orders/2/serve')->assertStatus(409);
        DB::table('ot_orders')->where('id', 2)->update(['payment_status' => 'paid', 'status' => 'received']);
        $this->getJson('/api/v1/order-table/orders/2/co')->assertOk()
            ->assertJsonPath('data.atasNama', 'Budi')->assertJsonPath('data.notes', 'Minuman duluan');
        $this->getJson('/api/v1/order-table/orders/2/receipt')->assertStatus(409);
    }

    public function test_node_auth_safe_errors_settle_after_expiry_and_serve_audit(): void
    {
        config(['order-table.node.enabled' => true, 'order-table.node.base_url' => 'https://node.test', 'order-table.node.internal_api_key' => 'test-key']);
        $paid = $this->postJson('/api/v1/order-table/orders/1/pay', ['claimToken' => $this->claimToken(), 'paymentId' => null, 'nominalBayar' => 22000])->assertOk();
        $this->travel(3)->minutes();
        Http::fake(['*' => Http::sequence()->push(['code' => 'PAYMENT_NOT_PENDING', 'message' => 'SQL secret'], 422)
            ->push(['secret' => 'not-forwarded'])->push(['accepted' => true])]);
        $this->postJson('/api/v1/order-table/orders/1/settle', ['posTransactionId' => $paid->json('data.posTransactionId')])
            ->assertStatus(422)->assertJsonPath('code', 'PAYMENT_NOT_PENDING')->assertDontSee('SQL secret');
        Http::assertSent(fn ($r) => $r->hasHeader('X-Internal-Api-Key', 'test-key') && $r->hasHeader('X-Actor-Id', (string) $this->cashier->id) && ! $r->hasHeader('Authorization'));
        $this->postJson('/api/v1/order-table/orders/1/settle', ['posTransactionId' => $paid->json('data.posTransactionId')])->assertOk()->assertDontSee('not-forwarded');
        DB::table('ot_orders')->where('id', 1)->update(['payment_status' => 'paid', 'status' => 'received']);
        $this->postJson('/api/v1/order-table/orders/1/serve')->assertOk();
        $this->assertDatabaseHas('ot_audit_logs', ['action' => 'order.serve-requested']);
        $this->assertDatabaseCount('order_table_payment_claims', 1);
    }

    public function test_cashier_cancel_forwards_reason_to_node_and_releases_claim(): void
    {
        config(['order-table.node.enabled' => true, 'order-table.node.base_url' => 'https://node.test', 'order-table.node.internal_api_key' => 'test-key']);
        Http::fake(['*' => function () {
            // Node yang sebenarnya mengubah baris order; di test disimulasikan.
            DB::table('ot_orders')->where('id', 1)->update(['status' => 'cancelled', 'payment_status' => 'failed', 'cancel_reason' => 'Menu habis']);

            return Http::response(['id' => 1, 'status' => 'cancelled']);
        }]);

        $this->postJson('/api/v1/order-table/orders/1/cancel', [])->assertStatus(422)->assertJsonValidationErrors('reason');
        $this->postJson('/api/v1/order-table/orders/1/cancel', ['reason' => str_repeat('a', 161)])->assertStatus(422);
        Http::assertNothingSent();

        $this->postJson('/api/v1/order-table/orders/1/cancel', ['reason' => 'Menu habis'])->assertOk()
            ->assertJsonPath('data.status', 'cancelled')->assertJsonPath('data.cancelReason', 'Menu habis');
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/api/v1/internal/orders/1/cancel')
            && $r['reason'] === 'Menu habis' && $r->hasHeader('X-Actor-Id', (string) $this->cashier->id));
        $this->assertDatabaseHas('ot_audit_logs', ['action' => 'order.cancelled-by-cashier', 'subject_id' => 1]);
        $claim = DB::table('order_table_payment_claims')->where('order_id', 1)->first();
        $this->assertFalse(\Carbon\Carbon::parse($claim->expires_at)->isFuture());
    }

    public function test_cashier_cancel_guards_claims_commits_modes_and_node_outage(): void
    {
        config(['order-table.node.enabled' => true, 'order-table.node.base_url' => 'https://node.test', 'order-table.node.internal_api_key' => 'test-key']);
        Http::fake(['*' => Http::response(['message' => 'down'], 500)]);

        // Order QRIS tidak boleh dibatalkan dari kasir: QR masih bisa dibayar pelanggan.
        $this->makeOrder(2, ['payment_mode' => 'qris', 'payment_status' => 'pending']);
        $this->postJson('/api/v1/order-table/orders/2/cancel', ['reason' => 'Menu habis'])
            ->assertStatus(409)->assertJsonPath('code', 'CASHIER_MODE_REQUIRED');

        // Sedang diproses kasir lain.
        $claim = $this->claimToken();
        $second = $this->cashier->createToken('second', ['*'], now()->addDay())->plainTextToken;
        DB::table('shift_sessions')->insert(['id' => 2, 'outlet_id' => 11, 'user_id' => $this->cashier->id,
            'petty_cash_id' => 1, 'android_id' => 'device-b', 'status' => 'ACTIVE']);
        $this->deviceHeaders($second, 'device-b', 2);
        $this->postJson('/api/v1/order-table/orders/1/cancel', ['reason' => 'Menu habis'])
            ->assertStatus(409)->assertJsonPath('code', 'ORDER_CLAIMED');

        // Pemilik claim boleh membatalkan; Node mati → 503 dan claim dilepas agar order bisa diproses lagi.
        $this->deviceHeaders();
        $this->postJson('/api/v1/order-table/orders/1/cancel', ['reason' => 'Menu habis'])
            ->assertStatus(503)->assertJsonPath('code', 'ORDER_TABLE_UNAVAILABLE');
        $this->assertNotSame($claim, $this->claimToken());
        $this->assertDatabaseMissing('ot_audit_logs', ['action' => 'order.cancelled-by-cashier']);

        // Transaksi POS sudah tercatat (uang diterima) → tidak bisa dibatalkan.
        $this->postJson('/api/v1/order-table/orders/1/pay', ['claimToken' => $this->claimToken(), 'paymentId' => null, 'nominalBayar' => 22000])->assertOk();
        $this->postJson('/api/v1/order-table/orders/1/cancel', ['reason' => 'Menu habis'])
            ->assertStatus(409)->assertJsonPath('code', 'POS_TRANSACTION_EXISTS');
        Http::assertSentCount(1);
    }
}

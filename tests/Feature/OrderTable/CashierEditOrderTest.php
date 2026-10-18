<?php

namespace Tests\Feature\OrderTable;

use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** Kasir mengedit item order bayar-di-kasir sebelum bayar (Node = penghitung harga, disimulasikan). */
class CashierEditOrderTest extends CashierHttpTestCase
{
    private array $finalItems = [
        ['orderItemId' => 1, 'productId' => 1, 'variantId' => 1, 'qty' => 1, 'modifiers' => [['modifierId' => 10, 'qty' => 2]]],
        ['productId' => 1, 'variantId' => 1, 'qty' => 1, 'notes' => 'tanpa gula'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        config(['order-table.node.enabled' => true, 'order-table.node.base_url' => 'https://node.test',
            'order-table.node.internal_api_key' => 'test-key']);
    }

    /** Meniru PUT /internal/orders/1/items di Node: baris lama di-void, baris baru + total baru. */
    private function fakeNodeEdit(): void
    {
        Http::fake(['node.test/api/v1/internal/orders/1/items' => function () {
            DB::table('ot_order_items')->where('id', 1)->update(['status' => 'cancelled', 'voided_at' => now(), 'voided_by' => 'Kasir']);
            $keep = DB::table('ot_order_items')->insertGetId(['order_id' => 1, 'product_id' => 1, 'variant_id' => 1,
                'product_name' => 'Kopi', 'variant_name' => 'Regular', 'unit_price' => 10000, 'qty' => 1,
                'modifier_total' => 1000, 'line_total' => 11000, 'exclude_tax' => false, 'status' => 'pending']);
            DB::table('ot_order_item_modifiers')->insert(['order_item_id' => $keep, 'modifier_id' => 10, 'name' => 'Extra', 'harga' => 500, 'qty' => 2]);
            DB::table('ot_order_items')->insert(['order_id' => 1, 'product_id' => 1, 'variant_id' => 1,
                'product_name' => 'Kopi', 'variant_name' => 'Regular', 'unit_price' => 10000, 'qty' => 1,
                'modifier_total' => 0, 'line_total' => 10000, 'exclude_tax' => false, 'notes' => 'tanpa gula', 'status' => 'pending']);
            DB::table('ot_orders')->where('id', 1)->update(['subtotal' => 21000, 'modifier_total' => 1000, 'grand_total' => 21000]);
            DB::table('ot_order_payments')->where('order_id', 1)->update(['amount' => 21000]);

            return Http::response(['orderId' => 1, 'changed' => true, 'replay' => false, 'grandTotal' => 21000]);
        }]);
    }

    public function test_quote_requires_owned_claim_and_relays_only_safe_fields(): void
    {
        Http::fake(['*' => Http::response([
            'previousGrandTotal' => 22000, 'changed' => true, 'priceChanged' => false, 'voucherDropped' => false,
            'pricing' => ['subtotal' => 21000, 'grandTotal' => 21000, 'taxBreakdown' => []],
            'items' => [['productId' => 1, 'variantId' => 1, 'productName' => 'Kopi', 'quantity' => 1, 'lineTotal' => 11000,
                'modifiers' => [['modifierId' => 10, 'name' => 'Extra', 'price' => 500, 'quantity' => 2]]]],
            'internalSecret' => 'jangan-diteruskan',
        ])]);

        $this->postJson('/api/v1/order-table/orders/1/quote', ['claimToken' => (string) \Illuminate\Support\Str::uuid(), 'items' => $this->finalItems])
            ->assertStatus(409)->assertJsonPath('code', 'CLAIM_EXPIRED_OR_NOT_OWNED');
        Http::assertNothingSent();

        $this->postJson('/api/v1/order-table/orders/1/quote', ['claimToken' => $this->claimToken(), 'items' => $this->finalItems])
            ->assertOk()
            ->assertJsonPath('data.pricing.grandTotal', 21000)
            ->assertJsonPath('data.previousGrandTotal', 22000)
            ->assertJsonPath('data.items.0.qty', 1)
            ->assertJsonPath('data.items.0.modifiers.0.harga', 500)
            ->assertDontSee('jangan-diteruskan');
        Http::assertSent(fn (HttpRequest $r) => $r->method() === 'POST'
            && str_ends_with($r->url(), '/internal/orders/1/quote')
            && $r['items'][0] === ['orderItemId' => 1, 'productId' => 1, 'variantId' => 1, 'quantity' => 1, 'modifiers' => [['modifierId' => 10, 'quantity' => 2]]]
            && $r['items'][1]['note'] === 'tanpa gula' && $r['dropVoucher'] === false);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_pay_with_items_saves_edit_first_then_charges_the_new_total(): void
    {
        $claim = $this->claimToken();
        $this->fakeNodeEdit();

        $this->postJson('/api/v1/order-table/orders/1/pay', [
            'claimToken' => $claim, 'paymentId' => null, 'nominalBayar' => 25000, 'items' => $this->finalItems,
        ])->assertOk()->assertJsonPath('data.state', 'pos_committed');

        Http::assertSent(fn (HttpRequest $r) => $r->method() === 'PUT'
            && str_starts_with($r->header('Idempotency-Key')[0] ?? '', 'cashier-edit-1-'));
        $transaction = DB::table('transactions')->first();
        $this->assertSame(21000, (int) $transaction->total);
        $this->assertSame(4000, (int) $transaction->change);
        // 1 baris aktif qty 1 + 1 baris tambahan qty 1 → 2 item POS; baris yang di-void tidak ikut.
        $this->assertSame(2, DB::table('transaction_items')->count());
        $this->assertSame(1, DB::table('transaction_items')->where('catatan', 'tanpa gula')->count());
        $this->assertDatabaseHas('ot_audit_logs', ['action' => 'order.items-edited-by-cashier']);

        // Payload kasir hanya berisi item yang berlaku.
        $this->getJson('/api/v1/order-table/orders/1')->assertOk()
            ->assertJsonCount(2, 'data.items')->assertJsonPath('data.grandTotal', 21000);
    }

    public function test_voucher_no_longer_eligible_is_relayed_with_reason_and_nothing_is_charged(): void
    {
        $claim = $this->claimToken();
        Http::fake(['*' => Http::response([
            'code' => 'VOUCHER_NO_LONGER_ELIGIBLE', 'message' => 'Voucher tidak lagi memenuhi syarat setelah perubahan.',
            'details' => ['reason' => 'VOUCHER_MIN_SPEND', 'message' => 'Minimum belanja voucher belum terpenuhi.', 'voucherCode' => 'HEMAT10'],
            'requestId' => 'secret-request',
        ], 409)]);

        $this->postJson('/api/v1/order-table/orders/1/pay', [
            'claimToken' => $claim, 'paymentId' => null, 'nominalBayar' => 22000, 'items' => $this->finalItems,
        ])->assertStatus(409)
            ->assertJsonPath('code', 'VOUCHER_NO_LONGER_ELIGIBLE')
            ->assertJsonPath('details.reason', 'VOUCHER_MIN_SPEND')
            ->assertJsonPath('details.voucherCode', 'HEMAT10')
            ->assertDontSee('secret-request');
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('ot_audit_logs', 0);
    }

    public function test_pay_without_items_keeps_the_existing_flow(): void
    {
        Http::fake();
        $this->postJson('/api/v1/order-table/orders/1/pay', ['claimToken' => $this->claimToken(), 'paymentId' => null, 'nominalBayar' => 22000])
            ->assertOk();
        Http::assertNothingSent();
        $this->assertSame(22000, (int) DB::table('transactions')->value('total'));
    }

    public function test_edit_request_is_validated_before_reaching_node(): void
    {
        Http::fake();
        $claim = $this->claimToken();
        $this->postJson('/api/v1/order-table/orders/1/quote', ['claimToken' => $claim, 'items' => []])
            ->assertStatus(422)->assertJsonValidationErrors('items');
        $this->postJson('/api/v1/order-table/orders/1/quote', ['claimToken' => $claim, 'items' => [['productId' => 1, 'variantId' => 1, 'qty' => 0]]])
            ->assertStatus(422)->assertJsonValidationErrors('items.0.qty');
        Http::assertNothingSent();
    }
}

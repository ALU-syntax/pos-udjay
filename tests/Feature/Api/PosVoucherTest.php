<?php

namespace Tests\Feature\Api;

use App\Models\OrderTable\Voucher;
use App\Models\Outlets;
use App\Models\PettyCash;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Voucher Order Table di transaksi POS reguler: hitung lewat Node, catat pemakaian saat transaksi masuk. */
class PosVoucherTest extends TestCase
{
    use DatabaseTransactions;

    private Outlets $outlet;

    private User $user;

    private PettyCash $pettyCash;

    protected function setUp(): void
    {
        parent::setUp();
        config(['order-table.node.enabled' => true, 'order-table.node.base_url' => 'https://node.test',
            'order-table.node.internal_api_key' => 'test-key', 'cache.default' => 'array']);

        $this->outlet = Outlets::create(['name' => 'Outlet Voucher POS', 'address' => 'Alamat', 'phone' => '0812']);
        $this->user = User::factory()->create([
            'username' => 'kasir_voucher_'.uniqid(), 'status' => '1', 'role' => 1,
            'outlet_id' => json_encode([$this->outlet->id]),
        ]);
        $this->pettyCash = PettyCash::create([
            'outlet_id' => (string) $this->outlet->id, 'amount_awal' => 100000,
            'user_id_started' => $this->user->id, 'open' => now()->subHour(),
        ]);
    }

    private function voucher(array $attributes = []): Voucher
    {
        return Voucher::create(array_merge([
            'outlet_id' => $this->outlet->id, 'code' => 'POS'.strtoupper(Str::random(8)), 'type' => 'fixed',
            'value' => 5000, 'min_spend' => 0, 'scope' => 'all', 'status' => true,
        ], $attributes));
    }

    private function payPayload(array $diskonAllItem, ?string $reference = null): array
    {
        return [
            'reference_id' => $reference ?? (string) Str::uuid(),
            'patty_cash_id' => $this->pettyCash->id,
            'total' => 15000, 'nominal_bayar' => 15000, 'change' => 0,
            'category_payment_id' => 1, 'nama_tipe_pembayaran' => 'Cash',
            'diskon_all_item' => $diskonAllItem,
            'items' => [['harga' => 20000, 'quantity' => 1, 'catatan' => 'Test']],
        ];
    }

    private function redemptions(Voucher $voucher): int
    {
        return DB::table('ot_voucher_redemptions')->where('voucher_id', $voucher->id)->count();
    }

    public function test_quote_sends_the_cart_to_node_and_returns_the_discount_entry_for_the_transaction(): void
    {
        Http::fake(['*' => Http::response([
            'voucher' => ['id' => 9, 'code' => 'HEMAT10', 'type' => 'percent', 'value' => 10, 'scope' => 'all'],
            'subtotal' => 50000, 'eligibleSubtotal' => 50000, 'discount' => 5000, 'internalSecret' => 'jangan-diteruskan',
        ])]);

        $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/vouchers/quote', [
            'code' => ' hemat10 ',
            'lines' => [['product_id' => 7, 'amount' => 45000], ['product_id' => -1, 'amount' => 5000]],
        ])->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.discount', 5000)
            ->assertJsonPath('data.discount_id', 'ot-voucher:9')
            ->assertJsonPath('data.discount_name', 'Voucher HEMAT10')
            ->assertJsonPath('data.voucher.type', 'percent')
            ->assertDontSee('jangan-diteruskan');

        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/api/v1/internal/vouchers/quote')
            && $r['outletId'] === $this->outlet->id && $r['voucherCode'] === 'HEMAT10'
            // Item custom (sentinel -1) dikirim tanpa produk.
            && $r['lines'] === [['productId' => 7, 'amount' => 45000], ['productId' => null, 'amount' => 5000]]);
    }

    public function test_quote_relays_the_reason_when_node_rejects_the_voucher(): void
    {
        Http::fake(['*' => Http::response([
            'code' => 'VOUCHER_MIN_SPEND', 'message' => 'Minimum belanja voucher belum terpenuhi.', 'requestId' => 'secret-request',
        ], 422)]);

        $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/vouchers/quote', [
            'code' => 'HEMAT10', 'lines' => [['product_id' => 7, 'amount' => 1000]],
        ])->assertStatus(422)
            ->assertJsonPath('code', 'VOUCHER_MIN_SPEND')
            ->assertJsonPath('message', 'Minimum belanja voucher belum terpenuhi.')
            ->assertDontSee('secret-request');
    }

    public function test_quote_is_validated_and_needs_login_and_a_reachable_node(): void
    {
        Http::fake();
        $this->postJson('/api/v1/vouchers/quote', ['code' => 'X', 'lines' => [['amount' => 1]]])->assertUnauthorized();
        $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/vouchers/quote', ['code' => 'HEMAT10', 'lines' => []])
            ->assertStatus(422)->assertJsonValidationErrors('lines');
        $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/vouchers/quote', [
            'code' => 'HEMAT10', 'lines' => [['product_id' => 7, 'amount' => -5]],
        ])->assertStatus(422)->assertJsonValidationErrors('lines.0.amount');
        Http::assertNothingSent();

        config(['order-table.node.enabled' => false]);
        $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/vouchers/quote', [
            'code' => 'HEMAT10', 'lines' => [['product_id' => 7, 'amount' => 1000]],
        ])->assertStatus(503)->assertJsonPath('code', 'ORDER_TABLE_UNAVAILABLE');
    }

    public function test_pos_transaction_with_a_voucher_records_one_redemption_and_uses_quota(): void
    {
        $voucher = $this->voucher();
        $reference = (string) Str::uuid();
        $payload = $this->payPayload([
            ['id' => 'ot-voucher:'.$voucher->id, 'nama' => 'Voucher '.$voucher->code, 'satuan' => 'rupiah', 'value' => 5000],
        ], $reference);

        $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/transactions/pay', $payload)->assertOk();

        $transaction = Transaction::where('reference_id', $reference)->firstOrFail();
        $this->assertSame(5000, (int) $transaction->total_diskon);
        $this->assertDatabaseHas('ot_voucher_redemptions', [
            'voucher_id' => $voucher->id, 'source' => 'pos', 'outlet_id' => $this->outlet->id,
            'pos_reference' => $reference, 'order_id' => null, 'session_id' => null, 'device_id' => null,
            'discount_amount' => 5000, 'user_agent' => 'kasir:'.$this->user->name,
        ]);
        $this->assertSame(1, $voucher->fresh()->quota_used);

        // Kasir mengirim ulang transaksi yang sama (retry sync): kuota tidak berkurang dua kali.
        $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/transactions/pay', $payload)->assertOk();
        $this->assertSame(1, $this->redemptions($voucher));
        $this->assertSame(1, $voucher->fresh()->quota_used);
    }

    public function test_voucher_is_still_recorded_when_quota_is_already_full_because_the_customer_has_paid(): void
    {
        $voucher = $this->voucher(['quota_total' => 1]);
        DB::table('ot_vouchers')->where('id', $voucher->id)->update(['quota_used' => 1]);

        $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/transactions/pay', $this->payPayload([
            ['id' => 'ot-voucher:'.$voucher->id, 'nama' => 'Voucher '.$voucher->code, 'satuan' => 'rupiah', 'value' => 5000],
        ]))->assertOk();

        $this->assertSame(1, $this->redemptions($voucher));
        $this->assertSame(2, $voucher->fresh()->quota_used);
    }

    public function test_unknown_or_foreign_voucher_never_blocks_the_transaction_and_is_not_recorded(): void
    {
        $otherOutlet = Outlets::create(['name' => 'Outlet Lain', 'address' => 'Alamat', 'phone' => '0813']);
        $foreign = $this->voucher(['outlet_id' => $otherOutlet->id]);

        foreach (['ot-voucher:'.$foreign->id, 'ot-voucher:999999999', 'ot-voucher:abc'] as $discountId) {
            $reference = (string) Str::uuid();
            $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/transactions/pay', $this->payPayload([
                ['id' => $discountId, 'nama' => 'Voucher', 'satuan' => 'rupiah', 'value' => 5000],
            ], $reference))->assertOk();
            $this->assertDatabaseHas('transactions', ['reference_id' => $reference]);
            $this->assertDatabaseMissing('ot_voucher_redemptions', ['pos_reference' => $reference]);
        }
        $this->assertSame(0, $foreign->fresh()->quota_used);
    }

    public function test_regular_discount_and_global_voucher_are_told_apart(): void
    {
        $global = $this->voucher(['outlet_id' => null]);

        // Diskon katalog biasa memakai id angka dan bukan pemakaian voucher.
        $plain = (string) Str::uuid();
        $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/transactions/pay', $this->payPayload([
            ['id' => $global->id, 'nama' => 'Diskon Member', 'satuan' => 'nominal', 'value' => 5000],
        ], $plain))->assertOk();
        $this->assertDatabaseMissing('ot_voucher_redemptions', ['pos_reference' => $plain]);

        // Voucher global (tanpa outlet) berlaku di outlet mana pun.
        $withVoucher = (string) Str::uuid();
        $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/transactions/pay', $this->payPayload([
            ['id' => 'ot-voucher:'.$global->id, 'nama' => 'Voucher', 'satuan' => 'rupiah', 'value' => 5000],
        ], $withVoucher))->assertOk();
        $this->assertDatabaseHas('ot_voucher_redemptions', ['pos_reference' => $withVoucher, 'voucher_id' => $global->id]);
        $this->assertSame(1, $global->fresh()->quota_used);
    }
}

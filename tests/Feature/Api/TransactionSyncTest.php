<?php

namespace Tests\Feature\Api;

use App\Models\Outlets;
use App\Models\PettyCash;
use App\Models\RefundTransaction;
use App\Models\ShiftSession;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class TransactionSyncTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Data tes diberi updated_at jauh di masa depan agar terisolasi
     * dari data transaksi nyata yang sudah ada di database.
     */
    private const FUTURE_UPDATED_AT = '2030-01-01 00:00:00';
    private const SINCE_BOUNDARY    = '2029-12-31T00:00:00Z';

    private Outlets $outlet;
    private User $user;
    private PettyCash $pettyCash;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outlet = Outlets::first() ?? Outlets::create([
            'name' => 'Outlet Test',
            'address' => 'Alamat Test',
            'phone' => '08123456789',
        ]);

        $this->user = User::factory()->create([
            'username' => 'testuser_' . uniqid(),
            'status' => '1',
            'role' => 1,
            'outlet_id' => json_encode([$this->outlet->id]),
        ]);

        $this->pettyCash = PettyCash::create([
            'outlet_id' => (string) $this->outlet->id,
            'amount_awal' => 100000,
            'user_id_started' => $this->user->id,
            'open' => now()->subHours(2),
            'close' => null,
        ]);
    }

    private function makeShiftSession(string $androidId): ShiftSession
    {
        return ShiftSession::create([
            'petty_cash_id' => $this->pettyCash->id,
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'status' => 'ACTIVE',
            'device_name' => 'Device ' . $androidId,
            'android_id' => $androidId,
            'last_sync_at' => now(),
        ]);
    }

    private function makeTransaction(?int $shiftSessionId, array $attrs = []): Transaction
    {
        $transaction = Transaction::create(array_merge([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->user->id,
            'patty_cash_id' => $this->pettyCash->id,
            'shift_session_id' => $shiftSessionId,
            'total' => 15000,
            'nominal_bayar' => 15000,
            'change' => 0,
            'category_payment_id' => 1,
            'nama_tipe_pembayaran' => 'Cash',
            'total_pajak' => '[]',
            'diskon_all_item' => '[]',
            'reference_id' => 'ref-' . Str::uuid(),
        ], $attrs));

        DB::table('transactions')
            ->where('id', $transaction->id)
            ->update(['updated_at' => $attrs['updated_at'] ?? self::FUTURE_UPDATED_AT]);

        return $transaction->fresh();
    }

    private function makeItem(Transaction $transaction, array $attrs = []): TransactionItem
    {
        return TransactionItem::create(array_merge([
            'transaction_id' => $transaction->id,
            'product_id' => null,
            'variant_id' => null,
            'harga' => 15000,
            'discount_id' => '[]',
            'modifier_id' => '[]',
            'promo_id' => '[]',
            'catatan' => 'Normal',
            'reward_item' => 0,
        ], $attrs));
    }

    public function test_sync_requires_authentication()
    {
        $this->getJson('/api/v1/transactions/sync?patty_cash_id=' . $this->pettyCash->id)
            ->assertStatus(401);
    }

    public function test_sync_requires_patty_cash_id()
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/transactions/sync')
            ->assertStatus(422);
    }

    public function test_sync_returns_transactions_from_all_devices_in_same_shift()
    {
        $sessionA = $this->makeShiftSession('android-A');
        $sessionB = $this->makeShiftSession('android-B');

        $txA = $this->makeTransaction($sessionA->id);
        $txB = $this->makeTransaction($sessionB->id);

        $this->makeItem($txA);
        $this->makeItem($txB);

        $response = $this->actingAs($this->user, 'sanctum')->getJson(
            '/api/v1/transactions/sync?patty_cash_id=' . $this->pettyCash->id
            . '&updated_since=' . urlencode(self::SINCE_BOUNDARY) . '&limit=100'
        );

        $response->assertStatus(200)->assertJson(['status' => 'success']);

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($txA->id), 'Transaksi device A harus ikut terkirim.');
        $this->assertTrue($ids->contains($txB->id), 'Transaksi device B harus ikut terkirim.');

        // shift_session_id penanda device asal
        $rowA = collect($response->json('data'))->firstWhere('id', $txA->id);
        $rowB = collect($response->json('data'))->firstWhere('id', $txB->id);
        $this->assertSame($sessionA->id, $rowA['shift_session_id']);
        $this->assertSame($sessionB->id, $rowB['shift_session_id']);
    }

    public function test_sync_includes_items_inline()
    {
        $session = $this->makeShiftSession('android-items');
        $tx = $this->makeTransaction($session->id);
        $this->makeItem($tx, ['catatan' => 'Pedas']);
        $this->makeItem($tx, ['catatan' => 'Pedas']);

        $response = $this->actingAs($this->user, 'sanctum')->getJson(
            '/api/v1/transactions/sync?patty_cash_id=' . $this->pettyCash->id
            . '&updated_since=' . urlencode(self::SINCE_BOUNDARY)
        );

        $response->assertStatus(200);

        $row = collect($response->json('data'))->firstWhere('id', $tx->id);
        $this->assertNotNull($row);
        $this->assertCount(2, $row['items']);
        $this->assertArrayHasKey('refund', $row['items'][0]);
        $this->assertNull($row['items'][0]['refund']);
    }

    public function test_sync_includes_refund_data_inline_for_refunded_item()
    {
        $session = $this->makeShiftSession('android-refund');
        $tx = $this->makeTransaction($session->id);
        $item = $this->makeItem($tx);

        $refund = RefundTransaction::create([
            'transaction_id' => $tx->id,
            'payment_method' => 'Cash',
            'nominal_refund' => 15000,
            'catatan' => 'Salah pesan',
        ]);

        $item->refund_at = now();
        $item->refund_transaction_id = $refund->id;
        $item->save();

        $response = $this->actingAs($this->user, 'sanctum')->getJson(
            '/api/v1/transactions/sync?patty_cash_id=' . $this->pettyCash->id
            . '&updated_since=' . urlencode(self::SINCE_BOUNDARY)
        );

        $response->assertStatus(200);

        $row = collect($response->json('data'))->firstWhere('id', $tx->id);
        $this->assertNotNull($row);

        $itemRow = collect($row['items'])->firstWhere('id', $item->id);
        $this->assertNotNull($itemRow);
        $this->assertNotNull($itemRow['refund_at']);
        $this->assertNotNull($itemRow['refund'], 'Data refund harus inline untuk item yang di-refund.');
        $this->assertSame($refund->id, $itemRow['refund']['id']);
        $this->assertSame('Cash', $itemRow['refund']['payment_method']);
        $this->assertSame(15000.0, (float) $itemRow['refund']['nominal_refund']);
    }

    public function test_sync_paginates_with_cursor_without_duplicates()
    {
        $session = $this->makeShiftSession('android-page');

        $created = collect();
        for ($i = 0; $i < 7; $i++) {
            $created->push($this->makeTransaction($session->id));
        }

        $seen = [];
        $cursor = null;
        $pages = 0;

        do {
            $query = '/api/v1/transactions/sync?patty_cash_id=' . $this->pettyCash->id
                . '&updated_since=' . urlencode(self::SINCE_BOUNDARY) . '&limit=3';

            if ($cursor) {
                $query = '/api/v1/transactions/sync?patty_cash_id=' . $this->pettyCash->id
                    . '&limit=3&cursor=' . urlencode($cursor);
            }

            $response = $this->actingAs($this->user, 'sanctum')->getJson($query);
            $response->assertStatus(200);

            foreach ($response->json('data') as $row) {
                $seen[] = $row['id'];
            }

            $cursor = $response->json('next_cursor');
            $pages++;

            $this->assertLessThan(20, $pages, 'Pagination tidak berhenti (kemungkinan infinite loop).');
        } while (!empty($cursor));

        $this->assertSame(count($seen), count(array_unique($seen)), 'Terdapat duplikat antar halaman.');

        foreach ($created as $c) {
            $this->assertContains($c->id, $seen, "Transaksi #{$c->id} tidak terambil saat pagination.");
        }
    }

    public function test_sync_includes_soft_deleted_transaction_with_flag()
    {
        $session = $this->makeShiftSession('android-deleted');
        $tx = $this->makeTransaction($session->id);

        $tx->delete();

        DB::table('transactions')
            ->where('id', $tx->id)
            ->update(['updated_at' => self::FUTURE_UPDATED_AT]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson(
            '/api/v1/transactions/sync?patty_cash_id=' . $this->pettyCash->id
            . '&updated_since=' . urlencode(self::SINCE_BOUNDARY)
        );

        $response->assertStatus(200);

        $row = collect($response->json('data'))->firstWhere('id', $tx->id);
        $this->assertNotNull($row, 'Transaksi soft-deleted harus ikut terkirim.');
        $this->assertTrue($row['is_deleted']);
        $this->assertNotNull($row['deleted_at']);
    }

    public function test_sync_delta_returns_only_recently_updated()
    {
        $session = $this->makeShiftSession('android-delta');

        $old = $this->makeTransaction($session->id);
        DB::table('transactions')->where('id', $old->id)->update(['updated_at' => now()->subDays(10)]);

        $fresh = $this->makeTransaction($session->id);

        $since = now()->subDay()->utc()->toIso8601String();

        $response = $this->actingAs($this->user, 'sanctum')->getJson(
            '/api/v1/transactions/sync?patty_cash_id=' . $this->pettyCash->id
            . '&updated_since=' . urlencode($since)
        );

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($fresh->id), 'Transaksi baru harus ikut terkirim.');
        $this->assertFalse($ids->contains($old->id), 'Transaksi lama tidak boleh ikut terkirim.');
    }

    public function test_sync_rejects_petty_cash_from_other_outlet()
    {
        $otherOutlet = Outlets::create([
            'name' => 'Outlet Lain ' . Str::random(4),
            'address' => 'Alamat Lain',
            'phone' => '0899999999',
        ]);

        $otherPettyCash = PettyCash::create([
            'outlet_id' => (string) $otherOutlet->id,
            'amount_awal' => 50000,
            'user_id_started' => $this->user->id,
            'open' => now(),
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/transactions/sync?patty_cash_id=' . $otherPettyCash->id)
            ->assertStatus(404)
            ->assertJson(['status' => 'error']);
    }

    public function test_sync_rejects_invalid_cursor()
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/transactions/sync?patty_cash_id=' . $this->pettyCash->id . '&cursor=invalid!!')
            ->assertStatus(422)
            ->assertJson(['status' => 'error']);
    }

    public function test_sync_rejects_invalid_updated_since()
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/transactions/sync?patty_cash_id=' . $this->pettyCash->id . '&updated_since=kemarin')
            ->assertStatus(422)
            ->assertJson(['status' => 'error']);
    }
}

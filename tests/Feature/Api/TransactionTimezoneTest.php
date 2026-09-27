<?php

namespace Tests\Feature\Api;

use App\Models\Outlets;
use App\Models\PettyCash;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TransactionTimezoneTest extends TestCase
{
    use DatabaseTransactions;

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
            'open' => now()->subHours(5),
        ]);
    }

    public function test_pay_converts_utc_iso_created_at_to_wib_timezone()
    {
        $outlet = $this->outlet;
        $user = $this->user;
        $pettyCash = $this->pettyCash;

        // Input UTC dari Android Instant.now()
        // 19:11:06 UTC tanggal 14 Sept = 02:11:06 WIB tanggal 15 Sept (+7 jam)
        $utcCreatedAt = '2026-09-14T19:11:06.210111Z';

        $payload = [
            'patty_cash_id'        => $pettyCash->id,
            'total'                => 15000,
            'nominal_bayar'        => 15000,
            'change'               => 0,
            'category_payment_id'  => 1,
            'nama_tipe_pembayaran' => 'Cash',
            'created_at'           => $utcCreatedAt,
            'items'                => [
                [
                    'harga'    => 15000,
                    'quantity' => 1,
                    'catatan'  => 'Test',
                ],
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions/pay', $payload);

        $response->assertStatus(200);

        $transactionId = $response->json('id');
        $transaction = Transaction::find($transactionId);

        $this->assertNotNull($transaction);
        // Harus tersimpan sebagai 2026-09-15 02:11:06 (WIB)
        $this->assertSame('2026-09-15 02:11:06', Carbon::parse($transaction->getRawOriginal('created_at'))->format('Y-m-d H:i:s'));
    }

    public function test_pay_normalizes_negative_custom_item_ids_to_null()
    {
        // Aplikasi Android versi lama mengirim sentinel -1 untuk item custom.
        // product_id/variant_id bertipe unsigned, sehingga -1 ditolak MySQL
        // (SQLSTATE 22003). Backend harus menormalkannya menjadi null.
        $payload = [
            'patty_cash_id'        => $this->pettyCash->id,
            'total'                => 273,
            'nominal_bayar'        => 273,
            'change'               => 0,
            'category_payment_id'  => 1,
            'nama_tipe_pembayaran' => 'Cash',
            'items'                => [
                [
                    'product_id' => -1,
                    'variant_id' => -1,
                    'harga'      => 273,
                    'quantity'   => 1,
                    'catatan'    => null,
                ],
            ],
        ];

        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/v1/transactions/pay', $payload);

        $response->assertStatus(200)->assertJson(['status' => 'success']);

        $item = TransactionItem::where('transaction_id', $response->json('id'))->first();

        $this->assertNotNull($item);
        $this->assertNull($item->product_id);
        $this->assertNull($item->variant_id);
        $this->assertSame(273, (int) $item->harga);
    }
}

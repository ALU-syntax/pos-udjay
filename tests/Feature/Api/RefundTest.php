<?php

namespace Tests\Feature\Api;

use App\Models\Outlets;
use App\Models\PettyCash;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\VariantProduct;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_refund_transaction_item()
    {
        $outlet = Outlets::first() ?? Outlets::create([
            'name' => 'Outlet Test',
            'address' => 'Alamat Test',
            'phone' => '08123456789',
        ]);

        $user = User::factory()->create([
            'username' => 'testuser_' . uniqid(),
            'status' => '1',
            'role' => 1,
            'outlet_id' => json_encode([$outlet->id]),
        ]);

        // Buat petty cash sendiri agar tidak bergantung pada id hardcode 1.
        $pettyCash = PettyCash::create([
            'outlet_id' => (string) $outlet->id,
            'amount_awal' => 100000,
            'user_id_started' => $user->id,
            'open' => now()->subHours(2),
            'close' => null,
        ]);

        $transaction = Transaction::create([
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'patty_cash_id' => $pettyCash->id,
            'total' => 30000,
            'nominal_bayar' => 30000,
            'change' => 0,
            'total_pajak' => '[]',
            'diskon_all_item' => '[]',
        ]);

        // Buat produk & varian sendiri agar tidak bergantung pada id hardcode 1.
        $product = Product::create([
            'name' => 'Produk Refund Test ' . uniqid(),
            'outlet_id' => $outlet->id,
            'harga_modal' => 0,
            'status' => true,
        ]);

        $variant = VariantProduct::create([
            'name' => 'Varian Refund Test',
            'harga' => 15000,
            'stok' => 100,
            'product_id' => $product->id,
        ]);

        $item = TransactionItem::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'harga' => 15000,
            'modifier_id' => '[]',
            'discount_id' => '[]',
            'catatan' => 'Normal',
        ]);

        $payload = [
            'transaction_id' => $transaction->id,
            'payment_method' => 'Cash',
            'nominal_refund' => 15000,
            'catatan' => 'Salah pesan item',
            'list_item' => [
                [
                    'variant_id' => $variant->id,
                    'modifier' => '[]',
                    'discount' => '[]',
                    'catatan' => 'Normal',
                    'quantity' => 1,
                    'harga' => 15000,
                ],
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/transactions/refund', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Refund berhasil diproses.',
            ]);

        $this->assertDatabaseHas('refund_transactions', [
            'transaction_id' => $transaction->id,
            'nominal_refund' => 15000,
            'catatan' => 'Salah pesan item',
        ]);

        $this->assertNotNull($item->fresh()->refund_at);
        $this->assertNotNull($item->fresh()->refund_transaction_id);
    }
}

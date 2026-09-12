<?php

namespace Tests\Feature\Api;

use App\Mail\ResendReceiptMail;
use App\Models\Outlets;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ResendReceiptTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_resend_receipt_email()
    {
        Mail::fake();

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

        $transaction = Transaction::create([
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'patty_cash_id' => 1,
            'total' => 50000,
            'nominal_bayar' => 50000,
            'change' => 0,
            'total_pajak' => '[]',
            'diskon_all_item' => '[]',
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/v1/transactions/{$transaction->id}/resend-receipt", [
            'email' => 'customer@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        Mail::assertSent(ResendReceiptMail::class, function ($mail) {
            return $mail->hasTo('customer@example.com');
        });
    }
}

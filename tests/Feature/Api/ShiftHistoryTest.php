<?php

namespace Tests\Feature\Api;

use App\Models\Outlets;
use App\Models\PettyCash;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ShiftHistoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_get_shift_history()
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

        $shift = PettyCash::create([
            'outlet_id' => (string) $outlet->id,
            'amount_awal' => 100000,
            'amount_akhir' => 250000,
            'user_id_started' => $user->id,
            'user_id_ended' => $user->id,
            'open' => now()->subHours(8),
            'close' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/shift/history');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);
    }

    public function test_can_get_shift_history_detail()
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

        $shift = PettyCash::create([
            'outlet_id' => (string) $outlet->id,
            'amount_awal' => 100000,
            'amount_akhir' => 250000,
            'user_id_started' => $user->id,
            'user_id_ended' => $user->id,
            'open' => now()->subHours(8),
            'close' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/v1/shift/history/{$shift->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'shift' => [
                        'id' => $shift->id,
                        'amount_awal' => 100000,
                        'amount_akhir' => 250000,
                    ],
                ],
            ]);
    }
}

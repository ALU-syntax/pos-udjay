<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\OpenBill;
use App\Models\Outlets;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OpenBillTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_store_open_bill_with_json_items()
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

        $level = \App\Models\LevelMembership::first() ?? \App\Models\LevelMembership::create([
            'name' => 'Silver',
            'benchmark' => 0,
            'color' => '#ffffff',
        ]);

        $customer = Customer::create([
            'name' => 'John Doe',
            'telfon' => '081234567890',
            'email' => 'john' . uniqid() . '@example.com',
            'outlet_id' => $outlet->id,
            'level_memberships_id' => $level->id,
        ]);

        $payload = [
            'name' => 'Meja 5',
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_id' => 1,
                    'variant_id' => 1,
                    'nama_product' => 'Kopi Susu',
                    'nama_variant' => 'Regular',
                    'harga' => 15000,
                    'quantity' => 2,
                    'result_total' => 30000,
                    'catatan' => 'Kurang manis',
                    'sales_type' => 'Dine In',
                    'tmp_id' => 'tmp_item_1',
                    'exclude_tax' => false,
                    'diskon' => [],
                    'modifier' => [],
                    'pilihan' => [],
                    'promo' => [],
                ],
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/open-bills', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'name' => 'Meja 5',
                    'total' => 30000,
                ],
            ]);

        $this->assertDatabaseHas('open_bills', [
            'name' => 'Meja 5',
            'outlet_id' => $outlet->id,
            'customer_id' => $customer->id,
        ]);

        $this->assertDatabaseHas('item_open_bills', [
            'nama_product' => 'Kopi Susu',
            'harga' => 15000,
            'quantity' => '2',
            'result_total' => 30000,
        ]);
    }

    public function test_can_store_open_bill_with_legacy_form_data()
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

        $payload = [
            'name' => 'Meja 10',
            'tmpId' => ['tmp_legacy_1'],
            'idProduct' => [1],
            'idVariant' => [1],
            'namaProduct' => ['Teh Tarik'],
            'namaVariant' => ['Large'],
            'harga' => [12000],
            'quantity' => [1],
            'resultTotal' => [12000],
            'catatan' => ['Dingin'],
            'salesType' => ['Take Away'],
            'exclude_tax' => [false],
            'diskon' => [[]],
            'modifier' => [[]],
            'pilihan' => [[]],
            'promo' => [[]],
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/v1/open-bills', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'name' => 'Meja 10',
                    'total' => 12000,
                ],
            ]);

        $this->assertDatabaseHas('open_bills', [
            'name' => 'Meja 10',
            'outlet_id' => $outlet->id,
        ]);

        $this->assertDatabaseHas('item_open_bills', [
            'nama_product' => 'Teh Tarik',
            'harga' => 12000,
        ]);
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\Config;
use App\Models\Outlets;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ConfigApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Config::query()->delete();

        Config::create([
            'name' => 'password_min_length',
            'type' => 'integer',
            'value' => '8',
            'description' => 'Panjang minimal password',
        ]);

        Config::create([
            'name' => 'pin_length',
            'type' => 'integer',
            'value' => '6',
            'description' => 'Panjang PIN kasir',
        ]);

        Config::create([
            'name' => 'feature_multi_outlet',
            'type' => 'boolean',
            'value' => '1',
            'description' => 'Flag fitur multi outlet',
        ]);

        Config::create([
            'name' => 'rule_json',
            'type' => 'json',
            'value' => json_encode(['max' => 5]),
            'description' => 'Contoh config json',
        ]);
    }

    protected function user(): User
    {
        $outlet = Outlets::first() ?? Outlets::create([
            'name' => 'Outlet Test',
            'address' => 'Alamat Test',
            'phone' => '08123456789',
        ]);

        return User::factory()->create([
            'username' => 'configuser_'.uniqid(),
            'status' => '1',
            'role' => 1,
            'outlet_id' => json_encode([$outlet->id]),
        ]);
    }

    public function test_requires_authentication()
    {
        $this->getJson('/api/v1/configs')->assertStatus(401);
    }

    public function test_can_get_all_configs()
    {
        $response = $this->actingAs($this->user(), 'sanctum')->getJson('/api/v1/configs');

        $response->assertStatus(200)
            ->assertJson(['status' => 'success'])
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('missing', []);
    }

    public function test_value_is_casted_according_to_type()
    {
        $response = $this->actingAs($this->user(), 'sanctum')->getJson('/api/v1/configs');

        $data = collect($response->json('data'))->keyBy('name');

        $this->assertSame(8, $data['password_min_length']['value']);
        $this->assertSame('integer', $data['password_min_length']['type']);
        $this->assertTrue($data['feature_multi_outlet']['value']);
        $this->assertSame(['max' => 5], $data['rule_json']['value']);
    }

    public function test_can_filter_by_single_name()
    {
        $response = $this->actingAs($this->user(), 'sanctum')
            ->getJson('/api/v1/configs?name=password_min_length');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'password_min_length')
            ->assertJsonPath('data.0.value', 8);
    }

    public function test_can_filter_by_multiple_names()
    {
        $response = $this->actingAs($this->user(), 'sanctum')
            ->getJson('/api/v1/configs?name=password_min_length,pin_length');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('missing', []);
    }

    public function test_reports_missing_names()
    {
        $response = $this->actingAs($this->user(), 'sanctum')
            ->getJson('/api/v1/configs?name=password_min_length,tidak_ada');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('missing', ['tidak_ada']);
    }

    public function test_can_show_config_by_name()
    {
        $response = $this->actingAs($this->user(), 'sanctum')
            ->getJson('/api/v1/configs/pin_length');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'pin_length')
            ->assertJsonPath('data.value', 6)
            ->assertJsonPath('data.type', 'integer');
    }

    public function test_show_returns_404_when_not_found()
    {
        $response = $this->actingAs($this->user(), 'sanctum')
            ->getJson('/api/v1/configs/tidak_ada_config');

        $response->assertStatus(404)
            ->assertJsonPath('status', 'error');
    }
}

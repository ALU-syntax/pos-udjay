<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\LevelMembership;
use App\Models\Outlets;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerSyncTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Data tes diberi updated_at jauh di masa depan agar terisolasi
     * dari data customer nyata yang sudah ada di database.
     */
    private const FUTURE_UPDATED_AT = '2030-01-01 00:00:00';
    private const SINCE_BOUNDARY    = '2029-12-31T00:00:00Z';

    private LevelMembership $level;

    protected function setUp(): void
    {
        parent::setUp();

        $this->level = LevelMembership::first() ?? LevelMembership::create([
            'name' => 'Silver',
            'benchmark' => 0,
            'color' => '#ffffff',
        ]);
    }

    private function actingUser()
    {
        $outlet = Outlets::first() ?? Outlets::create([
            'name' => 'Outlet Test',
            'address' => 'Alamat Test',
            'phone' => '08123456789',
        ]);

        return User::factory()->create([
            'username' => 'testuser_' . uniqid(),
            'status' => '1',
            'role' => 1,
            'outlet_id' => json_encode([$outlet->id]),
        ]);
    }

    private function makeCustomer(array $attrs = []): Customer
    {
        $customer = Customer::create(array_merge([
            'name' => 'Sync Test ' . Str::random(6),
            'telfon' => '0812' . random_int(1000000, 9999999),
            'gender' => 'laki-laki',
            'level_memberships_id' => $this->level->id,
            'level_batch' => 1,
            'point' => 10,
            'exp' => 5,
        ], $attrs));

        // Bypass Eloquent agar updated_at tidak ditimpa menjadi waktu sekarang
        DB::table('customers')
            ->where('id', $customer->id)
            ->update(['updated_at' => self::FUTURE_UPDATED_AT]);

        return $customer->fresh();
    }

    public function test_sync_requires_authentication()
    {
        $this->getJson('/api/v1/customers/sync')->assertStatus(401);
    }

    public function test_sync_returns_data_and_metadata()
    {
        $user = $this->actingUser();
        $customer = $this->makeCustomer();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customers/sync?updated_since=' . urlencode(self::SINCE_BOUNDARY) . '&limit=5');

        $response->assertStatus(200)
            ->assertJson(['status' => 'success'])
            ->assertJsonStructure([
                'status',
                'data',
                'has_more',
                'next_cursor',
                'server_time',
            ]);

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($customer->id));
    }

    public function test_sync_paginates_with_opaque_cursor_without_duplicates()
    {
        $user = $this->actingUser();

        $created = collect();
        for ($i = 0; $i < 7; $i++) {
            $created->push($this->makeCustomer(['name' => 'Page Test ' . $i . ' ' . Str::random(4)]));
        }

        $seen = [];
        $cursor = null;
        $pages = 0;

        do {
            $query = '/api/v1/customers/sync?updated_since=' . urlencode(self::SINCE_BOUNDARY) . '&limit=3';
            if ($cursor) {
                $query = '/api/v1/customers/sync?limit=3&cursor=' . urlencode($cursor);
            }

            $response = $this->actingAs($user, 'sanctum')->getJson($query);
            $response->assertStatus(200);

            foreach ($response->json('data') as $row) {
                $seen[] = $row['id'];
            }

            $cursor = $response->json('next_cursor');
            $pages++;

            $this->assertLessThan(20, $pages, 'Pagination tidak berhenti (kemungkinan infinite loop).');
        } while (!empty($cursor));

        // Tidak ada duplikat antar halaman
        $this->assertSame(count($seen), count(array_unique($seen)), 'Terdapat duplikat data antar halaman.');

        // Semua customer tes terambil
        foreach ($created as $c) {
            $this->assertContains($c->id, $seen, "Customer #{$c->id} tidak terambil saat pagination.");
        }
    }

    public function test_sync_includes_soft_deleted_with_flag()
    {
        $user = $this->actingUser();
        $customer = $this->makeCustomer();

        $customer->delete();

        // delete() menimpa updated_at, set ulang agar tetap dalam rentang tes
        DB::table('customers')
            ->where('id', $customer->id)
            ->update(['updated_at' => self::FUTURE_UPDATED_AT]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customers/sync?updated_since=' . urlencode(self::SINCE_BOUNDARY) . '&limit=1000');

        $response->assertStatus(200);

        $row = collect($response->json('data'))->firstWhere('id', $customer->id);

        $this->assertNotNull($row, 'Customer soft-deleted tidak ikut terkirim.');
        $this->assertTrue($row['is_deleted']);
        $this->assertNotNull($row['deleted_at']);
    }

    public function test_sync_delta_returns_only_recently_updated()
    {
        $user = $this->actingUser();

        $old = $this->makeCustomer(['name' => 'Old Customer ' . Str::random(4)]);
        DB::table('customers')
            ->where('id', $old->id)
            ->update(['updated_at' => now()->subDays(10)]);

        $fresh = $this->makeCustomer(['name' => 'Fresh Customer ' . Str::random(4)]);

        $since = now()->subDay()->utc()->toIso8601String();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customers/sync?updated_since=' . urlencode($since) . '&limit=1000');

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($fresh->id), 'Customer baru seharusnya ikut terkirim.');
        $this->assertFalse($ids->contains($old->id), 'Customer lama tidak boleh ikut terkirim.');
    }

    public function test_sync_rejects_invalid_cursor()
    {
        $user = $this->actingUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customers/sync?cursor=not-a-valid-cursor')
            ->assertStatus(422)
            ->assertJson(['status' => 'error']);
    }

    public function test_sync_rejects_invalid_updated_since()
    {
        $user = $this->actingUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customers/sync?updated_since=kemarin')
            ->assertStatus(422)
            ->assertJson(['status' => 'error']);
    }
}

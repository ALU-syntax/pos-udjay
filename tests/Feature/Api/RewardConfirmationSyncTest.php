<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\LevelMembership;
use App\Models\Outlets;
use App\Models\RewardConfirmation;
use App\Models\RewardMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class RewardConfirmationSyncTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Data tes diberi updated_at jauh di masa depan agar terisolasi
     * dari data reward confirmation nyata yang sudah ada di database.
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

    private function makeOutlet(): Outlets
    {
        return Outlets::create([
            'name' => 'Outlet ' . Str::random(6),
            'address' => 'Alamat Test',
            'phone' => '0812' . random_int(1000000, 9999999),
        ]);
    }

    private function makeCustomer(array $attrs = []): Customer
    {
        return Customer::create(array_merge([
            'name' => 'Reward Test ' . Str::random(6),
            'telfon' => '0812' . random_int(1000000, 9999999),
            'gender' => 'laki-laki',
            'level_memberships_id' => $this->level->id,
            'level_batch' => 1,
            'point' => 10,
            'exp' => 5,
        ], $attrs));
    }

    private function makeRewardMembership(array $attrs = []): RewardMembership
    {
        return RewardMembership::create(array_merge([
            'name' => 'Free Keychain ' . Str::random(6),
            'level_membership_id' => $this->level->id,
            'description' => 'Reward level test',
            'icon' => 'fa-solid fa-gift',
        ], $attrs));
    }

    private function makeConfirmation(array $attrs = []): RewardConfirmation
    {
        $confirmation = RewardConfirmation::create($attrs);

        // Bypass Eloquent agar updated_at tidak ditimpa menjadi waktu sekarang
        DB::table('reward_confirmations')
            ->where('id', $confirmation->id)
            ->update(['updated_at' => self::FUTURE_UPDATED_AT]);

        return $confirmation->fresh();
    }

    public function test_sync_requires_authentication()
    {
        $this->getJson('/api/v1/customers/reward-confirmations/sync')->assertStatus(401);
    }

    public function test_sync_returns_data_with_names_and_metadata()
    {
        $user     = $this->actingUser();
        $outlet   = $this->makeOutlet();
        $customer = $this->makeCustomer();
        $reward   = $this->makeRewardMembership();

        $confirmation = $this->makeConfirmation([
            'level_membership_id'   => $this->level->id,
            'reward_memberships_id' => $reward->id,
            'customer_id'           => $customer->id,
            'user_id'               => $user->id,
            'outlet_id'             => $outlet->id,
            'snapshot'              => json_encode([
                'product_id'            => 727,
                'product_name'          => 'Free Keychain',
                'level_membership_id'   => $this->level->id,
                'level_membership_name' => $this->level->name,
            ]),
            'level_batch'           => 1,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customers/reward-confirmations/sync?updated_since=' . self::SINCE_BOUNDARY . '&limit=1000');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
                'has_more',
                'next_cursor',
                'server_time',
            ])
            ->assertJson(['status' => 'success']);

        $row = collect($response->json('data'))->firstWhere('id', $confirmation->id);

        $this->assertNotNull($row, 'Reward confirmation baru seharusnya ikut terkirim.');
        $this->assertSame($customer->id, $row['customer_id']);
        $this->assertSame($customer->name, $row['customer_name']);
        $this->assertSame($this->level->id, $row['level_membership_id']);
        $this->assertSame($this->level->name, $row['level_membership_name']);
        $this->assertSame($reward->id, $row['reward_memberships_id']);
        $this->assertSame($reward->name, $row['reward_name']);
        $this->assertSame(1, $row['level_batch']);
        $this->assertSame($outlet->id, $row['outlet_id']);
        $this->assertSame($outlet->name, $row['outlet_name']);
        $this->assertSame($user->id, $row['user_id']);
        $this->assertSame($user->name, $row['user_name']);
        $this->assertSame(727, $row['snapshot']['product_id']);
        $this->assertFalse($row['is_deleted']);
        $this->assertNull($row['deleted_at']);
    }

    public function test_sync_is_global_across_outlets()
    {
        $user        = $this->actingUser();
        $otherOutlet = $this->makeOutlet();
        $customer    = $this->makeCustomer();
        $reward      = $this->makeRewardMembership();

        // Confirmation dari outlet yang BUKAN outlet user
        $confirmation = $this->makeConfirmation([
            'level_membership_id'   => $this->level->id,
            'reward_memberships_id' => $reward->id,
            'customer_id'           => $customer->id,
            'user_id'               => $user->id,
            'outlet_id'             => $otherOutlet->id,
            'level_batch'           => 1,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customers/reward-confirmations/sync?updated_since=' . self::SINCE_BOUNDARY . '&limit=1000');

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue(
            $ids->contains($confirmation->id),
            'Reward confirmation dari outlet lain harus ikut terkirim agar customer tidak bisa claim dua kali.'
        );
    }

    public function test_sync_sends_soft_deleted_row_with_is_deleted_flag()
    {
        $user     = $this->actingUser();
        $outlet   = $this->makeOutlet();
        $customer = $this->makeCustomer();
        $reward   = $this->makeRewardMembership();

        $confirmation = $this->makeConfirmation([
            'level_membership_id'   => $this->level->id,
            'reward_memberships_id' => $reward->id,
            'customer_id'           => $customer->id,
            'user_id'               => $user->id,
            'outlet_id'             => $outlet->id,
            'level_batch'           => 2,
        ]);

        $confirmation->delete();

        DB::table('reward_confirmations')
            ->where('id', $confirmation->id)
            ->update([
                'deleted_at' => self::FUTURE_UPDATED_AT,
                'updated_at' => self::FUTURE_UPDATED_AT,
            ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customers/reward-confirmations/sync?updated_since=' . self::SINCE_BOUNDARY . '&limit=1000');

        $response->assertStatus(200);

        $row = collect($response->json('data'))->firstWhere('id', $confirmation->id);

        $this->assertNotNull($row, 'Baris soft-deleted harus tetap terkirim.');
        $this->assertTrue($row['is_deleted']);
        $this->assertNotNull($row['deleted_at']);
    }

    public function test_sync_only_sends_rows_updated_after_since()
    {
        $user     = $this->actingUser();
        $outlet   = $this->makeOutlet();
        $customer = $this->makeCustomer();
        $reward   = $this->makeRewardMembership();

        $oldConfirmation = RewardConfirmation::create([
            'level_membership_id'   => $this->level->id,
            'reward_memberships_id' => $reward->id,
            'customer_id'           => $customer->id,
            'user_id'               => $user->id,
            'outlet_id'             => $outlet->id,
            'level_batch'           => 1,
        ]);
        DB::table('reward_confirmations')
            ->where('id', $oldConfirmation->id)
            ->update(['updated_at' => '2022-01-01 00:00:00']);

        $newConfirmation = $this->makeConfirmation([
            'level_membership_id'   => $this->level->id,
            'reward_memberships_id' => $reward->id,
            'customer_id'           => $customer->id,
            'user_id'               => $user->id,
            'outlet_id'             => $outlet->id,
            'level_batch'           => 2,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customers/reward-confirmations/sync?updated_since=' . self::SINCE_BOUNDARY . '&limit=1000');

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($newConfirmation->id), 'Confirmation baru seharusnya ikut terkirim.');
        $this->assertFalse($ids->contains($oldConfirmation->id), 'Confirmation lama tidak boleh ikut terkirim.');
    }

    public function test_sync_paginates_with_cursor()
    {
        $user     = $this->actingUser();
        $outlet   = $this->makeOutlet();
        $customer = $this->makeCustomer();
        $reward   = $this->makeRewardMembership();

        // updated_at sama semua agar keyset pagination (id > lastId) teruji
        for ($i = 0; $i < 3; $i++) {
            $this->makeConfirmation([
                'level_membership_id'   => $this->level->id,
                'reward_memberships_id' => $reward->id,
                'customer_id'           => $customer->id,
                'user_id'               => $user->id,
                'outlet_id'             => $outlet->id,
                'level_batch'           => $i + 1,
            ]);
        }

        $first = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customers/reward-confirmations/sync?updated_since=' . self::SINCE_BOUNDARY . '&limit=1');

        $first->assertStatus(200)
            ->assertJson(['has_more' => true]);

        $firstData = collect($first->json('data'));
        $this->assertCount(1, $firstData);

        $cursor = $first->json('next_cursor');
        $this->assertNotNull($cursor, 'next_cursor harus ada saat has_more = true.');

        $second = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customers/reward-confirmations/sync?cursor=' . urlencode($cursor) . '&limit=1000');

        $second->assertStatus(200);

        $secondIds = collect($second->json('data'))->pluck('id');
        $this->assertFalse(
            $secondIds->contains($firstData->first()['id']),
            'Halaman kedua tidak boleh mengulang baris halaman pertama.'
        );
        $this->assertGreaterThan(
            $firstData->first()['id'],
            $secondIds->max(),
            'Halaman kedua harus berisi id yang lebih besar (keyset).'
        );
    }

    public function test_sync_rejects_invalid_cursor()
    {
        $user = $this->actingUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customers/reward-confirmations/sync?cursor=not-a-valid-cursor')
            ->assertStatus(422)
            ->assertJson(['status' => 'error']);
    }

    public function test_sync_rejects_invalid_updated_since()
    {
        $user = $this->actingUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customers/reward-confirmations/sync?updated_since=kemarin')
            ->assertStatus(422)
            ->assertJson(['status' => 'error']);
    }
}

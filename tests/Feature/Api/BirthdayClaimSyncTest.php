<?php

namespace Tests\Feature\Api;

use App\Models\BirthdayRewardClaims;
use App\Models\Customer;
use App\Models\LevelMembership;
use App\Models\Outlets;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class BirthdayClaimSyncTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Data tes diberi updated_at jauh di masa depan agar terisolasi
     * dari data claim nyata yang sudah ada di database.
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
            'name' => 'Claim Test ' . Str::random(6),
            'telfon' => '0812' . random_int(1000000, 9999999),
            'gender' => 'laki-laki',
            'level_memberships_id' => $this->level->id,
            'level_batch' => 1,
            'point' => 10,
            'exp' => 5,
        ], $attrs));
    }

    private function makeProduct(Outlets $outlet): Product
    {
        return Product::create([
            'name' => 'Free Cake ' . Str::random(6),
            'outlet_id' => $outlet->id,
            'harga_modal' => 0,
            'status' => true,
        ]);
    }

    private function makeClaim(array $attrs = []): BirthdayRewardClaims
    {
        $claim = BirthdayRewardClaims::create($attrs);

        // Bypass Eloquent agar updated_at tidak ditimpa menjadi waktu sekarang
        DB::table('birthday_reward_claims')
            ->where('id', $claim->id)
            ->update(['updated_at' => self::FUTURE_UPDATED_AT]);

        return $claim->fresh();
    }

    public function test_sync_requires_authentication()
    {
        $this->getJson('/api/v1/customers/birthday-claims/sync')->assertStatus(401);
    }

    public function test_sync_returns_data_with_names_and_metadata()
    {
        $user     = $this->actingUser();
        $outlet   = $this->makeOutlet();
        $customer = $this->makeCustomer();
        $product  = $this->makeProduct($outlet);

        $claim = $this->makeClaim([
            'customer_id' => $customer->id,
            'outlet_id'   => $outlet->id,
            'product_id'  => $product->id,
            'age'         => 27,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customers/birthday-claims/sync?updated_since=' . self::SINCE_BOUNDARY . '&limit=1000');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
                'has_more',
                'next_cursor',
                'server_time',
            ])
            ->assertJson(['status' => 'success']);

        $row = collect($response->json('data'))->firstWhere('id', $claim->id);

        $this->assertNotNull($row, 'Claim baru seharusnya ikut terkirim.');
        $this->assertSame($customer->id, $row['customer_id']);
        $this->assertSame($customer->name, $row['customer_name']);
        $this->assertSame($outlet->id, $row['outlet_id']);
        $this->assertSame($outlet->name, $row['outlet_name']);
        $this->assertSame($product->id, $row['product_id']);
        $this->assertSame($product->name, $row['product_name']);
        $this->assertSame(27, $row['age']);
        $this->assertFalse($row['is_deleted']);
        $this->assertNull($row['deleted_at']);
    }

    public function test_sync_is_global_across_outlets()
    {
        $user        = $this->actingUser();
        $otherOutlet = $this->makeOutlet();
        $customer    = $this->makeCustomer();
        $product     = $this->makeProduct($otherOutlet);

        // Claim dari outlet yang BUKAN outlet user
        $claim = $this->makeClaim([
            'customer_id' => $customer->id,
            'outlet_id'   => $otherOutlet->id,
            'product_id'  => $product->id,
            'age'         => 30,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customers/birthday-claims/sync?updated_since=' . self::SINCE_BOUNDARY . '&limit=1000');

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue(
            $ids->contains($claim->id),
            'Claim dari outlet lain harus ikut terkirim agar customer tidak bisa claim dua kali.'
        );
    }

    public function test_sync_sends_soft_deleted_row_with_is_deleted_flag()
    {
        $user     = $this->actingUser();
        $outlet   = $this->makeOutlet();
        $customer = $this->makeCustomer();
        $product  = $this->makeProduct($outlet);

        $claim = $this->makeClaim([
            'customer_id' => $customer->id,
            'outlet_id'   => $outlet->id,
            'product_id'  => $product->id,
            'age'         => 31,
        ]);

        $claim->delete();

        // Pastikan deleted_at terlihat oleh delta sync (updated_at tetap di masa depan)
        DB::table('birthday_reward_claims')
            ->where('id', $claim->id)
            ->update([
                'deleted_at' => self::FUTURE_UPDATED_AT,
                'updated_at' => self::FUTURE_UPDATED_AT,
            ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customers/birthday-claims/sync?updated_since=' . self::SINCE_BOUNDARY . '&limit=1000');

        $response->assertStatus(200);

        $row = collect($response->json('data'))->firstWhere('id', $claim->id);

        $this->assertNotNull($row, 'Baris soft-deleted harus tetap terkirim.');
        $this->assertTrue($row['is_deleted']);
        $this->assertNotNull($row['deleted_at']);
    }

    public function test_sync_only_sends_rows_updated_after_since()
    {
        $user     = $this->actingUser();
        $outlet   = $this->makeOutlet();
        $customer = $this->makeCustomer();
        $product  = $this->makeProduct($outlet);

        // Claim lama: updated_at di masa lalu
        $oldClaim = BirthdayRewardClaims::create([
            'customer_id' => $customer->id,
            'outlet_id'   => $outlet->id,
            'product_id'  => $product->id,
            'age'         => 20,
        ]);
        DB::table('birthday_reward_claims')
            ->where('id', $oldClaim->id)
            ->update(['updated_at' => '2022-01-01 00:00:00']);

        // Claim baru: updated_at di masa depan
        $newClaim = $this->makeClaim([
            'customer_id' => $customer->id,
            'outlet_id'   => $outlet->id,
            'product_id'  => $product->id,
            'age'         => 21,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customers/birthday-claims/sync?updated_since=' . self::SINCE_BOUNDARY . '&limit=1000');

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($newClaim->id), 'Claim baru seharusnya ikut terkirim.');
        $this->assertFalse($ids->contains($oldClaim->id), 'Claim lama tidak boleh ikut terkirim.');
    }

    public function test_sync_paginates_with_cursor()
    {
        $user     = $this->actingUser();
        $outlet   = $this->makeOutlet();
        $customer = $this->makeCustomer();
        $product  = $this->makeProduct($outlet);

        // updated_at sama semua agar keyset pagination (id > lastId) teruji
        for ($i = 0; $i < 3; $i++) {
            $this->makeClaim([
                'customer_id' => $customer->id,
                'outlet_id'   => $outlet->id,
                'product_id'  => $product->id,
                'age'         => 40 + $i,
            ]);
        }

        $first = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customers/birthday-claims/sync?updated_since=' . self::SINCE_BOUNDARY . '&limit=1');

        $first->assertStatus(200)
            ->assertJson(['has_more' => true]);

        $firstData = collect($first->json('data'));
        $this->assertCount(1, $firstData);

        $cursor = $first->json('next_cursor');
        $this->assertNotNull($cursor, 'next_cursor harus ada saat has_more = true.');

        $second = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customers/birthday-claims/sync?cursor=' . urlencode($cursor) . '&limit=1000');

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
            ->getJson('/api/v1/customers/birthday-claims/sync?cursor=not-a-valid-cursor')
            ->assertStatus(422)
            ->assertJson(['status' => 'error']);
    }

    public function test_sync_rejects_invalid_updated_since()
    {
        $user = $this->actingUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/customers/birthday-claims/sync?updated_since=kemarin')
            ->assertStatus(422)
            ->assertJson(['status' => 'error']);
    }
}

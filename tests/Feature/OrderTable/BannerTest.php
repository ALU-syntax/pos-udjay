<?php

namespace Tests\Feature\OrderTable;

use App\Models\Category;
use App\Models\OrderTable\Banner;
use App\Models\OrderTable\Voucher;
use App\Models\Product;
use App\Models\Promo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class BannerTest extends OrderTableTestCase
{
    public function test_remote_image_accepts_https_and_rejects_javascript_and_data_urls(): void
    {
        $outlet = $this->outlet('Banner Remote');
        $user = $this->userFor($outlet, ['create order-table/banners']);

        $this->actingAs($user)->postJson(route('order-table/banners/store'), $this->payload($outlet->id, [
            'title' => 'HTTPS image', 'image_url' => 'https://cdn.example.test/banner.webp',
        ]))->assertOk();

        foreach (['javascript:alert(1)', 'data:image/png;base64,AAAA'] as $url) {
            $this->actingAs($user)->postJson(route('order-table/banners/store'), $this->payload($outlet->id, [
                'title' => 'Unsafe image', 'image_url' => $url,
            ]))->assertUnprocessable()->assertJsonValidationErrors('image_url');
        }
    }

    public function test_url_action_requires_http_or_https(): void
    {
        $outlet = $this->outlet('Banner Action URL');
        $user = $this->userFor($outlet, ['create order-table/banners']);

        $this->actingAs($user)->postJson(route('order-table/banners/store'), $this->payload($outlet->id, [
            'action_value' => 'javascript:alert(1)',
        ]))->assertUnprocessable()->assertJsonValidationErrors('action_value');
        $this->actingAs($user)->postJson(route('order-table/banners/store'), $this->payload($outlet->id, [
            'title' => 'Valid URL', 'action_value' => 'http://example.test/menu',
        ]))->assertOk();
    }

    public function test_catalog_targets_must_be_active_and_available_to_selected_outlet(): void
    {
        $own = $this->outlet('Banner Target Own');
        $other = $this->outlet('Banner Target Other');
        $user = $this->userFor($own, ['create order-table/banners']);
        $category = Category::create(['name' => 'Banner Category '.uniqid(), 'status' => true]);
        $ownProduct = Product::create(['name' => 'Banner Own Product', 'category_id' => $category->id, 'outlet_id' => $own->id, 'status' => true]);
        $foreignProduct = Product::create(['name' => 'Banner Foreign Product', 'category_id' => $category->id, 'outlet_id' => $other->id, 'status' => true]);
        $voucher = Voucher::create(['outlet_id' => $own->id, 'code' => 'BANNER-VOUCHER', 'type' => 'fixed', 'value' => 1000, 'min_spend' => 0, 'scope' => 'all', 'status' => true]);
        $promo = Promo::create(['name' => 'Banner Promo', 'outlet_id' => $own->id, 'type' => 'discount', 'purchase_requirement' => 'any_item', 'reward' => '[]', 'status' => true]);

        foreach ([['product', $ownProduct->id], ['category', $category->id], ['voucher', $voucher->id], ['promo', $promo->id]] as [$type, $id]) {
            $this->actingAs($user)->postJson(route('order-table/banners/store'), $this->payload($own->id, [
                'title' => 'Target '.$type, 'action_type' => $type, 'action_value' => $id,
            ]))->assertOk();
        }

        $this->actingAs($user)->postJson(route('order-table/banners/store'), $this->payload($own->id, [
            'title' => 'Foreign target', 'action_type' => 'product', 'action_value' => $foreignProduct->id,
        ]))->assertUnprocessable()->assertJsonValidationErrors('action_value');
        $this->actingAs($user)->postJson(route('order-table/banners/store'), $this->payload($other->id, [
            'title' => 'Foreign outlet', 'action_type' => 'product', 'action_value' => $foreignProduct->id,
        ]))->assertForbidden();
    }

    public function test_internal_action_uses_an_allowlist(): void
    {
        $outlet = $this->outlet('Banner Internal');
        $user = $this->userFor($outlet, ['create order-table/banners']);

        foreach (['menu', 'cart', 'orders', 'vouchers'] as $target) {
            $this->actingAs($user)->postJson(route('order-table/banners/store'), $this->payload($outlet->id, [
                'title' => 'Internal '.$target, 'action_type' => 'internal', 'action_value' => $target,
            ]))->assertOk();
        }
        $this->actingAs($user)->postJson(route('order-table/banners/store'), $this->payload($outlet->id, [
            'title' => 'Unsafe internal', 'action_type' => 'internal', 'action_value' => 'admin',
        ]))->assertUnprocessable()->assertJsonValidationErrors('action_value');
    }

    public function test_upload_stores_managed_image_url_and_writes_audit(): void
    {
        Storage::fake('public');
        $outlet = $this->outlet('Banner Upload');
        $user = $this->userFor($outlet, ['create order-table/banners']);

        $response = $this->actingAs($user)->post(route('order-table/banners/store'), $this->payload($outlet->id, [
            'title' => 'Uploaded image', 'image_url' => null,
            'image_upload' => UploadedFile::fake()->image('banner.jpg', 600, 300),
        ]), ['Accept' => 'application/json'])->assertOk();

        $banner = Banner::findOrFail($response->json('data.id'));
        $path = ltrim(substr(parse_url($banner->image_url, PHP_URL_PATH), strlen('/storage/')), '/');
        Storage::disk('public')->assertExists($path);
        $this->assertStringStartsWith('order-table/banners/', $path);
        $this->assertDatabaseHas('ot_audit_logs', ['action' => 'banner.created', 'subject_id' => $banner->id]);
    }

    public function test_update_retains_existing_image_when_url_is_empty_and_no_file_is_uploaded(): void
    {
        $outlet = $this->outlet('Banner Retain');
        $user = $this->userFor($outlet, ['update order-table/banners']);
        $banner = $this->banner($outlet->id, ['image_url' => 'https://cdn.example.test/original.jpg']);

        $this->actingAs($user)->putJson(route('order-table/banners/update', $banner->id), $this->payload($outlet->id, [
            'title' => 'Updated title', 'image_url' => '',
        ], false))->assertOk();

        $this->assertSame('https://cdn.example.test/original.jpg', $banner->fresh()->image_url);
    }

    public function test_replacing_managed_local_image_removes_the_old_file(): void
    {
        Storage::fake('public');
        $outlet = $this->outlet('Banner Replace');
        $user = $this->userFor($outlet, ['update order-table/banners']);
        $oldPath = 'order-table/banners/old.jpg';
        Storage::disk('public')->put($oldPath, 'old image');
        $banner = $this->banner($outlet->id, ['image_url' => Storage::disk('public')->url($oldPath)]);

        $this->actingAs($user)->post(route('order-table/banners/update', $banner->id), array_merge(
            $this->payload($outlet->id, ['title' => 'Replacement', 'image_url' => null], false),
            ['_method' => 'PUT', 'image_upload' => UploadedFile::fake()->image('replacement.png', 600, 300)],
        ), ['Accept' => 'application/json'])->assertOk();

        Storage::disk('public')->assertMissing($oldPath);
        $newPath = ltrim(substr(parse_url($banner->fresh()->image_url, PHP_URL_PATH), strlen('/storage/')), '/');
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_update_cannot_change_status_and_toggle_writes_semantic_audit(): void
    {
        $outlet = $this->outlet('Banner Toggle');
        $user = $this->userFor($outlet, ['update order-table/banners']);
        $banner = $this->banner($outlet->id);

        $this->actingAs($user)->putJson(route('order-table/banners/update', $banner->id), $this->payload($outlet->id, [
            'status' => false,
        ]))->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertTrue($banner->fresh()->status);

        $this->actingAs($user)->postJson(route('order-table/banners/toggle', $banner->id))->assertOk();
        $this->assertFalse($banner->fresh()->status);
        $this->assertDatabaseHas('ot_audit_logs', [
            'user_id' => $user->id, 'action' => 'banner.disabled', 'subject_id' => $banner->id,
        ]);
    }

    public function test_banners_have_no_delete_route(): void
    {
        $this->assertFalse(Route::has('order-table/banners/destroy'));
    }

    public function test_global_banner_rejects_outlet_specific_targets(): void
    {
        $outlet = $this->outlet('Global Banner');
        $user = $this->userFor($outlet, ['create order-table/banners']);
        $user->update(['outlet_id' => \App\Models\Outlets::query()->pluck('id')->all()]);

        $this->actingAs($user)->postJson(route('order-table/banners/store'), $this->payload($outlet->id, [
            'outlet_id' => null,
            'action_type' => 'product',
            'action_value' => 1,
        ]))->assertUnprocessable()->assertJsonValidationErrors('action_type');
    }

    public function test_external_lookalike_storage_url_never_deletes_local_file(): void
    {
        Storage::fake('public');
        $outlet = $this->outlet('External Image');
        $user = $this->userFor($outlet, ['update order-table/banners']);
        $protectedPath = 'order-table/banners/protected.jpg';
        Storage::disk('public')->put($protectedPath, 'protected');
        $banner = $this->banner($outlet->id, [
            'image_url' => 'https://attacker.example/storage/'.$protectedPath,
        ]);

        $this->actingAs($user)->post(route('order-table/banners/update', $banner->id), array_merge(
            $this->payload($outlet->id, ['image_url' => null], false),
            ['_method' => 'PUT', 'image_upload' => UploadedFile::fake()->image('new.png', 600, 300)],
        ), ['Accept' => 'application/json'])->assertOk();

        Storage::disk('public')->assertExists($protectedPath);
    }

    private function payload(int $outletId, array $overrides = [], bool $includeStatus = true): array
    {
        $payload = array_merge([
            'outlet_id' => $outletId,
            'title' => 'Banner '.uniqid(),
            'image_url' => 'https://cdn.example.test/banner.jpg',
            'action_type' => 'url',
            'action_value' => 'https://example.test/menu',
            'position' => 'home_top',
            'start_at' => null,
            'end_at' => null,
            'status' => true,
            'sort_order' => 0,
        ], $overrides);

        if (! $includeStatus) {
            unset($payload['status']);
        }

        return $payload;
    }

    private function banner(int $outletId, array $overrides = []): Banner
    {
        return Banner::create(array_merge($this->payload($outletId), $overrides));
    }
}

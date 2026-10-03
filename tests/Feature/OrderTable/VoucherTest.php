<?php

namespace Tests\Feature\OrderTable;

use App\Models\Category;
use App\Models\OrderTable\Voucher;
use App\Models\Outlets;
use App\Models\Product;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class VoucherTest extends OrderTableTestCase
{
    public function test_authorized_outlet_can_create_voucher_and_code_is_trimmed_and_uppercased(): void
    {
        $outlet = $this->outlet('Voucher Create');
        $user = $this->userFor($outlet, ['create order-table/vouchers']);

        $response = $this->actingAs($user)->postJson(
            route('order-table/vouchers/store'),
            $this->payload($outlet->id, ['code' => '  save-10  ']),
        )->assertOk();

        $voucher = Voucher::findOrFail($response->json('data.id'));
        $this->assertSame('SAVE-10', $voucher->code);
        $this->assertSame($outlet->id, $voucher->outlet_id);
        $this->assertDatabaseHas('ot_audit_logs', ['action' => 'voucher.created', 'subject_id' => $voucher->id]);
    }

    public function test_percent_and_fixed_values_are_validated(): void
    {
        $outlet = $this->outlet('Voucher Values');
        $user = $this->userFor($outlet, ['create order-table/vouchers']);

        $this->actingAs($user)->postJson(route('order-table/vouchers/store'), $this->payload($outlet->id, [
            'code' => 'PERCENT-INVALID',
            'type' => 'percent',
            'value' => 101,
        ]))->assertUnprocessable()->assertJsonValidationErrors('value');

        $this->actingAs($user)->postJson(route('order-table/vouchers/store'), $this->payload($outlet->id, [
            'code' => 'FIXED-INVALID',
            'type' => 'fixed',
            'value' => 1000,
            'max_discount' => 500,
        ]))->assertUnprocessable()->assertJsonValidationErrors('max_discount');

        $this->actingAs($user)->postJson(route('order-table/vouchers/store'), $this->payload($outlet->id, [
            'code' => 'FIXED-VALID',
            'type' => 'fixed',
            'value' => 1000,
            'max_discount' => null,
        ]))->assertOk();
    }

    public function test_code_is_globally_unique_and_server_managed_quota_is_prohibited(): void
    {
        $firstOutlet = $this->outlet('Voucher Unique A');
        $secondOutlet = $this->outlet('Voucher Unique B');
        Voucher::create($this->modelAttributes($firstOutlet->id, ['code' => 'GLOBAL-CODE']));
        $user = $this->userFor($secondOutlet, ['create order-table/vouchers']);

        $this->actingAs($user)->postJson(route('order-table/vouchers/store'), $this->payload($secondOutlet->id, [
            'code' => ' global-code ',
        ]))->assertUnprocessable()->assertJsonValidationErrors('code');

        $this->actingAs($user)->postJson(route('order-table/vouchers/store'), $this->payload($secondOutlet->id, [
            'code' => 'QUOTA-USED',
            'quota_used' => 1,
        ]))->assertUnprocessable()->assertJsonValidationErrors('quota_used');
    }

    public function test_per_device_and_session_quotas_are_limited_to_one(): void
    {
        $outlet = $this->outlet('Voucher Quotas');
        $user = $this->userFor($outlet, ['create order-table/vouchers']);

        foreach (['quota_per_device', 'quota_per_session'] as $field) {
            $this->actingAs($user)->postJson(route('order-table/vouchers/store'), $this->payload($outlet->id, [
                'code' => strtoupper($field),
                $field => 2,
            ]))->assertUnprocessable()->assertJsonValidationErrors($field);
        }
    }

    public function test_all_scope_normalizes_scope_ids_to_null(): void
    {
        $outlet = $this->outlet('Voucher All Scope');
        $user = $this->userFor($outlet, ['create order-table/vouchers']);

        $response = $this->actingAs($user)->postJson(route('order-table/vouchers/store'), $this->payload($outlet->id, [
            'code' => 'ALL-SCOPE',
            'scope_ids' => [999],
        ]))->assertOk();

        $this->assertNull(Voucher::findOrFail($response->json('data.id'))->scope_ids);
    }

    public function test_product_and_category_scopes_only_accept_active_targets_from_selected_outlet(): void
    {
        $own = $this->outlet('Voucher Scope Own');
        $other = $this->outlet('Voucher Scope Other');
        $user = $this->userFor($own, ['create order-table/vouchers']);
        $activeCategory = Category::create(['name' => 'Voucher Active Category '.uniqid(), 'status' => true]);
        $inactiveCategory = Category::create(['name' => 'Voucher Inactive Category '.uniqid(), 'status' => false]);
        $activeProduct = Product::create(['name' => 'Voucher Active Product', 'category_id' => $activeCategory->id, 'outlet_id' => $own->id, 'status' => true]);
        $inactiveProduct = Product::create(['name' => 'Voucher Inactive Product', 'category_id' => $activeCategory->id, 'outlet_id' => $own->id, 'status' => false]);
        $foreignProduct = Product::create(['name' => 'Voucher Foreign Product', 'category_id' => $inactiveCategory->id, 'outlet_id' => $other->id, 'status' => true]);

        $this->actingAs($user)->postJson(route('order-table/vouchers/store'), $this->payload($own->id, [
            'code' => 'PRODUCT-ACTIVE', 'scope' => 'product', 'scope_ids' => [$activeProduct->id],
        ]))->assertOk();
        $this->actingAs($user)->postJson(route('order-table/vouchers/store'), $this->payload($own->id, [
            'code' => 'PRODUCT-INACTIVE', 'scope' => 'product', 'scope_ids' => [$inactiveProduct->id],
        ]))->assertUnprocessable()->assertJsonValidationErrors('scope_ids');
        $this->actingAs($user)->postJson(route('order-table/vouchers/store'), $this->payload($own->id, [
            'code' => 'PRODUCT-FOREIGN', 'scope' => 'product', 'scope_ids' => [$foreignProduct->id],
        ]))->assertUnprocessable()->assertJsonValidationErrors('scope_ids');
        $this->actingAs($user)->postJson(route('order-table/vouchers/store'), $this->payload($own->id, [
            'code' => 'CATEGORY-ACTIVE', 'scope' => 'category', 'scope_ids' => [$activeCategory->id],
        ]))->assertOk();
        $this->actingAs($user)->postJson(route('order-table/vouchers/store'), $this->payload($own->id, [
            'code' => 'CATEGORY-INACTIVE', 'scope' => 'category', 'scope_ids' => [$inactiveCategory->id],
        ]))->assertUnprocessable()->assertJsonValidationErrors('scope_ids');
    }

    public function test_cross_outlet_creation_is_forbidden(): void
    {
        $own = $this->outlet('Voucher Authorized');
        $other = $this->outlet('Voucher Unauthorized');
        $user = $this->userFor($own, ['create order-table/vouchers']);

        $this->actingAs($user)->postJson(
            route('order-table/vouchers/store'),
            $this->payload($other->id, ['code' => 'FOREIGN-OUTLET']),
        )->assertForbidden();
    }

    public function test_global_voucher_requires_access_to_every_active_or_null_status_outlet(): void
    {
        $covered = $this->outlet('Global Covered');
        $user = $this->userFor($covered, ['create order-table/vouchers']);
        $user->update(['outlet_id' => $this->activeOutletIds()]);
        $missing = $this->outlet('Global Missing');

        $this->actingAs($user)->postJson(route('order-table/vouchers/store'), $this->payload(null, [
            'code' => 'GLOBAL-DENIED',
        ]))->assertForbidden();

        if (Schema::hasColumn('outlets', 'is_active')) {
            $missing->update(['is_active' => false]);
        }
        $user->update(['outlet_id' => $this->activeOutletIds()]);

        $response = $this->actingAs($user)->postJson(route('order-table/vouchers/store'), $this->payload(null, [
            'code' => 'GLOBAL-ALLOWED',
        ]))->assertOk();
        $this->assertNull(Voucher::findOrFail($response->json('data.id'))->outlet_id);
    }

    public function test_update_cannot_change_status_and_toggle_writes_semantic_audit(): void
    {
        $outlet = $this->outlet('Voucher Toggle');
        $user = $this->userFor($outlet, ['update order-table/vouchers']);
        $voucher = Voucher::create($this->modelAttributes($outlet->id, ['code' => 'TOGGLE-VOUCHER']));

        $this->actingAs($user)->putJson(route('order-table/vouchers/update', $voucher->id), $this->payload($outlet->id, [
            'code' => $voucher->code, 'status' => false,
        ]))->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertTrue($voucher->fresh()->status);

        $this->actingAs($user)->postJson(route('order-table/vouchers/toggle', $voucher->id))->assertOk();
        $this->assertFalse($voucher->fresh()->status);
        $this->assertDatabaseHas('ot_audit_logs', [
            'user_id' => $user->id, 'action' => 'voucher.disabled', 'subject_id' => $voucher->id,
        ]);
    }

    public function test_global_voucher_rejects_outlet_specific_scope(): void
    {
        $outlet = $this->outlet('Global Scope');
        $user = $this->userFor($outlet, ['create order-table/vouchers']);
        $user->update(['outlet_id' => $this->activeOutletIds()]);

        $this->actingAs($user)->postJson(route('order-table/vouchers/store'), $this->payload(null, [
            'code' => 'GLOBAL-PRODUCT',
            'scope' => 'product',
            'scope_ids' => [1],
        ]))->assertUnprocessable()->assertJsonValidationErrors('scope');
    }

    public function test_changing_percent_voucher_to_fixed_clears_max_discount(): void
    {
        $outlet = $this->outlet('Fixed Conversion');
        $user = $this->userFor($outlet, ['update order-table/vouchers']);
        $voucher = Voucher::create($this->modelAttributes($outlet->id, [
            'code' => 'PERCENT-TO-FIXED',
            'max_discount' => 25000,
        ]));

        $payload = $this->payload($outlet->id, [
            'code' => $voucher->code,
            'type' => 'fixed',
            'value' => 10000,
        ]);
        unset($payload['max_discount'], $payload['status']);

        $this->actingAs($user)
            ->putJson(route('order-table/vouchers/update', $voucher->id), $payload)
            ->assertOk();

        $this->assertNull($voucher->fresh()->max_discount);
    }

    public function test_vouchers_have_no_delete_route(): void
    {
        $this->assertFalse(Route::has('order-table/vouchers/destroy'));
    }

    private function payload(?int $outletId, array $overrides = []): array
    {
        return array_merge([
            'outlet_id' => $outletId,
            'code' => 'VOUCHER-'.uniqid(),
            'type' => 'percent',
            'value' => 10,
            'min_spend' => 0,
            'max_discount' => 10000,
            'quota_total' => null,
            'quota_per_device' => 1,
            'quota_per_session' => 1,
            'valid_from' => null,
            'valid_to' => null,
            'scope' => 'all',
            'scope_ids' => null,
            'status' => true,
        ], $overrides);
    }

    private function modelAttributes(?int $outletId, array $overrides = []): array
    {
        return array_merge($this->payload($outletId), ['quota_used' => 0], $overrides);
    }

    private function activeOutletIds(): array
    {
        return Outlets::query()
            ->when(Schema::hasColumn('outlets', 'is_active'), fn ($query) => $query
                ->where(fn ($query) => $query->where('is_active', '!=', false)->orWhereNull('is_active')))
            ->pluck('id')
            ->all();
    }
}

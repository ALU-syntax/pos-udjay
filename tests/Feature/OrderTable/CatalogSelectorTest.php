<?php

namespace Tests\Feature\OrderTable;

use App\Models\Category;
use App\Models\OrderTable\Voucher;
use App\Models\Product;
use App\Models\Promo;

class CatalogSelectorTest extends OrderTableTestCase
{
    public function test_selectors_require_a_relevant_permission_and_authorized_outlet(): void
    {
        $own = $this->outlet('Selector Own');
        $other = $this->outlet('Selector Other');
        $withoutPermission = $this->userFor($own);

        $this->actingAs($withoutPermission)->getJson(route('order-table/selectors/products', ['outlet_id' => $own->id]))->assertForbidden();

        $withPermission = $this->userFor($own, ['create order-table/vouchers']);
        $this->actingAs($withPermission)->getJson(route('order-table/selectors/products', ['outlet_id' => $other->id]))->assertForbidden();
    }

    public function test_product_and_category_selectors_return_data_arrays_scoped_to_active_outlet_catalog(): void
    {
        $own = $this->outlet('Selector Catalog Own');
        $other = $this->outlet('Selector Catalog Other');
        $user = $this->userFor($own, ['create order-table/vouchers']);
        $activeCategory = Category::create(['name' => 'Selector Active '.uniqid(), 'status' => true]);
        $inactiveCategory = Category::create(['name' => 'Selector Inactive '.uniqid(), 'status' => false]);
        $ownProduct = Product::create(['name' => 'Own Active Product', 'category_id' => $activeCategory->id, 'outlet_id' => $own->id, 'status' => true]);
        $inactiveProduct = Product::create(['name' => 'Own Inactive Product', 'category_id' => $activeCategory->id, 'outlet_id' => $own->id, 'status' => false]);
        $foreignProduct = Product::create(['name' => 'Foreign Active Product', 'category_id' => $inactiveCategory->id, 'outlet_id' => $other->id, 'status' => true]);

        $products = $this->actingAs($user)->getJson(route('order-table/selectors/products', ['outlet_id' => $own->id]))
            ->assertOk()->assertJsonStructure(['data'])->json('data');
        $this->assertIsArray($products);
        $this->assertContains($ownProduct->id, array_column($products, 'id'));
        $this->assertNotContains($inactiveProduct->id, array_column($products, 'id'));
        $this->assertNotContains($foreignProduct->id, array_column($products, 'id'));

        $categories = $this->actingAs($user)->getJson(route('order-table/selectors/categories', ['outlet_id' => $own->id]))
            ->assertOk()->assertJsonStructure(['data'])->json('data');
        $this->assertContains($activeCategory->id, array_column($categories, 'id'));
        $this->assertNotContains($inactiveCategory->id, array_column($categories, 'id'));
    }

    public function test_banner_target_selectors_scope_vouchers_promos_and_internal_routes(): void
    {
        $own = $this->outlet('Selector Targets Own');
        $other = $this->outlet('Selector Targets Other');
        $user = $this->userFor($own, ['create order-table/banners']);
        $ownVoucher = $this->voucher($own->id, 'SELECTOR-OWN', true);
        $globalVoucher = $this->voucher(null, 'SELECTOR-GLOBAL', true);
        $inactiveVoucher = $this->voucher($own->id, 'SELECTOR-INACTIVE', false);
        $foreignVoucher = $this->voucher($other->id, 'SELECTOR-FOREIGN', true);
        $ownPromo = $this->promo($own->id, 'Selector Own Promo', true);
        $inactivePromo = $this->promo($own->id, 'Selector Inactive Promo', false);
        $foreignPromo = $this->promo($other->id, 'Selector Foreign Promo', true);

        $vouchers = $this->actingAs($user)->getJson(route('order-table/selectors/vouchers', ['outlet_id' => $own->id]))->assertOk()->json('data');
        $voucherIds = array_column($vouchers, 'id');
        $this->assertContains($ownVoucher->id, $voucherIds);
        $this->assertContains($globalVoucher->id, $voucherIds);
        $this->assertNotContains($inactiveVoucher->id, $voucherIds);
        $this->assertNotContains($foreignVoucher->id, $voucherIds);

        $promos = $this->actingAs($user)->getJson(route('order-table/selectors/promos', ['outlet_id' => $own->id]))->assertOk()->json('data');
        $promoIds = array_column($promos, 'id');
        $this->assertContains($ownPromo->id, $promoIds);
        $this->assertNotContains($inactivePromo->id, $promoIds);
        $this->assertNotContains($foreignPromo->id, $promoIds);

        $internal = $this->actingAs($user)->getJson(route('order-table/selectors/internal', ['outlet_id' => $own->id]))
            ->assertOk()->assertJsonStructure(['data'])->json('data');
        $this->assertSame(['menu', 'cart', 'orders', 'vouchers'], array_column($internal, 'id'));
    }

    public function test_read_only_permissions_cannot_use_form_selectors(): void
    {
        $outlet = $this->outlet('Read Only Selector');
        $voucherReader = $this->userFor($outlet, ['read order-table/vouchers']);
        $bannerReader = $this->userFor($outlet, ['read order-table/banners']);

        $this->actingAs($voucherReader)
            ->getJson(route('order-table/selectors/products', ['outlet_id' => $outlet->id]))
            ->assertForbidden();
        $this->actingAs($bannerReader)
            ->getJson(route('order-table/selectors/internal'))
            ->assertForbidden();
    }

    private function voucher(?int $outletId, string $code, bool $status): Voucher
    {
        return Voucher::create([
            'outlet_id' => $outletId, 'code' => $code, 'type' => 'fixed', 'value' => 1000,
            'min_spend' => 0, 'scope' => 'all', 'scope_ids' => null, 'status' => $status,
        ]);
    }

    private function promo(int $outletId, string $name, bool $status): Promo
    {
        return Promo::create([
            'name' => $name, 'outlet_id' => $outletId, 'type' => 'discount',
            'purchase_requirement' => 'any_item', 'reward' => '[]', 'status' => $status,
        ]);
    }
}

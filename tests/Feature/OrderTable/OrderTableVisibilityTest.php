<?php

namespace Tests\Feature\OrderTable;

use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\Modifiers;
use App\Models\Outlets;
use App\Models\Product;
use App\Services\OrderTable\CatalogSelectorService;

/**
 * Pengaturan produk / grup modifier / opsi modifier yang tampil di menu Order Table.
 */
class OrderTableVisibilityTest extends OrderTableTestCase
{
    private function category(bool $showInOrderTable = true): Category
    {
        return Category::create([
            'name' => 'Visibility '.uniqid(),
            'status' => true,
            'reward_categories' => false,
            'show_in_order_table' => $showInOrderTable,
        ]);
    }

    private function product(Outlets $outlet, array $attributes = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Visibility Product '.uniqid(),
            'category_id' => $this->category()->id,
            'outlet_id' => $outlet->id,
            'status' => true,
            'harga_modal' => 0,
        ], $attributes));
    }

    private function group(Outlets $outlet, array $options, array $attributes = []): ModifierGroup
    {
        $group = ModifierGroup::create(array_merge([
            'name' => 'Visibility Group '.uniqid(),
            'outlet_id' => (string) $outlet->id,
            'is_required' => false,
        ], $attributes));
        foreach ($options as $name => $show) {
            Modifiers::create([
                'name' => $name, 'harga' => 0, 'stok' => null,
                'modifiers_group_id' => $group->id, 'show_in_order_table' => $show,
            ]);
        }

        return $group;
    }

    public function test_new_columns_default_to_visible(): void
    {
        $outlet = $this->outlet('Visibility Default');
        $product = $this->product($outlet)->refresh();
        $group = ModifierGroup::create(['name' => 'Default', 'outlet_id' => (string) $outlet->id])->refresh();
        $option = Modifiers::create(['name' => 'Opsi', 'harga' => 0, 'modifiers_group_id' => $group->id])->refresh();

        $this->assertTrue($product->show_in_order_table);
        $this->assertTrue($group->show_in_order_table);
        $this->assertTrue($option->show_in_order_table);
    }

    public function test_product_store_saves_flag_for_every_outlet_copy_and_update_changes_it(): void
    {
        $outletA = $this->outlet('Visibility Store A');
        $outletB = $this->outlet('Visibility Store B');
        $user = $this->userFor($outletA, ['create library/product', 'update library/product']);
        $user->update(['outlet_id' => [$outletA->id, $outletB->id]]);
        $name = 'Produk Khusus Kasir '.uniqid();

        $this->actingAs($user)->post(route('library/product/store'), [
            'name' => $name,
            'category_id' => $this->category()->id,
            'harga_modal' => 'Rp. 5.000',
            'harga_jual' => ['Rp. 10.000'],
            'stock' => [10],
            'status' => 1,
            'description' => null,
            'show_in_order_table' => 0,
            'outlet_id' => [$outletA->id, $outletB->id],
        ])->assertOk()->assertJson(['status' => 'success']);

        $products = Product::where('name', $name)->get();
        $this->assertCount(2, $products);
        $this->assertTrue($products->every(fn (Product $product) => $product->show_in_order_table === false));

        $product = $products->firstWhere('outlet_id', $outletA->id);
        $this->actingAs($user)->put(route('library/product/update', $product->id), [
            'name' => $name,
            'category_id' => $product->category_id,
            'harga_modal' => 'Rp. 5.000',
            'harga_jual' => ['Rp. 10.000'],
            'stock' => [10],
            'id_variant' => [$product->variants()->first()->id],
            'status' => 1,
            'show_in_order_table' => 1,
            'outlet_id' => $outletA->id,
        ])->assertOk();
        $this->assertTrue($product->refresh()->show_in_order_table);
    }

    public function test_product_store_requires_the_flag(): void
    {
        $outlet = $this->outlet('Visibility Required');
        $user = $this->userFor($outlet, ['create library/product']);

        $this->actingAs($user)->postJson(route('library/product/store'), [
            'name' => 'Tanpa Flag',
            'category_id' => $this->category()->id,
            'harga_modal' => 'Rp. 0',
            'harga_jual' => ['Rp. 1.000'],
            'stock' => [1],
            'status' => 1,
            'outlet_id' => [$outlet->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('show_in_order_table');
    }

    public function test_product_form_renders_flag_and_hidden_category_marker(): void
    {
        $outlet = $this->outlet('Visibility Product Form');
        $hiddenCategory = $this->category(false);
        $product = $this->product($outlet, ['category_id' => $hiddenCategory->id, 'show_in_order_table' => false]);
        $product->load('category', 'variants');

        $html = view('layouts.product.product-modal', [
            'data' => $product,
            'update' => true,
            'action' => route('library/product/update', $product->id),
            'categorys' => Category::whereKey($hiddenCategory->id)->get(),
            'outlets' => json_encode([['id' => $outlet->id, 'name' => $outlet->name]]),
        ])->render();

        $this->assertMatchesRegularExpression('/<option value="0"\s+selected\s*>\s*Tidak, khusus kasir/', $html);
        $this->assertStringContainsString('data-order-table="0"', $html);
        $this->assertStringContainsString('id="order-table-category-hidden"', $html);
    }

    public function test_product_toggle_requires_update_permission_and_own_outlet(): void
    {
        $own = $this->outlet('Visibility Toggle Own');
        $other = $this->outlet('Visibility Toggle Other');
        $product = $this->product($own);
        $foreign = $this->product($other);

        $reader = $this->userFor($own, ['read library/product']);
        $this->actingAs($reader)->patchJson(route('library/product/order-table', $product->id), ['show' => 0])->assertForbidden();

        $editor = $this->userFor($own, ['update library/product']);
        $this->actingAs($editor)->patchJson(route('library/product/order-table', $foreign->id), ['show' => 0])->assertForbidden();
        $this->assertTrue($foreign->refresh()->show_in_order_table);

        $this->actingAs($editor)->patchJson(route('library/product/order-table', $product->id), ['show' => 0])
            ->assertOk()->assertJsonPath('data.show_in_order_table', false);
        $this->assertFalse($product->refresh()->show_in_order_table);

        $this->actingAs($editor)->patchJson(route('library/product/order-table', $product->id), ['show' => 1])->assertOk();
        $this->assertTrue($product->refresh()->show_in_order_table);

        $this->actingAs($editor)->patchJson(route('library/product/order-table', $product->id), [])
            ->assertUnprocessable()->assertJsonValidationErrors('show');
    }

    public function test_modifier_store_keeps_option_flags_aligned_with_option_rows(): void
    {
        $outlet = $this->outlet('Visibility Modifier Store');
        $user = $this->userFor($outlet, ['create library/modifiers']);
        $name = 'Sugar '.uniqid();

        $this->actingAs($user)->post(route('library/modifiers/store'), [
            'name' => $name,
            'option_name' => ['Less', 'Normal', 'Extra'],
            'price' => ['Rp. 0', 'Rp. 0', 'Rp. 2.000'],
            'stok' => [null, null, null],
            'option_show_in_order_table' => [1, 0, 1],
            'show_in_order_table' => 1,
            'is_required' => 1,
            'outlet_id' => [$outlet->id],
        ])->assertOk()->assertJson(['status' => 'success']);

        $group = ModifierGroup::where('name', $name)->with('modifier')->firstOrFail();
        $this->assertTrue($group->show_in_order_table);
        $this->assertSame(
            ['Less' => true, 'Normal' => false, 'Extra' => true],
            $group->modifier->pluck('show_in_order_table', 'name')->all(),
        );
    }

    public function test_modifier_store_without_option_flags_treats_options_as_visible(): void
    {
        $outlet = $this->outlet('Visibility Modifier Legacy');
        $user = $this->userFor($outlet, ['create library/modifiers']);
        $name = 'Legacy '.uniqid();

        $this->actingAs($user)->post(route('library/modifiers/store'), [
            'name' => $name,
            'option_name' => ['A', 'B'],
            'price' => ['Rp. 0', 'Rp. 0'],
            'stok' => [null, null],
            'show_in_order_table' => 0,
            'outlet_id' => [$outlet->id],
        ])->assertOk();

        $group = ModifierGroup::where('name', $name)->with('modifier')->firstOrFail();
        $this->assertFalse($group->show_in_order_table);
        $this->assertTrue($group->modifier->every(fn (Modifiers $option) => $option->show_in_order_table));
    }

    public function test_modifier_update_maps_flags_for_existing_removed_and_new_rows(): void
    {
        $outlet = $this->outlet('Visibility Modifier Update');
        $user = $this->userFor($outlet, ['update library/modifiers']);
        $group = $this->group($outlet, ['Hot' => true, 'Warm' => true, 'Iced' => true]);
        [$hot, $warm, $iced] = $group->modifier()->orderBy('id')->get()->all();

        // "Warm" dihapus dari form; "Iced" disembunyikan; baris baru "Frozen" disembunyikan.
        $this->actingAs($user)->put(route('library/modifiers/update', $group->id), [
            'name' => $group->name,
            'option_name' => ['Hot', 'Iced', 'Frozen'],
            'id_modifier' => [$hot->id, $iced->id],
            'price' => ['Rp. 0', 'Rp. 0', 'Rp. 3.000'],
            'stok' => [null, null, null],
            'option_show_in_order_table' => [1, 0, 0],
            'show_in_order_table' => 1,
            'outlet_id' => $outlet->id,
        ])->assertOk();

        $this->assertSoftDeleted('modifiers', ['id' => $warm->id]);
        $this->assertSame(
            ['Hot' => true, 'Iced' => false, 'Frozen' => false],
            $group->modifier()->orderBy('id')->get()->pluck('show_in_order_table', 'name')->all(),
        );
    }

    public function test_modifier_group_toggle_requires_update_permission_and_own_outlet(): void
    {
        $own = $this->outlet('Visibility Group Toggle Own');
        $other = $this->outlet('Visibility Group Toggle Other');
        $group = $this->group($own, ['A' => true]);
        $foreign = $this->group($other, ['A' => true]);

        $reader = $this->userFor($own, ['read library/modifiers']);
        $this->actingAs($reader)->patchJson(route('library/modifiers/order-table', $group->id), ['show' => 0])->assertForbidden();

        $editor = $this->userFor($own, ['update library/modifiers']);
        $this->actingAs($editor)->patchJson(route('library/modifiers/order-table', $foreign->id), ['show' => 0])->assertForbidden();

        $this->actingAs($editor)->patchJson(route('library/modifiers/order-table', $group->id), ['show' => 0])
            ->assertOk()->assertJsonPath('data.show_in_order_table', false);
        $this->assertFalse($group->refresh()->show_in_order_table);
        $this->assertTrue($group->modifier()->first()->show_in_order_table, 'Opsi tidak ikut diubah oleh switch grup.');
    }

    public function test_modifier_form_renders_group_select_and_option_flags(): void
    {
        $outlet = $this->outlet('Visibility Modifier Form');
        $group = $this->group($outlet, ['Shown' => true, 'Hidden' => false], ['show_in_order_table' => false]);
        $group->load('modifier');

        $html = view('layouts.modifiers.modifiers-modal', [
            'data' => $group,
            'action' => route('library/modifiers/update', $group->id),
            'outlets' => json_encode([['id' => $outlet->id, 'name' => $outlet->name]]),
            'update' => true,
        ])->render();

        $this->assertMatchesRegularExpression('/<option value="0"\s+selected\s*>Tidak, khusus kasir/', $html);
        // 2 baris opsi + 1 template baris baru di JS "Add Row".
        $this->assertSame(3, substr_count($html, 'name="option_show_in_order_table[]" value="'));
        $this->assertStringContainsString('name="option_show_in_order_table[]" value="1"', $html);
        $this->assertStringContainsString('name="option_show_in_order_table[]" value="0"', $html);
    }

    public function test_selector_search_lists_only_visible_products_and_categories_but_validation_still_accepts_hidden(): void
    {
        $outlet = $this->outlet('Visibility Selector');
        $user = $this->userFor($outlet, ['create order-table/vouchers']);
        $shownCategory = $this->category();
        $hiddenOnlyCategory = $this->category();
        $hiddenCategory = $this->category(false);
        $shown = $this->product($outlet, ['category_id' => $shownCategory->id]);
        $hidden = $this->product($outlet, ['category_id' => $hiddenOnlyCategory->id, 'show_in_order_table' => false]);
        $this->product($outlet, ['category_id' => $hiddenCategory->id]);

        $products = $this->actingAs($user)->getJson(route('order-table/selectors/products', ['outlet_id' => $outlet->id]))
            ->assertOk()->json('data');
        $this->assertContains($shown->id, array_column($products, 'id'));
        $this->assertNotContains($hidden->id, array_column($products, 'id'));

        $categories = $this->actingAs($user)->getJson(route('order-table/selectors/categories', ['outlet_id' => $outlet->id]))
            ->assertOk()->json('data');
        $categoryIds = array_column($categories, 'id');
        $this->assertContains($shownCategory->id, $categoryIds);
        $this->assertNotContains($hiddenOnlyCategory->id, $categoryIds, 'Kategori yang semua produknya disembunyikan tidak ditawarkan.');
        $this->assertNotContains($hiddenCategory->id, $categoryIds);

        // Banner/voucher lama yang menunjuk produk tersembunyi tetap bisa disimpan ulang.
        $this->assertTrue(app(CatalogSelectorService::class)->validIds('product', $user, $outlet->id, [$hidden->id]));
    }
}

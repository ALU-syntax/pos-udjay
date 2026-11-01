<?php

namespace Tests\Feature\OrderTable;

use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\Modifiers;
use App\Models\Outlets;
use App\Models\Product;
use App\Models\User;
use App\Models\VariantProduct;

/**
 * Halaman Library (produk, kategori, modifier) bergaya report: endpoint DataTables dan halaman index.
 */
class LibraryListPagesTest extends OrderTableTestCase
{
    private const PERMISSIONS = [
        'read library/product', 'update library/product', 'delete library/product',
        'read library/category', 'update library/category',
        'read library/modifiers', 'update library/modifiers', 'delete library/modifiers',
    ];

    /** Controller lama memanggil json_decode(user->outlet_id), jadi outlet_id disimpan sebagai teks JSON. */
    private function libraryUser(Outlets $outlet): User
    {
        $user = $this->userFor($outlet, self::PERMISSIONS);
        $user->forceFill(['outlet_id' => json_encode([$outlet->id])])->save();

        return $user->refresh();
    }

    private function datatable(User $user, string $route, array $params = []): array
    {
        // Yajra menyimpan request pertama di singleton; di production tiap request memakai container baru.
        $this->app->forgetInstance('datatables.request');

        return $this->actingAs($user)
            ->getJson(route($route, array_merge(['draw' => 1, 'start' => 0, 'length' => 100], $params)), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->json();
    }

    private function product(Outlets $outlet, string $name, array $attributes = [], array $prices = [10000]): Product
    {
        $product = Product::create(array_merge([
            'name' => $name, 'outlet_id' => $outlet->id, 'status' => true, 'harga_modal' => 4000,
        ], $attributes));
        foreach ($prices as $index => $price) {
            VariantProduct::create(['name' => $name.' '.$index, 'harga' => $price, 'stok' => 5, 'product_id' => $product->id]);
        }

        return $product;
    }

    public function test_product_table_is_scoped_filtered_and_renders_report_cells(): void
    {
        $outlet = $this->outlet('Library Product');
        $other = $this->outlet('Library Product Other');
        $user = $this->libraryUser($outlet);
        $hiddenCategory = Category::create(['name' => 'Lib Hidden '.uniqid(), 'status' => true, 'reward_categories' => false, 'show_in_order_table' => false]);
        $shown = $this->product($outlet, 'Lib Latte', ['category_id' => $hiddenCategory->id], [18000, 22000]);
        $hidden = $this->product($outlet, 'Lib Internal', ['show_in_order_table' => false]);
        $foreign = $this->product($other, 'Lib Foreign');

        $all = collect($this->datatable($user, 'library/product')['data'])->keyBy('DT_RowId');
        $this->assertTrue($all->has($shown->id) && $all->has($hidden->id));
        $this->assertFalse($all->has($foreign->id), 'Produk outlet lain tidak tampil.');

        $row = $all[$shown->id];
        $this->assertStringContainsString('ob-item-cell', $row['name']);
        $this->assertStringContainsString('Rp 18.000 – Rp 22.000', $row['harga_jual']);
        $this->assertStringContainsString('2 varian', $row['harga_jual']);
        $this->assertStringContainsString('order-table-switch', $row['show_in_order_table']);
        $this->assertStringContainsString('Kategori disembunyikan', $row['show_in_order_table']);
        $this->assertStringContainsString(route('library/product/edit', $shown->id), $row['action']);
        $this->assertStringContainsString('delete', $row['action']);

        $hiddenOnly = collect($this->datatable($user, 'library/product', ['order_table' => '0'])['data'])->pluck('DT_RowId');
        $this->assertEquals([$hidden->id], $hiddenOnly->all());

        $foreignFilter = $this->datatable($user, 'library/product', ['outlet' => (string) $other->id])['data'];
        $this->assertNotEmpty($foreignFilter, 'Filter outlet lain diabaikan, tetap dibatasi outlet milik user.');
        $this->assertNotContains($foreign->id, array_column($foreignFilter, 'DT_RowId'));
    }

    public function test_category_table_filters_by_order_table_and_status(): void
    {
        $outlet = $this->outlet('Library Category');
        $user = $this->libraryUser($outlet);
        $hidden = Category::create(['name' => 'Lib Cat Hidden '.uniqid(), 'status' => true, 'reward_categories' => true, 'show_in_order_table' => false]);
        $inactive = Category::create(['name' => 'Lib Cat Inactive '.uniqid(), 'status' => false, 'reward_categories' => false, 'show_in_order_table' => true]);

        $rows = collect($this->datatable($user, 'library/category', ['order_table' => '0'])['data'])->keyBy('DT_RowId');
        $this->assertTrue($rows->has($hidden->id));
        $this->assertFalse($rows->has($inactive->id));
        $this->assertStringContainsString('Disembunyikan', $rows[$hidden->id]['show_in_order_table']);
        $this->assertStringContainsString('Reward', $rows[$hidden->id]['reward_categories']);
        $this->assertStringContainsString(route('library/category/edit', $hidden->id), $rows[$hidden->id]['action']);

        $inactiveRows = collect($this->datatable($user, 'library/category', ['status' => '0'])['data'])->pluck('DT_RowId');
        $this->assertContains($inactive->id, $inactiveRows);
        $this->assertNotContains($hidden->id, $inactiveRows);
    }

    public function test_modifier_table_is_scoped_on_first_load_and_marks_hidden_options(): void
    {
        $outlet = $this->outlet('Library Modifier');
        $other = $this->outlet('Library Modifier Other');
        $user = $this->libraryUser($outlet);
        $group = ModifierGroup::create(['name' => 'Lib Ice', 'outlet_id' => (string) $outlet->id, 'is_required' => true, 'product_id' => json_encode(['1', '2'])]);
        Modifiers::create(['name' => 'Less Ice', 'harga' => 0, 'modifiers_group_id' => $group->id]);
        Modifiers::create(['name' => 'Extra Ice', 'harga' => 2000, 'modifiers_group_id' => $group->id, 'show_in_order_table' => false]);
        $foreign = ModifierGroup::create(['name' => 'Lib Foreign', 'outlet_id' => (string) $other->id]);

        // Tanpa parameter outlet (muatan pertama) tetap dibatasi outlet milik user.
        $rows = collect($this->datatable($user, 'library/modifiers')['data'])->keyBy('DT_RowId');
        $this->assertTrue($rows->has($group->id));
        $this->assertFalse($rows->has($foreign->id));

        $row = $rows[$group->id];
        $this->assertStringContainsString('Wajib dipilih · 2 produk', $row['name']);
        $this->assertMatchesRegularExpression('/ob-chip ob-chip--hidden"[^>]*>Extra Ice <span class="ob-chip__price">\+2\.000/', $row['option_name']);
        $this->assertStringContainsString('<span class="ob-chip">Less Ice</span>', $row['option_name']);
        $this->assertStringContainsString(route('library/modifiers/getProduct', $group->id), $row['action']);

        $optional = collect($this->datatable($user, 'library/modifiers', ['required' => '0'])['data'])->pluck('DT_RowId');
        $this->assertNotContains($group->id, $optional);
    }

    public function test_index_pages_render_with_report_layout(): void
    {
        $outlet = $this->outlet('Library Index');
        $user = $this->libraryUser($outlet);

        foreach (['library/product' => 'Daftar Produk', 'library/category' => 'Daftar Kategori', 'library/modifiers' => 'Daftar Modifier'] as $route => $title) {
            $this->actingAs($user)->get(route($route))
                ->assertOk()
                ->assertSee('main-content ob-page', false)
                ->assertSee($title)
                ->assertSee('id="filter-order-table"', false);
        }
    }
}

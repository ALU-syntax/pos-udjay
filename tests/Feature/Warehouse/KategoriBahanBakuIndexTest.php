<?php

namespace Tests\Feature\Warehouse;

use App\Models\RawMaterialCategories;
use App\Models\RawMaterials;
use App\Models\RawStorageType;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KategoriBahanBakuIndexTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(array $permissions = []): User
    {
        $role = Role::firstOrCreate(['name' => 'Uji Admin Warehouse', 'guard_name' => 'web']);
        $admin = User::factory()->create(['role' => $role->id]);

        foreach ($permissions as $name) {
            $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }

        return $admin;
    }

    private function category(array $attributes = []): RawMaterialCategories
    {
        return RawMaterialCategories::create(array_merge([
            'name' => 'Uji Kategori ' . uniqid(),
            'is_active' => true,
            'notes' => null,
        ], $attributes));
    }

    private function rawMaterial(RawMaterialCategories $category): RawMaterials
    {
        $unit = Satuan::create(['name' => 'Uji Satuan ' . uniqid(), 'is_active' => 1]);
        $storage = RawStorageType::query()->forceCreate(['name' => 'Uji Simpan ' . uniqid()]);

        return RawMaterials::query()->forceCreate([
            'name' => 'Uji Bahan ' . uniqid(),
            'raw_material_category_id' => $category->id,
            'base_unit_id' => $unit->id,
            'storage_type_id' => $storage->id,
            'is_active' => true,
        ]);
    }

    private function rows(User $actor, array $params = []): Collection
    {
        // Yajra menyimpan request sebagai singleton; dilupakan agar tiap panggilan membaca parameternya sendiri
        app()->forgetInstance('datatables.request');

        // Kolom dikirim seperti browser, karena pencarian global hanya berlaku pada kolom yang searchable
        $columns = collect(['name', 'raw_materials_count', 'is_active', 'updated_at', 'action'])
            ->map(fn ($column) => [
                'data' => $column,
                'name' => $column,
                'searchable' => $column === 'name' ? 'true' : 'false',
                'orderable' => 'false',
                'search' => ['value' => '', 'regex' => 'false'],
            ])
            ->all();

        return collect($this->actingAs($actor)->getJson(route('library/category-bahan-baku', array_merge([
            'draw' => 1, 'start' => 0, 'length' => 1000, 'columns' => $columns,
        ], $params)), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'))
            ->keyBy(fn ($row) => (int) $row['DT_RowId']);
    }

    public function test_index_page_renders_report_style(): void
    {
        $this->actingAs($this->admin(['create library/category-bahan-baku']))
            ->get(route('library/category-bahan-baku'))
            ->assertOk()
            ->assertSee('ob-page', false)
            ->assertSee('Daftar Kategori')
            ->assertSee('filter-status')
            ->assertSee('filter-usage')
            ->assertSee(route('library/category-bahan-baku/create'));
    }

    public function test_add_button_is_hidden_without_create_permission(): void
    {
        $this->actingAs($this->admin())
            ->get(route('library/category-bahan-baku'))
            ->assertOk()
            ->assertDontSee(route('library/category-bahan-baku/create'));
    }

    public function test_rows_escape_name_and_show_notes_status_and_usage(): void
    {
        $admin = $this->admin();
        $used = $this->category(['name' => '<b>Uji Escape</b>', 'notes' => 'Wajib cold storage', 'is_active' => false]);
        $empty = $this->category();
        $this->rawMaterial($used);
        $this->rawMaterial($used);
        $this->rawMaterial($used)->delete(); // soft delete tidak ikut dihitung

        $rows = $this->rows($admin);

        $this->assertStringContainsString('&lt;b&gt;Uji Escape&lt;/b&gt;', $rows[$used->id]['name']);
        $this->assertStringContainsString('Wajib cold storage', $rows[$used->id]['name']);
        $this->assertStringContainsString('Nonaktif', $rows[$used->id]['is_active']);
        $this->assertStringContainsString('2 bahan baku', $rows[$used->id]['raw_materials_count']);

        $this->assertStringContainsString('Aktif', $rows[$empty->id]['is_active']);
        $this->assertStringContainsString('Belum dipakai', $rows[$empty->id]['raw_materials_count']);
    }

    public function test_status_and_usage_filters_limit_rows(): void
    {
        $admin = $this->admin();
        $activeUsed = $this->category(['is_active' => true]);
        $activeEmpty = $this->category(['is_active' => true]);
        $inactiveUsed = $this->category(['is_active' => false]);
        $this->rawMaterial($activeUsed);
        $this->rawMaterial($inactiveUsed);

        $inactive = $this->rows($admin, ['status' => '0']);
        $this->assertTrue($inactive->has($inactiveUsed->id));
        $this->assertFalse($inactive->has($activeUsed->id));

        $empty = $this->rows($admin, ['usage' => 'empty']);
        $this->assertTrue($empty->has($activeEmpty->id));
        $this->assertFalse($empty->has($activeUsed->id));
        $this->assertFalse($empty->has($inactiveUsed->id));

        $activeAndUsed = $this->rows($admin, ['status' => '1', 'usage' => 'used']);
        $this->assertTrue($activeAndUsed->has($activeUsed->id));
        $this->assertFalse($activeAndUsed->has($activeEmpty->id));
        $this->assertFalse($activeAndUsed->has($inactiveUsed->id));
    }

    public function test_search_matches_notes(): void
    {
        $admin = $this->admin();
        $match = $this->category(['notes' => 'Ujcari freezer minus 18']);
        $other = $this->category(['notes' => 'Rak kering']);

        $rows = $this->rows($admin, ['search' => ['value' => 'Ujcari freezer', 'regex' => 'false']]);

        $this->assertTrue($rows->has($match->id));
        $this->assertFalse($rows->has($other->id));
    }

    public function test_row_actions_follow_permissions(): void
    {
        $category = $this->category();

        $readOnly = $this->rows($this->admin(['read library/category-bahan-baku']))[$category->id]['action'];
        $this->assertStringContainsString(route('library/category-bahan-baku/detail', $category->id), $readOnly);
        $this->assertStringNotContainsString(route('library/category-bahan-baku/edit', $category->id), $readOnly);

        $editor = $this->rows($this->admin(['update library/category-bahan-baku']))[$category->id]['action'];
        $this->assertStringNotContainsString(route('library/category-bahan-baku/detail', $category->id), $editor);
        $this->assertStringContainsString(route('library/category-bahan-baku/edit', $category->id), $editor);
    }

    public function test_store_returns_updated_stats(): void
    {
        $before = RawMaterialCategories::count();

        $this->actingAs($this->admin(['create library/category-bahan-baku']))
            ->postJson(route('library/category-bahan-baku/store'), [
                'name' => 'Uji Kategori Baru ' . uniqid(),
                'is_active' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('data.total', $before + 1);
    }

    public function test_edit_modal_keeps_saved_values(): void
    {
        $category = $this->category(['name' => 'Uji Modal ' . uniqid(), 'notes' => 'Catatan modal uji', 'is_active' => false]);

        $this->actingAs($this->admin(['update library/category-bahan-baku']))
            ->get(route('library/category-bahan-baku/edit', $category->id))
            ->assertOk()
            ->assertSee('Edit Kategori Bahan Baku')
            ->assertSee('ob-modal', false)
            ->assertSee('value="' . $category->name . '"', false)
            ->assertSee('Catatan modal uji')
            ->assertSee('<option value="0" selected>Nonaktif</option>', false);
    }
}

<?php

namespace Tests\Feature\Warehouse;

use App\Models\Brand;
use App\Models\Inventory;
use App\Models\InventoryRawMaterialStockBalance;
use App\Models\InventoryType;
use App\Models\Outlets;
use App\Models\RawMaterials;
use App\Models\RawStorageType;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InventoryIndexTest extends TestCase
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

    private function type(array $attributes = []): InventoryType
    {
        return InventoryType::create(array_merge(['name' => 'Uji Tipe ' . uniqid(), 'is_active' => true], $attributes));
    }

    private function location(array $attributes = []): Inventory
    {
        return Inventory::create(array_merge([
            'name' => 'Uji Lokasi ' . uniqid(),
            'inventory_type_id' => $attributes['inventory_type_id'] ?? $this->type()->id,
            'is_active' => true,
        ], $attributes));
    }

    private function outlet(): Outlets
    {
        return Outlets::create(['name' => 'Uji Outlet ' . uniqid(), 'address' => 'Alamat uji', 'phone' => '0800000000']);
    }

    private function brand(): Brand
    {
        return Brand::query()->forceCreate(['name' => 'Uji Brand ' . uniqid()]);
    }

    private function stock(Inventory $location, float $available): void
    {
        $material = RawMaterials::query()->forceCreate([
            'name' => 'Uji Bahan ' . uniqid(),
            'base_unit_id' => Satuan::create(['name' => 'Uji Satuan ' . uniqid(), 'is_active' => 1])->id,
            'storage_type_id' => RawStorageType::query()->forceCreate(['name' => 'Uji Simpan ' . uniqid()])->id,
            'is_active' => true,
        ]);

        InventoryRawMaterialStockBalance::query()->forceCreate([
            'raw_material_id' => $material->id,
            'inventory_id' => $location->id,
            'qty_available' => $available,
            'qty_reserved' => 0,
        ]);
    }

    private function rows(User $actor, array $params = []): Collection
    {
        // Yajra menyimpan request sebagai singleton; dilupakan agar tiap panggilan membaca parameternya sendiri
        app()->forgetInstance('datatables.request');

        // Kolom dikirim seperti browser, karena pencarian global hanya berlaku pada kolom yang searchable
        $columns = collect(['name', 'type_name', 'scope', 'stock_summary', 'is_active', 'updated_at', 'action'])
            ->map(fn ($column) => [
                'data' => $column,
                'name' => $column,
                'searchable' => $column === 'name' ? 'true' : 'false',
                'orderable' => 'false',
                'search' => ['value' => '', 'regex' => 'false'],
            ])
            ->all();

        return collect($this->actingAs($actor)->getJson(route('warehouse/inventory', array_merge([
            'draw' => 1, 'start' => 0, 'length' => 1000, 'columns' => $columns,
        ], $params)), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'))
            ->keyBy(fn ($row) => (int) $row['DT_RowId']);
    }

    public function test_index_page_renders_report_style(): void
    {
        $this->actingAs($this->admin(['create warehouse/inventory']))
            ->get(route('warehouse/inventory'))
            ->assertOk()
            ->assertSee('ob-page', false)
            ->assertSee('Daftar Lokasi')
            ->assertSee('filter-type')
            ->assertSee('filter-outlet')
            ->assertSee('filter-brand')
            ->assertSee('filter-status')
            ->assertSee(route('warehouse/inventory/create'));
    }

    public function test_add_button_is_hidden_without_create_permission(): void
    {
        $this->actingAs($this->admin())
            ->get(route('warehouse/inventory'))
            ->assertOk()
            ->assertDontSee(route('warehouse/inventory/create'));
    }

    public function test_rows_escape_name_and_show_parent_scope_and_stock_count(): void
    {
        $outlet = $this->outlet();
        $brand = $this->brand();
        $parent = $this->location(['name' => 'Uji Gudang Induk ' . uniqid()]);
        $location = $this->location([
            'name' => '<b>Uji Rak</b>',
            'code' => 'UJ-RAK-1',
            'parent_id' => $parent->id,
            'outlet_id' => $outlet->id,
            'brand_id' => $brand->id,
            'is_active' => false,
        ]);
        $this->stock($location, 500);
        $this->stock($location, 0);
        $this->stock($location, 0);

        $rows = $this->rows($this->admin());

        $row = $rows[$location->id];
        $this->assertStringContainsString('&lt;b&gt;Uji Rak&lt;/b&gt;', $row['name']);
        $this->assertStringContainsString('UJ-RAK-1', $row['name']);
        $this->assertStringContainsString('Bagian dari: ' . $parent->name, $row['name']);
        $this->assertStringContainsString($location->type->name, $row['type_name']);
        $this->assertStringContainsString($outlet->name, $row['scope']);
        $this->assertStringContainsString($brand->name, $row['scope']);
        // Hitungan bahan, bukan jumlah qty lintas satuan
        $this->assertStringContainsString('3 bahan', $row['stock_summary']);
        $this->assertStringContainsString('2 habis', $row['stock_summary']);
        $this->assertStringContainsString('Nonaktif', $row['is_active']);

        $parentRow = $rows[$parent->id];
        $this->assertStringContainsString('Tanpa kode', $parentRow['name']);
        $this->assertStringContainsString('Semua outlet', $parentRow['scope']);
        $this->assertStringContainsString('Semua brand', $parentRow['scope']);
        $this->assertStringContainsString('Belum ada stok', $parentRow['stock_summary']);
    }

    public function test_filters_limit_rows_and_can_be_combined(): void
    {
        $admin = $this->admin();
        $type = $this->type();
        $outlet = $this->outlet();
        $brand = $this->brand();
        $match = $this->location(['inventory_type_id' => $type->id, 'outlet_id' => $outlet->id, 'brand_id' => $brand->id]);
        $sameTypeInactive = $this->location(['inventory_type_id' => $type->id, 'outlet_id' => $outlet->id, 'is_active' => false]);
        $central = $this->location();

        $byType = $this->rows($admin, ['type' => (string) $type->id]);
        $this->assertTrue($byType->has($match->id));
        $this->assertTrue($byType->has($sameTypeInactive->id));
        $this->assertFalse($byType->has($central->id));

        $byOutlet = $this->rows($admin, ['outlet' => (string) $outlet->id]);
        $this->assertTrue($byOutlet->has($match->id));
        $this->assertFalse($byOutlet->has($central->id));

        // "all" = lokasi tanpa outlet/brand (berlaku semua)
        $allOutlets = $this->rows($admin, ['outlet' => 'all']);
        $this->assertTrue($allOutlets->has($central->id));
        $this->assertFalse($allOutlets->has($match->id));

        $byBrand = $this->rows($admin, ['brand' => (string) $brand->id]);
        $this->assertTrue($byBrand->has($match->id));
        $this->assertFalse($byBrand->has($sameTypeInactive->id));

        $activeOfType = $this->rows($admin, ['type' => (string) $type->id, 'status' => '1']);
        $this->assertTrue($activeOfType->has($match->id));
        $this->assertFalse($activeOfType->has($sameTypeInactive->id));
    }

    public function test_search_matches_code(): void
    {
        $match = $this->location(['code' => 'UJCARI-' . uniqid()]);
        $other = $this->location(['code' => 'LAIN-' . uniqid()]);

        $rows = $this->rows($this->admin(), ['search' => ['value' => $match->code, 'regex' => 'false']]);

        $this->assertTrue($rows->has($match->id));
        $this->assertFalse($rows->has($other->id));
    }

    public function test_row_actions_follow_permissions(): void
    {
        $location = $this->location();
        $detail = route('warehouse/inventory/detail', $location->id);
        $edit = route('warehouse/inventory/edit', $location->id);
        $destroy = route('warehouse/inventory/destroy', $location->id);

        $readOnly = $this->rows($this->admin(['read warehouse/inventory']))[$location->id]['action'];
        $this->assertStringContainsString($detail, $readOnly);
        $this->assertStringNotContainsString($edit, $readOnly);
        $this->assertStringNotContainsString($destroy, $readOnly);

        $editor = $this->rows($this->admin(['update warehouse/inventory', 'delete warehouse/inventory']))[$location->id]['action'];
        $this->assertStringNotContainsString($detail, $editor);
        $this->assertStringContainsString($edit, $editor);
        $this->assertStringContainsString($destroy, $editor);
    }

    public function test_stocked_locations_stat_ignores_archived_location(): void
    {
        $admin = $this->admin(['create warehouse/inventory']);
        $stocked = $this->location();
        $this->stock($stocked, 10);
        $archived = $this->location();
        $this->stock($archived, 10);

        $before = $this->actingAs($admin)
            ->postJson(route('warehouse/inventory/store'), ['name' => 'Uji Baru ' . uniqid(), 'inventory_type_id' => $this->type()->id, 'is_active' => 1])
            ->assertOk()
            ->json('data.stocked_locations');

        $archived->delete();

        $after = $this->actingAs($admin)
            ->postJson(route('warehouse/inventory/store'), ['name' => 'Uji Baru ' . uniqid(), 'inventory_type_id' => $this->type()->id, 'is_active' => 1])
            ->assertOk()
            ->json('data.stocked_locations');

        $this->assertSame($before - 1, $after);
    }

    public function test_edit_modal_keeps_saved_values(): void
    {
        $parent = $this->location(['name' => 'Uji Induk Modal ' . uniqid()]);
        $location = $this->location(['name' => 'Uji Modal ' . uniqid(), 'code' => 'UJ-MDL', 'parent_id' => $parent->id, 'is_active' => false]);

        $this->actingAs($this->admin(['update warehouse/inventory']))
            ->get(route('warehouse/inventory/edit', $location->id))
            ->assertOk()
            ->assertSee('Edit Lokasi Inventory')
            ->assertSee('ob-modal', false)
            ->assertSee('value="' . $location->name . '"', false)
            ->assertSee('value="UJ-MDL"', false)
            ->assertSee($parent->name)
            ->assertSee('<option value="0" selected>Nonaktif</option>', false);
    }

    public function test_edit_modal_hides_self_and_descendants_from_parent_options(): void
    {
        $root = $this->location(['name' => 'Uji Gudang ' . uniqid()]);
        $child = $this->location(['name' => 'Uji Rak ' . uniqid(), 'parent_id' => $root->id]);
        $grandchild = $this->location(['name' => 'Uji Laci ' . uniqid(), 'parent_id' => $child->id]);
        $other = $this->location(['name' => 'Uji Gudang Lain ' . uniqid()]);

        $html = $this->actingAs($this->admin(['update warehouse/inventory']))
            ->get(route('warehouse/inventory/edit', $root->id))
            ->assertOk()
            ->getContent();

        // Hanya select parent: ID lokasi bisa sama dengan ID outlet/brand/tipe di select lain
        $this->assertSame(1, preg_match('/<select id="parent_id".*?<\/select>/s', $html, $match));
        $parentSelect = $match[0];

        $this->assertStringContainsString('<option value="' . $other->id . '"', $parentSelect);
        $this->assertStringNotContainsString('<option value="' . $root->id . '"', $parentSelect);
        $this->assertStringNotContainsString('<option value="' . $child->id . '"', $parentSelect);
        $this->assertStringNotContainsString('<option value="' . $grandchild->id . '"', $parentSelect);
    }

    public function test_update_rejects_descendant_as_parent(): void
    {
        $root = $this->location(['name' => 'Uji Gudang ' . uniqid()]);
        $child = $this->location(['name' => 'Uji Rak ' . uniqid(), 'parent_id' => $root->id]);
        $grandchild = $this->location(['name' => 'Uji Laci ' . uniqid(), 'parent_id' => $child->id]);
        $admin = $this->admin(['update warehouse/inventory']);

        foreach ([$root, $grandchild] as $parent) {
            $this->actingAs($admin)
                ->putJson(route('warehouse/inventory/update', $root->id), [
                    'name' => $root->name,
                    'parent_id' => $parent->id,
                    'inventory_type_id' => $root->inventory_type_id,
                    'is_active' => 1,
                ])
                ->assertStatus(422)
                ->assertJsonValidationErrors('parent_id');
        }

        $this->assertNull($root->fresh()->parent_id);

        $other = $this->location(['name' => 'Uji Gudang Lain ' . uniqid()]);

        $this->actingAs($admin)
            ->putJson(route('warehouse/inventory/update', $root->id), [
                'name' => $root->name,
                'parent_id' => $other->id,
                'inventory_type_id' => $root->inventory_type_id,
                'is_active' => 1,
            ])
            ->assertOk();

        $this->assertSame($other->id, $root->fresh()->parent_id);
    }
}

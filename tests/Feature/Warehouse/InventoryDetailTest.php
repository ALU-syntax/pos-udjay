<?php

namespace Tests\Feature\Warehouse;

use App\Models\Inventory;
use App\Models\InventoryRawMaterialStockBalance;
use App\Models\InventoryType;
use App\Models\RawMaterialCategories;
use App\Models\RawMaterials;
use App\Models\RawStorageType;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InventoryDetailTest extends TestCase
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

    private function location(array $attributes = []): Inventory
    {
        $type = InventoryType::create(['name' => 'Uji Tipe ' . uniqid(), 'is_active' => true]);

        return Inventory::create(array_merge([
            'name' => 'Uji Lokasi ' . uniqid(),
            'inventory_type_id' => $type->id,
            'is_active' => true,
        ], $attributes));
    }

    private function material(array $attributes = [], ?Satuan $unit = null): RawMaterials
    {
        return RawMaterials::query()->forceCreate(array_merge([
            'name' => 'Uji Bahan ' . uniqid(),
            'base_unit_id' => ($unit ?? Satuan::create(['name' => 'Uji Satuan ' . uniqid(), 'is_active' => 1]))->id,
            'storage_type_id' => RawStorageType::query()->forceCreate(['name' => 'Uji Simpan ' . uniqid()])->id,
            'is_active' => true,
        ], $attributes));
    }

    private function stock(Inventory $location, RawMaterials $material, float $available, float $reserved = 0): InventoryRawMaterialStockBalance
    {
        return InventoryRawMaterialStockBalance::query()->forceCreate([
            'raw_material_id' => $material->id,
            'inventory_id' => $location->id,
            'qty_available' => $available,
            'qty_reserved' => $reserved,
        ]);
    }

    public function test_detail_shows_stock_rows_statuses_and_count_stats(): void
    {
        $gram = Satuan::create(['name' => 'Uji Gram ' . uniqid(), 'symbol' => 'ujgr', 'is_active' => 1]);
        $category = RawMaterialCategories::create(['name' => 'Uji Kat Detail ' . uniqid(), 'is_active' => true]);
        $parent = $this->location(['name' => 'Uji Gudang Induk ' . uniqid()]);
        $location = $this->location(['name' => '<b>Uji Rak</b>', 'code' => 'UJ-RAK', 'parent_id' => $parent->id]);
        $child = $this->location(['name' => 'Uji Sub Rak ' . uniqid(), 'parent_id' => $location->id, 'is_active' => false]);

        $this->stock($location, $this->material(['name' => 'Uji A Tersedia', 'code' => 'UJ-A', 'raw_material_category_id' => $category->id], $gram), 25000);
        $this->stock($location, $this->material(['name' => 'Uji B Sebagian'], $gram), 100, 40.5);
        $this->stock($location, $this->material(['name' => 'Uji C Dipesan'], $gram), 10, 10);
        $this->stock($location, $this->material(['name' => 'Uji D Habis', 'is_active' => false], $gram), 0);
        $archived = $this->material(['name' => 'Uji E Arsip'], $gram);
        $this->stock($location, $archived, 5);
        $archived->delete();

        $response = $this->actingAs($this->admin(['read warehouse/inventory']))
            ->get(route('warehouse/inventory/detail', $location->id))
            ->assertOk()
            ->assertSee('ob-page', false)
            ->assertSee('&lt;b&gt;Uji Rak&lt;/b&gt;', false)
            ->assertSee('UJ-RAK')
            ->assertSee(route('warehouse/inventory/detail', $parent->id))
            // Qty diformat tanpa nol desimal, dengan satuan bahan
            ->assertSee('25.000')
            ->assertSee('ujgr')
            ->assertSee('40,5')
            ->assertSee('59,5')
            ->assertSee('UJ-A · ' . $category->name)
            ->assertSee('Tersedia')
            ->assertSee('Sebagian Dipesan')
            ->assertSee('Dipesan Semua')
            ->assertSee('Habis')
            ->assertSee('Bahan nonaktif')
            ->assertSee('Bahan sudah diarsipkan')
            ->assertSee($child->name)
            ->assertDontSee('Out of Stock');

        // Kartu ringkasan: hitungan bahan, bukan jumlah qty
        $stats = $response->viewData('stockStats');
        $this->assertSame(['total_materials' => 5, 'empty_materials' => 1, 'reserved_materials' => 2], $stats);

        // Urut nama bahan
        $names = $response->viewData('stockBalances')->map(fn ($balance) => $balance->rawMaterial->name)->all();
        $this->assertSame(['Uji A Tersedia', 'Uji B Sebagian', 'Uji C Dipesan', 'Uji D Habis', 'Uji E Arsip'], $names);
    }

    public function test_detail_shows_empty_states(): void
    {
        $location = $this->location();

        $this->actingAs($this->admin(['read warehouse/inventory']))
            ->get(route('warehouse/inventory/detail', $location->id))
            ->assertOk()
            ->assertSee('Belum ada stok')
            ->assertSee('Belum ada lokasi lain di dalam lokasi ini.')
            ->assertSee('Lokasi utama')
            ->assertDontSee('id="inventory-stock-table"', false);
    }

    public function test_actions_follow_permissions(): void
    {
        $location = $this->location();
        $balance = $this->stock($location, $this->material(), 10);
        $create = route('warehouse/inventory/stock-balances/create', $location->id);
        $editLocation = route('warehouse/inventory/edit', $location->id);
        $editStock = route('warehouse/inventory/stock-balances/edit', [$location->id, $balance->id]);
        $deleteStock = route('warehouse/inventory/stock-balances/destroy', [$location->id, $balance->id]);

        $this->actingAs($this->admin(['read warehouse/inventory']))
            ->get(route('warehouse/inventory/detail', $location->id))
            ->assertOk()
            ->assertDontSee($create)
            ->assertDontSee($editLocation)
            ->assertDontSee($editStock)
            ->assertDontSee($deleteStock);

        $this->actingAs($this->admin(['read warehouse/inventory', 'create warehouse/inventory', 'update warehouse/inventory', 'delete warehouse/inventory']))
            ->get(route('warehouse/inventory/detail', $location->id))
            ->assertOk()
            ->assertSee($create)
            ->assertSee($editLocation)
            ->assertSee($editStock)
            ->assertSee($deleteStock);
    }

    public function test_create_stock_modal_lists_only_active_materials_not_yet_in_location(): void
    {
        $location = $this->location(['name' => 'Uji Lokasi & Rak ' . uniqid()]);
        $used = $this->material(['name' => 'Uji Sudah Ada ' . uniqid()]);
        $this->stock($location, $used, 1);
        $inactive = $this->material(['name' => 'Uji Nonaktif ' . uniqid(), 'is_active' => false]);
        $free = $this->material(['name' => 'Uji Bisa Dipilih ' . uniqid()]);

        $this->actingAs($this->admin(['create warehouse/inventory']))
            ->get(route('warehouse/inventory/stock-balances/create', $location->id))
            ->assertOk()
            ->assertSee('ob-modal', false)
            // Judul memakai :title, jadi & tidak ter-escape dua kali
            ->assertSee('Tambah Bahan Baku ke ' . e($location->name), false)
            ->assertDontSee('&amp;amp;', false)
            ->assertSee($free->name)
            ->assertDontSee($used->name)
            ->assertDontSee($inactive->name);
    }

    public function test_edit_stock_modal_keeps_current_inactive_material_and_unit(): void
    {
        $unit = Satuan::create(['name' => 'Uji Liter ' . uniqid(), 'symbol' => 'ujl', 'is_active' => 1]);
        $location = $this->location();
        $material = $this->material(['name' => 'Uji Edit Nonaktif ' . uniqid(), 'is_active' => false], $unit);
        $balance = $this->stock($location, $material, 12.5, 2);

        $this->actingAs($this->admin(['update warehouse/inventory']))
            ->get(route('warehouse/inventory/stock-balances/edit', [$location->id, $balance->id]))
            ->assertOk()
            ->assertSee('Edit Stok Bahan Baku')
            // Tombol simpan berlabel Update, bukan Add
            ->assertSeeInOrder(['id="addRowButton"', 'Update'], false)
            ->assertSee($material->name)
            ->assertSee('(Nonaktif)')
            ->assertSee('value="12.5"', false)
            ->assertSee('value="2"', false)
            ->assertSee('ujl');
    }

    public function test_store_stock_returns_count_stats(): void
    {
        $location = $this->location();
        $this->stock($location, $this->material(), 0);

        $this->actingAs($this->admin(['create warehouse/inventory']))
            ->postJson(route('warehouse/inventory/stock-balances/store', $location->id), [
                'raw_material_id' => $this->material()->id,
                'qty_available' => 10,
                'qty_reserved' => 3,
            ])
            ->assertOk()
            ->assertJsonPath('data.total_materials', 2)
            ->assertJsonPath('data.empty_materials', 1)
            ->assertJsonPath('data.reserved_materials', 1);
    }
}

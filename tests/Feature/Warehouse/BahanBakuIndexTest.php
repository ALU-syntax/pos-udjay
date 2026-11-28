<?php

namespace Tests\Feature\Warehouse;

use App\Enums\ProcurementMode;
use App\Models\Inventory;
use App\Models\InventoryRawMaterialStockBalance;
use App\Models\InventoryType;
use App\Models\RawMaterialCategories;
use App\Models\RawMaterials;
use App\Models\RawMaterialUnitConversions;
use App\Models\RawStorageType;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\Supplier;
use App\Models\SupplierRawMaterials;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BahanBakuIndexTest extends TestCase
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
        ], $attributes));
    }

    private function unit(array $attributes = []): Satuan
    {
        return Satuan::create(array_merge(['name' => 'Uji Satuan ' . uniqid(), 'is_active' => 1], $attributes));
    }

    private function storage(): RawStorageType
    {
        return RawStorageType::query()->forceCreate(['name' => 'Uji Simpan ' . uniqid()]);
    }

    private function rawMaterial(array $attributes = []): RawMaterials
    {
        return RawMaterials::query()->forceCreate(array_merge([
            'name' => 'Uji Bahan ' . uniqid(),
            'raw_material_category_id' => null,
            'base_unit_id' => $attributes['base_unit_id'] ?? $this->unit()->id,
            'storage_type_id' => $attributes['storage_type_id'] ?? $this->storage()->id,
            'is_active' => true,
        ], $attributes));
    }

    private function rows(User $actor, array $params = []): Collection
    {
        // Yajra menyimpan request sebagai singleton; dilupakan agar tiap panggilan membaca parameternya sendiri
        app()->forgetInstance('datatables.request');

        // Kolom dikirim seperti browser, karena pencarian global hanya berlaku pada kolom yang searchable
        $columns = collect(['name', 'category_name', 'base_unit_name', 'storage_type_name', 'is_active', 'updated_at', 'action'])
            ->map(fn ($column) => [
                'data' => $column,
                'name' => $column,
                'searchable' => $column === 'name' ? 'true' : 'false',
                'orderable' => 'false',
                'search' => ['value' => '', 'regex' => 'false'],
            ])
            ->all();

        return collect($this->actingAs($actor)->getJson(route('library/bahan-baku', array_merge([
            'draw' => 1, 'start' => 0, 'length' => 1000, 'columns' => $columns,
        ], $params)), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'))
            ->keyBy(fn ($row) => (int) $row['DT_RowId']);
    }

    public function test_index_page_renders_report_style(): void
    {
        $this->actingAs($this->admin(['create library/bahan-baku']))
            ->get(route('library/bahan-baku'))
            ->assertOk()
            ->assertSee('ob-page', false)
            ->assertSee('Daftar Bahan Baku')
            ->assertSee('filter-category')
            ->assertSee('filter-storage')
            ->assertSee('filter-status')
            ->assertSee(route('library/bahan-baku/create'));
    }

    public function test_add_button_is_hidden_without_create_permission(): void
    {
        $this->actingAs($this->admin())
            ->get(route('library/bahan-baku'))
            ->assertOk()
            ->assertDontSee(route('library/bahan-baku/create'));
    }

    public function test_rows_escape_name_and_show_code_notes_and_relations(): void
    {
        $category = $this->category(['name' => 'Uji Kat Baris ' . uniqid()]);
        $unit = $this->unit(['symbol' => 'ujg']);
        $material = $this->rawMaterial([
            'name' => '<b>Uji Escape</b>',
            'code' => 'UJ-ROW-1',
            'notes' => 'Simpan kering',
            'raw_material_category_id' => $category->id,
            'base_unit_id' => $unit->id,
            'is_active' => false,
        ]);
        $plain = $this->rawMaterial();

        $rows = $this->rows($this->admin());

        $row = $rows[$material->id];
        $this->assertStringContainsString('&lt;b&gt;Uji Escape&lt;/b&gt;', $row['name']);
        $this->assertStringContainsString('UJ-ROW-1', $row['name']);
        $this->assertStringContainsString('Simpan kering', $row['name']);
        $this->assertStringContainsString($category->name, $row['category_name']);
        $this->assertStringContainsString('ujg', $row['base_unit_name']);
        $this->assertStringContainsString($material->storageType->name, $row['storage_type_name']);
        $this->assertStringContainsString('Nonaktif', $row['is_active']);

        $this->assertStringContainsString('Tanpa kode', $rows[$plain->id]['name']);
        $this->assertStringContainsString('Tanpa kategori', $rows[$plain->id]['category_name']);
        $this->assertStringContainsString('Aktif', $rows[$plain->id]['is_active']);
    }

    public function test_filters_limit_rows_and_can_be_combined(): void
    {
        $admin = $this->admin();
        $category = $this->category();
        $storage = $this->storage();
        $inCategory = $this->rawMaterial(['raw_material_category_id' => $category->id, 'storage_type_id' => $storage->id]);
        $inCategoryInactive = $this->rawMaterial(['raw_material_category_id' => $category->id, 'is_active' => false]);
        $uncategorized = $this->rawMaterial(['storage_type_id' => $storage->id]);

        $byCategory = $this->rows($admin, ['category' => (string) $category->id]);
        $this->assertTrue($byCategory->has($inCategory->id));
        $this->assertTrue($byCategory->has($inCategoryInactive->id));
        $this->assertFalse($byCategory->has($uncategorized->id));

        $none = $this->rows($admin, ['category' => 'none']);
        $this->assertTrue($none->has($uncategorized->id));
        $this->assertFalse($none->has($inCategory->id));

        $byStorage = $this->rows($admin, ['storage' => (string) $storage->id]);
        $this->assertTrue($byStorage->has($inCategory->id));
        $this->assertTrue($byStorage->has($uncategorized->id));
        $this->assertFalse($byStorage->has($inCategoryInactive->id));

        $activeInCategory = $this->rows($admin, ['category' => (string) $category->id, 'status' => '1']);
        $this->assertTrue($activeInCategory->has($inCategory->id));
        $this->assertFalse($activeInCategory->has($inCategoryInactive->id));
    }

    public function test_category_filter_matches_exact_category(): void
    {
        // Dulu filter mencocokkan potongan nama: "Sayur" ikut memunculkan "Sayuran"
        $short = $this->category(['name' => 'Ujsayur ' . uniqid()]);
        $long = $this->category(['name' => $short->name . 'an']);
        $inShort = $this->rawMaterial(['raw_material_category_id' => $short->id]);
        $inLong = $this->rawMaterial(['raw_material_category_id' => $long->id]);

        $rows = $this->rows($this->admin(), ['category' => (string) $short->id]);

        $this->assertTrue($rows->has($inShort->id));
        $this->assertFalse($rows->has($inLong->id));
    }

    public function test_search_matches_code_and_notes(): void
    {
        $admin = $this->admin();
        $byCode = $this->rawMaterial(['code' => 'UJCARI-' . uniqid()]);
        $byNotes = $this->rawMaterial(['notes' => 'Ujcari chiller dua derajat']);
        $other = $this->rawMaterial(['notes' => 'Rak kering']);

        $codeRows = $this->rows($admin, ['search' => ['value' => $byCode->code, 'regex' => 'false']]);
        $this->assertTrue($codeRows->has($byCode->id));
        $this->assertFalse($codeRows->has($other->id));

        $notesRows = $this->rows($admin, ['search' => ['value' => 'Ujcari chiller', 'regex' => 'false']]);
        $this->assertTrue($notesRows->has($byNotes->id));
        $this->assertFalse($notesRows->has($other->id));
    }

    public function test_row_actions_follow_permissions(): void
    {
        $material = $this->rawMaterial();
        $detail = route('library/bahan-baku/detail', $material->id);
        $edit = route('library/bahan-baku/edit', $material->id);
        $destroy = route('library/bahan-baku/destroy', $material->id);

        $readOnly = $this->rows($this->admin(['read library/bahan-baku']))[$material->id]['action'];
        $this->assertStringContainsString($detail, $readOnly);
        $this->assertStringNotContainsString($edit, $readOnly);
        $this->assertStringNotContainsString($destroy, $readOnly);

        $editor = $this->rows($this->admin(['update library/bahan-baku', 'delete library/bahan-baku']))[$material->id]['action'];
        $this->assertStringNotContainsString($detail, $editor);
        $this->assertStringContainsString($edit, $editor);
        $this->assertStringContainsString($destroy, $editor);
    }

    public function test_store_returns_updated_stats(): void
    {
        $before = RawMaterials::count();

        $this->actingAs($this->admin(['create library/bahan-baku']))
            ->postJson(route('library/bahan-baku/store'), [
                'name' => 'Uji Bahan Baru ' . uniqid(),
                'base_unit_id' => $this->unit()->id,
                'storage_type_id' => $this->storage()->id,
                'is_active' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('data.total', $before + 1);
    }

    public function test_create_modal_hides_inactive_category_and_unit(): void
    {
        $activeCategory = $this->category(['name' => 'Uji Kat Aktif ' . uniqid()]);
        $inactiveCategory = $this->category(['name' => 'Uji Kat Mati ' . uniqid(), 'is_active' => false]);
        $activeUnit = $this->unit(['name' => 'Uji Sat Aktif ' . uniqid()]);
        $inactiveUnit = $this->unit(['name' => 'Uji Sat Mati ' . uniqid(), 'is_active' => 0]);

        $this->actingAs($this->admin(['create library/bahan-baku']))
            ->get(route('library/bahan-baku/create'))
            ->assertOk()
            ->assertSee('Tambah Bahan Baku')
            ->assertSee('ob-modal', false)
            ->assertSee($activeCategory->name)
            ->assertDontSee($inactiveCategory->name)
            ->assertSee($activeUnit->name)
            ->assertDontSee($inactiveUnit->name);
    }

    public function test_edit_modal_keeps_saved_inactive_choices_and_values(): void
    {
        $inactiveCategory = $this->category(['name' => 'Uji Kat Simpan ' . uniqid(), 'is_active' => false]);
        $otherInactiveCategory = $this->category(['name' => 'Uji Kat Lain ' . uniqid(), 'is_active' => false]);
        $inactiveUnit = $this->unit(['name' => 'Uji Sat Simpan ' . uniqid(), 'is_active' => 0]);
        $material = $this->rawMaterial([
            'name' => 'Uji Modal ' . uniqid(),
            'code' => 'UJ-MDL-1',
            'notes' => 'Catatan modal uji',
            'raw_material_category_id' => $inactiveCategory->id,
            'base_unit_id' => $inactiveUnit->id,
            'is_active' => false,
        ]);

        $this->actingAs($this->admin(['update library/bahan-baku']))
            ->get(route('library/bahan-baku/edit', $material->id))
            ->assertOk()
            ->assertSee('Edit Bahan Baku')
            ->assertSee('value="' . $material->name . '"', false)
            ->assertSee('value="UJ-MDL-1"', false)
            ->assertSee('Catatan modal uji')
            ->assertSee($inactiveCategory->name . ' (Nonaktif)')
            ->assertDontSee($otherInactiveCategory->name)
            ->assertSee($inactiveUnit->name . ' (Nonaktif)')
            ->assertSee('<option value="0" selected>Nonaktif</option>', false);
    }

    public function test_store_rejects_inactive_category_and_unit(): void
    {
        $inactiveCategory = $this->category(['is_active' => false]);
        $inactiveUnit = $this->unit(['is_active' => 0]);
        $name = 'Uji Bahan Ditolak ' . uniqid();

        $this->actingAs($this->admin(['create library/bahan-baku']))
            ->postJson(route('library/bahan-baku/store'), [
                'name' => $name,
                'raw_material_category_id' => $inactiveCategory->id,
                'base_unit_id' => $inactiveUnit->id,
                'storage_type_id' => $this->storage()->id,
                'is_active' => 1,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'raw_material_category_id' => 'Kategori tidak ditemukan atau sudah nonaktif.',
                'base_unit_id' => 'Satuan dasar tidak ditemukan atau sudah nonaktif.',
            ]);

        $this->assertFalse(RawMaterials::where('name', $name)->exists());
    }

    public function test_update_keeps_saved_inactive_choice_but_rejects_other_inactive(): void
    {
        $savedCategory = $this->category(['is_active' => false]);
        $savedUnit = $this->unit(['is_active' => 0]);
        $otherInactiveUnit = $this->unit(['is_active' => 0]);
        $material = $this->rawMaterial(['raw_material_category_id' => $savedCategory->id, 'base_unit_id' => $savedUnit->id]);
        $admin = $this->admin(['update library/bahan-baku']);
        $payload = [
            'name' => 'Uji Bahan Ubah ' . uniqid(),
            'raw_material_category_id' => $savedCategory->id,
            'base_unit_id' => $savedUnit->id,
            'storage_type_id' => $material->storage_type_id,
            'is_active' => 1,
        ];

        // Nilai tersimpan boleh tetap dipakai walau sudah nonaktif
        $this->actingAs($admin)
            ->putJson(route('library/bahan-baku/update', $material->id), $payload)
            ->assertOk();
        $this->assertSame($payload['name'], $material->fresh()->name);

        $this->actingAs($admin)
            ->putJson(route('library/bahan-baku/update', $material->id), array_merge($payload, ['base_unit_id' => $otherInactiveUnit->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('base_unit_id');
        $this->assertSame($savedUnit->id, $material->fresh()->base_unit_id);
    }

    private function stock(RawMaterials $material, array $inventory, float $available, float $reserved = 0): Inventory
    {
        $type = InventoryType::query()->forceCreate(['name' => 'Uji Tipe ' . uniqid(), 'is_active' => true]);
        $location = Inventory::query()->forceCreate(array_merge([
            'name' => 'Uji Lokasi ' . uniqid(),
            'inventory_type_id' => $type->id,
            'is_active' => true,
        ], $inventory));

        InventoryRawMaterialStockBalance::query()->forceCreate([
            'raw_material_id' => $material->id,
            'inventory_id' => $location->id,
            'qty_available' => $available,
            'qty_reserved' => $reserved,
        ]);

        return $location;
    }

    private function supplierMaterial(RawMaterials $material, array $supplier = [], array $attributes = []): SupplierRawMaterials
    {
        $supplier = Supplier::create(array_merge([
            'name' => 'Uji Supplier ' . uniqid(),
            'procurement_mode' => ProcurementMode::OFFLINE->value,
            'is_active' => true,
        ], $supplier));

        return SupplierRawMaterials::query()->forceCreate(array_merge([
            'supplier_id' => $supplier->id,
            'raw_material_id' => $material->id,
            'purchase_unit_id' => $this->unit(['name' => 'Uji Karung ' . uniqid()])->id,
            'minimum_order_qty' => 0,
            'lead_time_days' => 2,
            'current_price' => null,
            'is_preferred' => false,
            'is_active' => true,
        ], $attributes));
    }

    public function test_detail_modal_shows_stock_suppliers_and_conversions(): void
    {
        $gram = $this->unit(['name' => 'Uji Gram ' . uniqid(), 'symbol' => 'ujgr']);
        $sack = $this->unit(['name' => 'Uji Sak ' . uniqid(), 'symbol' => 'ujsak']);
        $material = $this->rawMaterial(['name' => '<i>Uji Detail</i>', 'code' => 'UJ-DET-1', 'notes' => 'Catatan detail uji', 'base_unit_id' => $gram->id]);

        $main = $this->stock($material, ['name' => 'Uji Gudang Utama', 'code' => 'GD-UJ'], 25000, 1500.5);
        $closed = $this->stock($material, ['name' => 'Uji Gudang Tutup', 'is_active' => false], 100);

        $preferred = $this->supplierMaterial($material, ['name' => '<b>Uji Supplier Utama</b>'], [
            'supplier_material_name' => 'Terigu Cakra 25kg',
            'supplier_sku' => 'CK-25',
            'current_price' => 285000,
            'lead_time_days' => 3,
            'is_preferred' => true,
        ]);
        $inactiveSupplier = $this->supplierMaterial($material, ['name' => 'Uji Supplier Mati ' . uniqid(), 'is_active' => false]);
        $trashedSupplier = $this->supplierMaterial($material, ['name' => 'Uji Supplier Hapus ' . uniqid()]);
        $trashedSupplier->supplier->delete();
        $trashedLink = $this->supplierMaterial($material, ['name' => 'Uji Link Hapus ' . uniqid()]);
        $trashedLink->delete();

        RawMaterialUnitConversions::query()->forceCreate([
            'raw_material_id' => $material->id,
            'from_unit_id' => $sack->id,
            'to_unit_id' => $gram->id,
            'multiplier' => 25000,
            'notes' => 'Isi bersih per sak',
        ]);

        $response = $this->actingAs($this->admin(['read library/bahan-baku', 'read warehouse/supplier']))
            ->get(route('library/bahan-baku/detail', $material->id))
            ->assertOk()
            ->assertSee('ob-modal', false)
            ->assertSee('&lt;i&gt;Uji Detail&lt;/i&gt;', false)
            // Judul modal tidak boleh ter-escape dua kali
            ->assertDontSee('&amp;lt;i&amp;gt;', false)
            ->assertSee('UJ-DET-1')
            ->assertSee('Catatan detail uji')
            // Total stok semua lokasi, dalam satuan dasar
            ->assertSee('25.100')
            ->assertSee('1.500,5')
            ->assertSee('Uji Gudang Utama')
            ->assertSee('GD-UJ')
            ->assertSee('Lokasi nonaktif')
            // Supplier: nama di-escape, alias, harga, lead time, utama, nonaktif
            ->assertSee('&lt;b&gt;Uji Supplier Utama&lt;/b&gt;', false)
            ->assertSee('Di supplier: Terigu Cakra 25kg · SKU CK-25')
            ->assertSee('Rp 285.000')
            ->assertSee('3 hari')
            ->assertSee('Utama')
            ->assertSee($inactiveSupplier->supplier->name)
            ->assertSee('Supplier nonaktif')
            ->assertSee(route('warehouse/supplier/show', $preferred->supplier_id))
            // Supplier atau tautan yang sudah dihapus tidak tampil
            ->assertDontSee($trashedSupplier->supplier->name)
            ->assertDontSee($trashedLink->supplier->name)
            // Konversi
            ->assertSee('1 ujsak = 25.000 ujgr')
            ->assertSee('Isi bersih per sak');

        $this->assertNotNull($closed);
    }

    public function test_detail_modal_hides_supplier_link_without_permission(): void
    {
        $material = $this->rawMaterial();
        $item = $this->supplierMaterial($material, ['name' => 'Uji Supplier Tanpa Izin ' . uniqid()]);

        $this->actingAs($this->admin(['read library/bahan-baku']))
            ->get(route('library/bahan-baku/detail', $material->id))
            ->assertOk()
            ->assertSee($item->supplier->name)
            ->assertDontSee(route('warehouse/supplier/show', $item->supplier_id));
    }

    public function test_detail_modal_shows_empty_states(): void
    {
        $material = $this->rawMaterial();

        $this->actingAs($this->admin(['read library/bahan-baku']))
            ->get(route('library/bahan-baku/detail', $material->id))
            ->assertOk()
            ->assertSee('Belum ada stok')
            ->assertSee('Belum ada supplier')
            ->assertSee('Belum ada konversi')
            ->assertSee('Tanpa kategori')
            ->assertDontSee('<table class="wh-table">', false);
    }
}

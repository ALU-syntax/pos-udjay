<?php

namespace Tests\Feature\Warehouse;

use App\Enums\ProcurementMode;
use App\Models\RawMaterialCategories;
use App\Models\RawMaterials;
use App\Models\RawStorageType;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\Supplier;
use App\Models\SupplierRawMaterialPriceHistories;
use App\Models\SupplierRawMaterials;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SupplierDetailTest extends TestCase
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

    private function supplier(array $attributes = []): Supplier
    {
        return Supplier::create(array_merge([
            'name' => 'Uji Supplier ' . uniqid(),
            'procurement_mode' => ProcurementMode::OFFLINE->value,
            'is_active' => true,
        ], $attributes));
    }

    private function unit(string $name): Satuan
    {
        return Satuan::query()->forceCreate(['name' => $name . ' ' . uniqid(), 'is_active' => true]);
    }

    private function rawMaterial(string $name, bool $active = true): RawMaterials
    {
        $category = RawMaterialCategories::query()->forceCreate(['name' => 'Uji Kategori ' . uniqid(), 'is_active' => true]);
        $storage = RawStorageType::query()->forceCreate(['name' => 'Uji Simpan ' . uniqid()]);

        return RawMaterials::query()->forceCreate([
            'name' => $name,
            'raw_material_category_id' => $category->id,
            'base_unit_id' => $this->unit('gram')->id,
            'storage_type_id' => $storage->id,
            'is_active' => $active,
        ]);
    }

    private function supplierMaterial(Supplier $supplier, RawMaterials $material, array $attributes = []): SupplierRawMaterials
    {
        return SupplierRawMaterials::query()->forceCreate(array_merge([
            'supplier_id' => $supplier->id,
            'raw_material_id' => $material->id,
            'purchase_unit_id' => $this->unit('karung')->id,
            'minimum_order_qty' => 0,
            'lead_time_days' => 2,
            'current_price' => null,
            'is_preferred' => false,
            'is_active' => true,
        ], $attributes));
    }

    private function history(SupplierRawMaterials $item, float $price, string $date, User $by): void
    {
        SupplierRawMaterialPriceHistories::query()->forceCreate([
            'supplier_raw_material_id' => $item->id,
            'price' => $price,
            'effective_from' => $date,
            'updated_by' => $by->id,
            'tax_type' => 'non_tax',
        ]);
    }

    public function test_detail_shows_materials_stats_and_price_trend(): void
    {
        $admin = $this->admin(['update warehouse/supplier']);
        $supplier = $this->supplier(['name' => 'Uji <b>Detail</b>', 'code' => 'UJI-DTL-1']);
        $item = $this->supplierMaterial($supplier, $this->rawMaterial('Uji Tepung Detail'), [
            'supplier_material_name' => 'Tepung Cakra 25kg',
            'supplier_sku' => 'SKU-77',
            'minimum_order_qty' => 2.5,
            'current_price' => 220000,
            'is_preferred' => true,
        ]);
        $this->history($item, 200000, '2026-11-01', $admin);
        $this->history($item, 220000, '2026-11-20', $admin);

        $this->actingAs($admin)
            ->get(route('warehouse/supplier/show', $supplier->id))
            ->assertOk()
            ->assertSee('Uji &lt;b&gt;Detail&lt;/b&gt;', false)
            ->assertSee('UJI-DTL-1')
            ->assertSee('Uji Tepung Detail')
            ->assertSee('Di supplier: Tepung Cakra 25kg · SKU SKU-77')
            ->assertSee('Utama')
            ->assertSee('2,5')
            ->assertSee('Rp 220.000')
            ->assertSee('Harga awal')
            // Naik Rp 20.000 dari Rp 200.000 = 10%
            ->assertSee('Rp 20.000 · 10,0%')
            ->assertSee('Terakhir 20 Nov 2026')
            ->assertSee('Belum ada kontak yang tercatat.')
            ->assertSee(route('warehouse/supplier/raw-materials/create', $supplier->id))
            ->assertSee(route('warehouse/supplier/raw-materials/destroy', [$supplier->id, $item->id]));
    }

    public function test_actions_are_hidden_without_update_permission(): void
    {
        $supplier = $this->supplier();
        $item = $this->supplierMaterial($supplier, $this->rawMaterial('Uji Gula Tanpa Izin'));

        $this->actingAs($this->admin(['read warehouse/supplier']))
            ->get(route('warehouse/supplier/show', $supplier->id))
            ->assertOk()
            ->assertSee('Uji Gula Tanpa Izin')
            ->assertDontSee(route('warehouse/supplier/edit', $supplier->id))
            ->assertDontSee(route('warehouse/supplier/raw-materials/create', $supplier->id))
            ->assertDontSee(route('warehouse/supplier/raw-materials/edit', [$supplier->id, $item->id]));
    }

    public function test_raw_material_of_other_supplier_returns_not_found(): void
    {
        $admin = $this->admin(['update warehouse/supplier']);
        $owner = $this->supplier();
        $other = $this->supplier();
        $item = $this->supplierMaterial($owner, $this->rawMaterial('Uji Milik Lain'));

        $this->actingAs($admin)
            ->get(route('warehouse/supplier/raw-materials/edit', [$other->id, $item->id]))
            ->assertNotFound();

        $this->actingAs($admin)
            ->putJson(route('warehouse/supplier/raw-materials/update', [$other->id, $item->id]), [
                'raw_material_id' => $item->raw_material_id,
                'purchase_unit_id' => $item->purchase_unit_id,
                'lead_time_days' => 1,
                'is_preferred' => 0,
                'is_active' => 1,
            ])
            ->assertNotFound();

        $this->actingAs($admin)
            ->deleteJson(route('warehouse/supplier/raw-materials/destroy', [$other->id, $item->id]))
            ->assertNotFound();

        $this->assertNotSoftDeleted($item);

        $this->actingAs($admin)
            ->deleteJson(route('warehouse/supplier/raw-materials/destroy', [$owner->id, $item->id]))
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertSoftDeleted($item);
    }

    public function test_price_change_records_history_in_indonesian(): void
    {
        $admin = $this->admin(['update warehouse/supplier']);
        $supplier = $this->supplier();
        $item = $this->supplierMaterial($supplier, $this->rawMaterial('Uji Mentega Harga'), ['current_price' => 50000]);

        $this->actingAs($admin)
            ->putJson(route('warehouse/supplier/raw-materials/update', [$supplier->id, $item->id]), [
                'raw_material_id' => $item->raw_material_id,
                'purchase_unit_id' => $item->purchase_unit_id,
                'lead_time_days' => 2,
                'current_price' => 45000,
                'is_preferred' => 0,
                'is_active' => 1,
            ])
            ->assertOk();

        $this->assertDatabaseHas('supplier_raw_material_price_histories', [
            'supplier_raw_material_id' => $item->id,
            'price' => 45000,
            'updated_by' => $admin->id,
            'notes' => 'Harga sebelumnya Rp 50.000,00',
        ]);
    }

    public function test_edit_modal_keeps_inactive_raw_material_selected(): void
    {
        $supplier = $this->supplier();
        $inactive = $this->rawMaterial('Uji Bahan Nonaktif', false);
        $item = $this->supplierMaterial($supplier, $inactive, ['lead_time_days' => 3]);

        $this->actingAs($this->admin(['update warehouse/supplier']))
            ->get(route('warehouse/supplier/raw-materials/edit', [$supplier->id, $item->id]))
            ->assertOk()
            ->assertSee('Edit Bahan Baku Supplier')
            ->assertSee('<option value="' . $inactive->id . '" selected>', false)
            ->assertSee('Uji Bahan Nonaktif')
            ->assertSee('— nonaktif');
    }
}

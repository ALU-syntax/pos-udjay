<?php

namespace Tests\Feature\Warehouse;

use App\Enums\ProcurementMode;
use App\Models\RawMaterialCategories;
use App\Models\RawMaterialUnitConversions;
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

    private function conversion(RawMaterials $material, int $fromUnitId, int $toUnitId, float $multiplier): void
    {
        RawMaterialUnitConversions::create([
            'raw_material_id' => $material->id,
            'from_unit_id' => $fromUnitId,
            'to_unit_id' => $toUnitId,
            'multiplier' => $multiplier,
        ]);
    }

    private function storeMaterial(Supplier $supplier, RawMaterials $material, int $purchaseUnitId)
    {
        return $this->actingAs($this->admin(['update warehouse/supplier']))
            ->postJson(route('warehouse/supplier/raw-materials/store', $supplier->id), [
                'raw_material_id' => $material->id,
                'purchase_unit_id' => $purchaseUnitId,
                'lead_time_days' => 1,
                'current_price' => 285000,
                'is_preferred' => 1,
                'is_active' => 1,
            ]);
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
        $material = $this->rawMaterial('Uji Mentega Harga');
        $item = $this->supplierMaterial($supplier, $material, ['current_price' => 50000]);
        $this->conversion($material, $item->purchase_unit_id, $material->base_unit_id, 25000);

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

    public function test_purchase_unit_without_conversion_to_base_is_rejected(): void
    {
        $material = $this->rawMaterial('Uji Tepung Konversi');
        $sack = $this->unit('Karung');
        $supplier = $this->supplier();

        $this->storeMaterial($supplier, $material, $sack->id)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['purchase_unit_id'])
            ->assertJsonPath('errors.purchase_unit_id.0', fn ($message) => str_contains($message, $sack->name . ' belum punya konversi ke')
                && str_contains($message, 'Uji Tepung Konversi'));

        $this->assertDatabaseMissing('supplier_raw_materials', ['supplier_id' => $supplier->id]);
    }

    public function test_purchase_unit_accepted_with_conversion_in_either_direction_or_as_base_unit(): void
    {
        // Konversi satuan beli → dasar (1 karung = 25.000 g)
        $flour = $this->rawMaterial('Uji Tepung Langsung');
        $sack = $this->unit('Karung');
        $this->conversion($flour, $sack->id, $flour->base_unit_id, 25000);
        $this->storeMaterial($this->supplier(), $flour, $sack->id)->assertOk();

        // Konversi kebalikannya (1 g = 0,001 kg)
        $sugar = $this->rawMaterial('Uji Gula Kebalikan');
        $kilo = $this->unit('Kilogram');
        $this->conversion($sugar, $sugar->base_unit_id, $kilo->id, 0.001);
        $this->storeMaterial($this->supplier(), $sugar, $kilo->id)->assertOk();

        // Satuan beli = satuan dasar
        $salt = $this->rawMaterial('Uji Garam Dasar');
        $this->storeMaterial($this->supplier(), $salt, $salt->base_unit_id)->assertOk();

        $this->assertSame(1000.0, $sugar->conversionToBase($kilo->id));
        $this->assertSame(25000.0, $flour->conversionToBase($sack->id));
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

    public function test_create_modal_hides_inactive_purchase_unit(): void
    {
        $supplier = $this->supplier();
        $active = $this->unit('Uji Sat Aktif');
        $inactive = $this->unit('Uji Sat Mati');
        $inactive->update(['is_active' => 0]);

        $this->actingAs($this->admin(['update warehouse/supplier']))
            ->get(route('warehouse/supplier/raw-materials/create', $supplier->id))
            ->assertOk()
            ->assertSee($active->name)
            ->assertDontSee($inactive->name);
    }

    public function test_edit_modal_keeps_saved_inactive_purchase_unit_marked(): void
    {
        $supplier = $this->supplier();
        $sack = $this->unit('Uji Karung Lama');
        $other = $this->unit('Uji Sat Lain');
        $item = $this->supplierMaterial($supplier, $this->rawMaterial('Uji Tepung ' . uniqid()), ['purchase_unit_id' => $sack->id]);
        $sack->update(['is_active' => 0]);
        $other->update(['is_active' => 0]);

        $this->actingAs($this->admin(['update warehouse/supplier']))
            ->get(route('warehouse/supplier/raw-materials/edit', [$supplier->id, $item->id]))
            ->assertOk()
            ->assertSee($sack->name . ' — nonaktif')
            ->assertDontSee($other->name);
    }

    public function test_store_rejects_inactive_purchase_unit(): void
    {
        $material = $this->rawMaterial('Uji Tepung ' . uniqid());
        $sack = $this->unit('Uji Karung Mati');
        $this->conversion($material, $sack->id, $material->base_unit_id, 25000);
        $sack->update(['is_active' => 0]);
        $supplier = $this->supplier();

        $this->storeMaterial($supplier, $material, $sack->id)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['purchase_unit_id' => 'Satuan beli tidak ditemukan atau sudah nonaktif.']);

        $this->assertDatabaseMissing('supplier_raw_materials', ['supplier_id' => $supplier->id]);
    }

    public function test_update_keeps_saved_inactive_purchase_unit_but_rejects_other_inactive(): void
    {
        $supplier = $this->supplier();
        $material = $this->rawMaterial('Uji Tepung ' . uniqid());
        $sack = $this->unit('Uji Karung Lama');
        $other = $this->unit('Uji Sat Lain');
        $this->conversion($material, $sack->id, $material->base_unit_id, 25000);
        $this->conversion($material, $other->id, $material->base_unit_id, 1000);
        $item = $this->supplierMaterial($supplier, $material, ['purchase_unit_id' => $sack->id]);
        $sack->update(['is_active' => 0]);
        $other->update(['is_active' => 0]);
        $admin = $this->admin(['update warehouse/supplier']);
        $payload = [
            'raw_material_id' => $material->id,
            'purchase_unit_id' => $sack->id,
            'lead_time_days' => 5,
            'is_preferred' => 0,
            'is_active' => 1,
        ];
        $url = route('warehouse/supplier/raw-materials/update', [$supplier->id, $item->id]);

        $this->actingAs($admin)->putJson($url, $payload)->assertOk();
        $this->assertSame(5, (int) $item->fresh()->lead_time_days);

        $this->actingAs($admin)
            ->putJson($url, array_merge($payload, ['purchase_unit_id' => $other->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('purchase_unit_id');
        $this->assertSame($sack->id, $item->fresh()->purchase_unit_id);
    }
}

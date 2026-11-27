<?php

namespace Tests\Feature\Warehouse;

use App\Enums\ProcurementMode;
use App\Models\Inventory;
use App\Models\InventoryType;
use App\Models\ProcurementPlans;
use App\Models\RawMaterialRequestItems;
use App\Models\RawMaterialRequestStatus;
use App\Models\RawMaterialRequests;
use App\Models\RawMaterials;
use App\Models\RawStorageType;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\Supplier;
use App\Models\SupplierRawMaterials;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProcurementPlanFormTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'Uji Admin Warehouse', 'guard_name' => 'web']);
        $admin = User::factory()->create(['role' => $role->id]);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'create warehouse/procurement-plan', 'guard_name' => 'web']));

        return $admin;
    }

    private function location(string $name = null): Inventory
    {
        $type = InventoryType::create(['name' => 'Uji Tipe ' . uniqid(), 'is_active' => true]);

        return Inventory::create(['name' => $name ?? 'Uji Lokasi ' . uniqid(), 'inventory_type_id' => $type->id, 'is_active' => true]);
    }

    private function material(string $name, Satuan $unit): RawMaterials
    {
        return RawMaterials::query()->forceCreate([
            'name' => $name,
            'code' => 'UJ-' . uniqid(),
            'base_unit_id' => $unit->id,
            'storage_type_id' => RawStorageType::query()->forceCreate(['name' => 'Uji Simpan ' . uniqid()])->id,
            'is_active' => true,
        ]);
    }

    /** Item request order yang sudah disetujui, siap direncanakan. */
    private function approvedItem(RawMaterials $material, Satuan $unit, float $qty, Inventory $requester, string $number): RawMaterialRequestItems
    {
        $requestOrder = RawMaterialRequests::create([
            'request_number' => $number,
            'requester_inventory_id' => $requester->id,
            'status_id' => (int) RawMaterialRequestStatus::where('code', RawMaterialRequestStatus::APPROVED)->value('id'),
            'needed_at' => '2026-12-05',
        ]);

        return RawMaterialRequestItems::create([
            'raw_material_request_id' => $requestOrder->id,
            'raw_material_id' => $material->id,
            'requested_qty' => $qty,
            'requested_satuan_id' => $unit->id,
            'requested_satuan_name' => $unit->symbol,
            'requested_conversion_to_base' => 1,
            'requested_base_qty' => $qty,
            'requested_base_satuan_id' => $unit->id,
            'requested_base_satuan_name' => $unit->symbol,
            'approved_base_qty' => $qty,
            'approved_base_satuan_name' => $unit->symbol,
            'fulfilled_base_qty' => 0,
            'fulfilled_base_satuan_id' => $unit->id,
            'fulfilled_base_satuan_name' => $unit->symbol,
        ]);
    }

    private function supplierFor(RawMaterials $material, string $name, ?float $price, bool $preferred, Satuan $purchaseUnit): SupplierRawMaterials
    {
        $supplier = Supplier::create(['name' => $name, 'procurement_mode' => ProcurementMode::OFFLINE->value, 'is_active' => true]);

        return SupplierRawMaterials::query()->forceCreate([
            'supplier_id' => $supplier->id,
            'raw_material_id' => $material->id,
            'purchase_unit_id' => $purchaseUnit->id,
            'minimum_order_qty' => 0,
            'lead_time_days' => 0,
            'current_price' => $price,
            'is_preferred' => $preferred,
            'is_active' => true,
        ]);
    }

    public function test_create_page_groups_approved_items_per_material_with_units_and_suppliers(): void
    {
        $gram = Satuan::create(['name' => 'Uji Gram', 'symbol' => 'ujg', 'is_active' => 1]);
        $sack = Satuan::create(['name' => 'Uji Karung', 'symbol' => 'ujkrg', 'is_active' => 1]);
        $material = $this->material('Uji Tepung <Protein>', $gram);
        $kitchen = $this->location('Uji Kitchen & Bar');
        $this->approvedItem($material, $gram, 2500, $kitchen, 'UJ-RO-A-' . uniqid());
        $this->approvedItem($material, $gram, 1500, $this->location(), 'UJ-RO-B-' . uniqid());
        $this->supplierFor($material, 'Uji CV Pangan', 285000, true, $sack);
        $noSupplier = $this->material('Uji Ragi Instan', $gram);
        $this->approvedItem($noSupplier, $gram, 100, $kitchen, 'UJ-RO-C-' . uniqid());

        $this->actingAs($this->admin())
            ->get(route('warehouse/procurement-plan/create'))
            ->assertOk()
            ->assertSee('ob-page', false)
            ->assertSee('Item Request Order Disetujui')
            ->assertSee('Uji Tepung &lt;Protein&gt;', false)
            ->assertSee('Uji Kitchen &amp; Bar', false)
            ->assertSee('2 request')
            // Total sisa per bahan selalu disertai satuannya
            ->assertSee('4.000 ujg')
            ->assertSee('Uji CV Pangan · Utama')
            ->assertSee('Rp 285.000')
            ->assertSee('per ujkrg')
            ->assertSee('Belum ada supplier')
            ->assertSee('Simpan & Buat PO', false);
    }

    public function test_store_creates_plan_and_purchase_order_then_redirects_to_purchase_orders(): void
    {
        $gram = Satuan::create(['name' => 'Uji Gram', 'symbol' => 'ujg', 'is_active' => 1]);
        $material = $this->material('Uji Gula', $gram);
        $location = $this->location();
        $item = $this->approvedItem($material, $gram, 3000, $location, 'UJ-RO-S-' . uniqid());
        $supplierMaterial = $this->supplierFor($material, 'Uji Toko Gula', 15000, true, $gram);

        $response = $this->actingAs($this->admin())->post(route('warehouse/procurement-plan/store'), [
            'plan_number' => 'UJ-PP-' . uniqid(),
            'planning_location_id' => $location->id,
            'selected_sources' => [$item->id],
            'supplier_raw_materials' => [$material->id => $supplierMaterial->id],
        ]);

        $plan = ProcurementPlans::latest('id')->firstOrFail();
        $response->assertRedirect(route('warehouse/procurement-plan/purchase-orders', $plan->id));
        $this->assertSame(1, $plan->items()->count());
        $this->assertSame(1, $plan->purchaseOrders()->count());
    }

    public function test_store_without_selection_returns_indonesian_error(): void
    {
        $this->actingAs($this->admin())
            ->from(route('warehouse/procurement-plan/create'))
            ->post(route('warehouse/procurement-plan/store'), ['planning_location_id' => $this->location()->id])
            ->assertRedirect(route('warehouse/procurement-plan/create'))
            ->assertSessionHasErrors(['selected_sources' => 'Pilih minimal satu item request order yang sudah approved.']);
    }
}

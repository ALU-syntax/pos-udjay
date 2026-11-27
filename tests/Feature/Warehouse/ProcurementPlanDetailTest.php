<?php

namespace Tests\Feature\Warehouse;

use App\Enums\ProcurementMode;
use App\Models\Inventory;
use App\Models\InventoryType;
use App\Models\ProcurementPlanStatus;
use App\Models\ProcurementPlans;
use App\Models\PurchaseOrders;
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

class ProcurementPlanDetailTest extends TestCase
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

    private function location(string $name = null): Inventory
    {
        $type = InventoryType::create(['name' => 'Uji Tipe ' . uniqid(), 'is_active' => true]);

        return Inventory::create(['name' => $name ?? 'Uji Lokasi ' . uniqid(), 'inventory_type_id' => $type->id, 'is_active' => true]);
    }

    private function plan(string $status = ProcurementPlanStatus::DRAFT, array $attributes = []): ProcurementPlans
    {
        return ProcurementPlans::create(array_merge([
            'plan_number' => 'UJ-PP-' . uniqid(),
            'planning_location_id' => $this->location()->id,
            'status_id' => (int) ProcurementPlanStatus::where('code', $status)->value('id'),
            'planned_at' => now(),
        ], $attributes));
    }

    /** Item plan beserta satu sumber Request Order. */
    private function item(ProcurementPlans $plan, string $name, float $required, float $available, ?float $price, string $requestNumber): RawMaterialRequests
    {
        $gram = Satuan::create(['name' => 'Uji Gram ' . uniqid(), 'symbol' => 'ujg', 'is_active' => 1]);
        $sack = Satuan::create(['name' => 'Uji Karung ' . uniqid(), 'symbol' => 'ujkrg', 'is_active' => 1]);
        $material = RawMaterials::query()->forceCreate([
            'name' => $name,
            'code' => 'UJ-' . uniqid(),
            'base_unit_id' => $gram->id,
            'storage_type_id' => RawStorageType::query()->forceCreate(['name' => 'Uji Simpan ' . uniqid()])->id,
            'is_active' => true,
        ]);
        $supplier = Supplier::create(['name' => 'Uji Supplier ' . $name, 'procurement_mode' => ProcurementMode::OFFLINE->value, 'is_active' => true]);
        $supplierMaterial = SupplierRawMaterials::query()->forceCreate([
            'supplier_id' => $supplier->id, 'raw_material_id' => $material->id, 'purchase_unit_id' => $sack->id,
            'minimum_order_qty' => 0, 'lead_time_days' => 0, 'current_price' => $price, 'is_preferred' => true, 'is_active' => true,
        ]);
        $requestOrder = RawMaterialRequests::create([
            'request_number' => $requestNumber,
            'requester_inventory_id' => $this->location('Uji Kitchen ' . uniqid())->id,
            'status_id' => (int) RawMaterialRequestStatus::where('code', RawMaterialRequestStatus::APPROVED)->value('id'),
        ]);
        $requestItem = RawMaterialRequestItems::create([
            'raw_material_request_id' => $requestOrder->id, 'raw_material_id' => $material->id,
            'requested_qty' => $required, 'requested_satuan_id' => $gram->id, 'requested_satuan_name' => 'ujg',
            'requested_conversion_to_base' => 1, 'requested_base_qty' => $required, 'requested_base_satuan_id' => $gram->id,
            'requested_base_satuan_name' => 'ujg', 'approved_base_qty' => $required, 'approved_base_satuan_name' => 'ujg',
            'fulfilled_base_qty' => 0, 'fulfilled_base_satuan_id' => $gram->id, 'fulfilled_base_satuan_name' => 'ujg',
        ]);
        $toPurchase = max($required - $available, 0);
        $planItem = $plan->items()->create([
            'raw_material_id' => $material->id,
            'supplier_id' => $supplier->id,
            'supplier_raw_material_id' => $supplierMaterial->id,
            'unit_id' => $gram->id,
            'qty_required_base' => $required,
            'qty_available_base' => $available,
            'qty_shortage_base' => $toPurchase,
            'qty_to_purchase_base' => $toPurchase,
            'estimated_unit_price' => $price,
            'estimated_subtotal' => $price === null ? null : $toPurchase * $price,
        ]);
        $planItem->sources()->create(['raw_material_request_item_id' => $requestItem->id, 'qty_base_allocated' => $required]);

        return $requestOrder;
    }

    public function test_detail_shows_items_with_units_stock_coverage_and_sources(): void
    {
        $plan = $this->plan(ProcurementPlanStatus::CONVERTED_TO_PO, ['notes' => 'Belanja <mingguan>']);
        $requestOrder = $this->item($plan, 'Uji Tepung & Gandum', 2500, 500, 1000, 'UJ-RO-' . uniqid());
        $this->item($plan, 'Uji Gula', 300, 1000, null, 'UJ-RO-' . uniqid());
        PurchaseOrders::create([
            'po_number' => 'UJ-PO-' . uniqid(), 'procurement_plan_id' => $plan->id,
            'supplier_id' => $plan->items()->first()->supplier_id,
            'ordered_by_inventory_id' => $plan->planning_location_id, 'receiving_inventory_id' => $plan->planning_location_id,
        ]);

        $this->actingAs($this->admin(['read warehouse/procurement-plan']))
            ->get(route('warehouse/procurement-plan/detail', $plan->id))
            ->assertOk()
            ->assertSee('ob-page', false)
            ->assertSee('Sudah Jadi PO')
            ->assertSee('Uji Tepung &amp; Gandum', false)
            ->assertSee('Belanja &lt;mingguan&gt;', false)
            // Qty selalu bersatuan; yang dibeli hanya kekurangan
            ->assertSee('2.500 ujg')
            ->assertSee('2.000 ujg')
            ->assertSee('Cukup dari stok')
            ->assertSee('per ujkrg')
            ->assertSee('Rp 2.000.000')
            ->assertSee($requestOrder->request_number)
            ->assertSee(route('warehouse/request-order/detail', $requestOrder->id))
            ->assertSee(route('warehouse/procurement-plan/purchase-orders', $plan->id))
            ->assertSee('Asal Request');
    }

    public function test_delete_button_only_for_draft_with_delete_permission(): void
    {
        $draft = $this->plan();
        $converted = $this->plan(ProcurementPlanStatus::CONVERTED_TO_PO);

        $this->actingAs($this->admin(['read warehouse/procurement-plan', 'delete warehouse/procurement-plan']))
            ->get(route('warehouse/procurement-plan/detail', $draft->id))
            ->assertSee(route('warehouse/procurement-plan/destroy', $draft->id))
            ->assertSee('Belum ada bahan');

        $this->actingAs($this->admin(['read warehouse/procurement-plan', 'delete warehouse/procurement-plan']))
            ->get(route('warehouse/procurement-plan/detail', $converted->id))
            ->assertDontSee(route('warehouse/procurement-plan/destroy', $converted->id));

        // Tanpa izin: tanpa tombol hapus dan tanpa tautan PO
        $this->actingAs($this->admin())
            ->get(route('warehouse/procurement-plan/detail', $draft->id))
            ->assertDontSee(route('warehouse/procurement-plan/destroy', $draft->id))
            ->assertDontSee(route('warehouse/procurement-plan/purchase-orders', $draft->id));
    }
}

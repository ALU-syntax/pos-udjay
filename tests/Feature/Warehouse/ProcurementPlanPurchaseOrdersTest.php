<?php

namespace Tests\Feature\Warehouse;

use App\Enums\ProcurementMode;
use App\Models\Inventory;
use App\Models\InventoryType;
use App\Models\ProcurementPlanStatus;
use App\Models\ProcurementPlans;
use App\Models\PurchaseOrderCancellations;
use App\Models\PurchaseOrderReceiptStatus;
use App\Models\PurchaseOrderReceipts;
use App\Models\PurchaseOrders;
use App\Models\PurchaseOrderStatus;
use App\Models\RawMaterials;
use App\Models\RawStorageType;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProcurementPlanPurchaseOrdersTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name' => 'Uji Admin Warehouse', 'guard_name' => 'web']);
        $admin = User::factory()->create(['role' => $role->id, 'name' => 'Uji Admin Gudang']);
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'read warehouse/procurement-plan', 'guard_name' => 'web']));

        return $admin;
    }

    private function plan(): ProcurementPlans
    {
        $type = InventoryType::create(['name' => 'Uji Tipe ' . uniqid(), 'is_active' => true]);
        $location = Inventory::create(['name' => 'Uji Gudang ' . uniqid(), 'inventory_type_id' => $type->id, 'is_active' => true]);

        return ProcurementPlans::create([
            'plan_number' => 'UJ-PP-' . uniqid(),
            'planning_location_id' => $location->id,
            'status_id' => (int) ProcurementPlanStatus::where('code', ProcurementPlanStatus::CONVERTED_TO_PO)->value('id'),
            'planned_at' => now(),
        ]);
    }

    private function purchaseOrder(ProcurementPlans $plan, string $supplierName, string $status, User $requester): PurchaseOrders
    {
        $supplier = Supplier::create(['name' => $supplierName, 'procurement_mode' => ProcurementMode::OFFLINE->value, 'is_active' => true]);

        return PurchaseOrders::create([
            'po_number' => 'UJ-PO-' . uniqid(),
            'procurement_plan_id' => $plan->id,
            'supplier_id' => $supplier->id,
            'ordered_by_inventory_id' => $plan->planning_location_id,
            'receiving_inventory_id' => $plan->planning_location_id,
            'status_id' => (int) PurchaseOrderStatus::where('code', $status)->value('id'),
            'order_date' => '2026-11-27',
            'requested_by' => $requester->id,
        ]);
    }

    private function poItem(PurchaseOrders $purchaseOrder, string $name, float $ordered, float $received, ?float $price)
    {
        $unit = Satuan::create(['name' => 'Uji Gram ' . uniqid(), 'symbol' => 'ujg', 'is_active' => 1]);
        $material = RawMaterials::query()->forceCreate([
            'name' => $name,
            'code' => 'UJ-' . uniqid(),
            'base_unit_id' => $unit->id,
            'storage_type_id' => RawStorageType::query()->forceCreate(['name' => 'Uji Simpan ' . uniqid()])->id,
            'is_active' => true,
        ]);

        return $purchaseOrder->items()->create([
            'raw_material_id' => $material->id,
            'qty_ordered' => $ordered,
            'unit_id' => $unit->id,
            'qty_base_ordered' => $ordered,
            'qty_base_received' => $received,
            'qty_base_rejected' => 0,
            'qty_base_cancelled' => 0,
            'unit_price' => $price,
            'subtotal' => $price === null ? null : $ordered * $price,
        ]);
    }

    public function test_page_lists_purchase_orders_with_indonesian_status_items_and_history(): void
    {
        $admin = $this->admin();
        $plan = $this->plan();
        $partial = $this->purchaseOrder($plan, 'Uji CV <Pangan>', PurchaseOrderStatus::PARTIALLY_RECEIVED, $admin);
        $flour = $this->poItem($partial, 'Uji Tepung', 2500, 1000, 10);
        $this->poItem($partial, 'Uji Gula', 300, 300, 5);
        PurchaseOrderReceipts::query()->forceCreate([
            'receipt_number' => 'UJ-GRN-001', 'purchase_order_id' => $partial->id, 'supplier_id' => $partial->supplier_id,
            'receipt_status_id' => (int) PurchaseOrderReceiptStatus::where('code', PurchaseOrderReceiptStatus::POSTED)->value('id'),
            'received_inventory_id' => $plan->planning_location_id, 'delivery_note_number' => 'SJ-778',
            'received_by' => $admin->id, 'received_at' => now(),
        ]);
        PurchaseOrderCancellations::query()->forceCreate([
            'purchase_order_id' => $partial->id, 'supplier_id' => $partial->supplier_id, 'cancellation_number' => 'UJ-BTL-001',
            'cancelled_by_party' => 'supplier', 'cancelled_at' => now(), 'reason' => 'Stok supplier habis',
        ]);
        $this->purchaseOrder($plan, 'Uji Toko Telur', PurchaseOrderStatus::DRAFT, $admin);

        $this->actingAs($admin)
            ->get(route('warehouse/procurement-plan/purchase-orders', $plan->id))
            ->assertOk()
            ->assertSee('ob-page', false)
            ->assertSee($plan->plan_number)
            ->assertSee('Plan: Sudah Jadi PO')
            ->assertSee($partial->po_number)
            ->assertSee('Uji CV &lt;Pangan&gt;', false)
            ->assertSee('Diterima Sebagian')
            ->assertSee('Draft')
            // Qty bersatuan; item yang sudah diterima penuh ditandai Lengkap
            ->assertSee('2.500 ujg')
            ->assertSee('1.000 ujg')
            ->assertSee('Lengkap')
            ->assertSee('Rp 26.500')
            ->assertSee('Uji Admin Gudang')
            // Riwayat penerimaan & pembatalan
            ->assertSee('UJ-GRN-001')
            ->assertSee('Diposting')
            ->assertSee('SJ-778')
            ->assertSee('UJ-BTL-001')
            ->assertSee('Oleh Supplier')
            ->assertSee('Stok supplier habis')
            // PO tanpa riwayat
            ->assertSee('Belum ada penerimaan maupun pembatalan untuk PO ini.')
            ->assertSee(route('warehouse/procurement-plan/detail', $plan->id));

        $this->assertNotNull($flour);
    }

    public function test_plan_without_purchase_orders_shows_empty_state(): void
    {
        $plan = $this->plan();

        $this->actingAs($this->admin())
            ->get(route('warehouse/procurement-plan/purchase-orders', $plan->id))
            ->assertOk()
            ->assertSee('Belum ada Purchase Order')
            ->assertSee('cukup dari stok');
    }
}

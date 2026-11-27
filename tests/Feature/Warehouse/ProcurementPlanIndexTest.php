<?php

namespace Tests\Feature\Warehouse;

use App\Enums\ProcurementMode;
use App\Models\Inventory;
use App\Models\InventoryType;
use App\Models\ProcurementPlanStatus;
use App\Models\ProcurementPlans;
use App\Models\PurchaseOrders;
use App\Models\RawMaterials;
use App\Models\RawStorageType;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProcurementPlanIndexTest extends TestCase
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

    private function statusId(string $code): int
    {
        return (int) ProcurementPlanStatus::where('code', $code)->value('id');
    }

    private function plan(array $attributes = [], string $status = ProcurementPlanStatus::DRAFT): ProcurementPlans
    {
        return ProcurementPlans::create(array_merge([
            'plan_number' => 'UJ-PP-' . uniqid(),
            'planning_location_id' => $attributes['planning_location_id'] ?? $this->location()->id,
            'status_id' => $this->statusId($status),
            'planned_at' => now(),
        ], $attributes));
    }

    private function item(ProcurementPlans $plan, ?float $subtotal): void
    {
        $unit = Satuan::create(['name' => 'Uji Satuan ' . uniqid(), 'symbol' => 'ujs', 'is_active' => 1]);
        $material = RawMaterials::query()->forceCreate([
            'name' => 'Uji Bahan ' . uniqid(),
            'base_unit_id' => $unit->id,
            'storage_type_id' => RawStorageType::query()->forceCreate(['name' => 'Uji Simpan ' . uniqid()])->id,
            'is_active' => true,
        ]);

        $plan->items()->create([
            'raw_material_id' => $material->id,
            'unit_id' => $unit->id,
            'qty_required_base' => 10,
            'qty_to_purchase_base' => 10,
            'estimated_subtotal' => $subtotal,
        ]);
    }

    private function purchaseOrder(ProcurementPlans $plan): void
    {
        $supplier = Supplier::create(['name' => 'Uji Supplier ' . uniqid(), 'procurement_mode' => ProcurementMode::OFFLINE->value, 'is_active' => true]);

        PurchaseOrders::create([
            'po_number' => 'UJ-PO-' . uniqid(),
            'procurement_plan_id' => $plan->id,
            'supplier_id' => $supplier->id,
            'ordered_by_inventory_id' => $plan->planning_location_id,
            'receiving_inventory_id' => $plan->planning_location_id,
        ]);
    }

    private function rows(User $actor, array $params = []): Collection
    {
        // Yajra menyimpan request sebagai singleton; dilupakan agar tiap panggilan membaca parameternya sendiri
        app()->forgetInstance('datatables.request');

        // Kolom dikirim seperti browser, karena pencarian global hanya berlaku pada kolom yang searchable
        $columns = collect(['plan_number', 'planning_location_name', 'items_count_label', 'purchase_orders_label', 'estimated_total', 'status_name', 'updated_at', 'action'])
            ->map(fn ($column) => [
                'data' => $column,
                'name' => $column,
                'searchable' => $column === 'plan_number' ? 'true' : 'false',
                'orderable' => 'false',
                'search' => ['value' => '', 'regex' => 'false'],
            ])
            ->all();

        return collect($this->actingAs($actor)->getJson(route('warehouse/procurement-plan', array_merge([
            'draw' => 1, 'start' => 0, 'length' => 1000, 'columns' => $columns,
        ], $params)), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'))
            ->keyBy(fn ($row) => (int) $row['DT_RowId']);
    }

    public function test_index_page_renders_report_style_with_indonesian_statuses(): void
    {
        $this->actingAs($this->admin(['create warehouse/procurement-plan']))
            ->get(route('warehouse/procurement-plan'))
            ->assertOk()
            ->assertSee('ob-page', false)
            ->assertSee('Daftar Plan')
            ->assertSee('filter-status')
            ->assertSee('filter-location')
            ->assertSee('Sudah Jadi PO')
            ->assertSee('Dibatalkan')
            ->assertSee(route('warehouse/procurement-plan/create'));
    }

    public function test_add_button_is_hidden_without_create_permission(): void
    {
        $this->actingAs($this->admin())
            ->get(route('warehouse/procurement-plan'))
            ->assertOk()
            ->assertDontSee(route('warehouse/procurement-plan/create'));
    }

    public function test_rows_show_location_items_purchase_orders_estimate_and_status(): void
    {
        $planner = User::factory()->create(['name' => 'Uji Perencana']);
        $location = $this->location('<b>Uji Gudang</b>');
        $plan = $this->plan(['planning_location_id' => $location->id, 'planned_by' => $planner->id], ProcurementPlanStatus::CONVERTED_TO_PO);
        $this->item($plan, 250000);
        $this->item($plan, 35000.5);
        $this->purchaseOrder($plan);
        $this->purchaseOrder($plan);
        $empty = $this->plan();
        $this->item($empty, null);

        $rows = $this->rows($this->admin());

        $row = $rows[$plan->id];
        $this->assertStringContainsString($plan->plan_number, $row['plan_number']);
        $this->assertStringContainsString('Uji Perencana', $row['plan_number']);
        $this->assertStringContainsString('&lt;b&gt;Uji Gudang&lt;/b&gt;', $row['planning_location_name']);
        $this->assertStringContainsString('2 bahan', $row['items_count_label']);
        $this->assertStringContainsString('2 PO', $row['purchase_orders_label']);
        $this->assertStringContainsString('Rp 285.000,50', $row['estimated_total']);
        $this->assertStringContainsString('Sudah Jadi PO', $row['status_name']);

        // Plan tanpa PO dan tanpa harga
        $this->assertStringContainsString('Belum ada PO', $rows[$empty->id]['purchase_orders_label']);
        $this->assertStringContainsString('Belum ada harga', $rows[$empty->id]['estimated_total']);
        $this->assertStringContainsString('Draft', $rows[$empty->id]['status_name']);
    }

    public function test_filters_limit_rows_and_can_be_combined(): void
    {
        $admin = $this->admin();
        $location = $this->location();
        $draftAtLocation = $this->plan(['planning_location_id' => $location->id]);
        $cancelledAtLocation = $this->plan(['planning_location_id' => $location->id], ProcurementPlanStatus::CANCELLED);
        $other = $this->plan();

        $byLocation = $this->rows($admin, ['location' => (string) $location->id]);
        $this->assertTrue($byLocation->has($draftAtLocation->id));
        $this->assertTrue($byLocation->has($cancelledAtLocation->id));
        $this->assertFalse($byLocation->has($other->id));

        $cancelled = $this->rows($admin, ['location' => (string) $location->id, 'status' => (string) $this->statusId(ProcurementPlanStatus::CANCELLED)]);
        $this->assertTrue($cancelled->has($cancelledAtLocation->id));
        $this->assertFalse($cancelled->has($draftAtLocation->id));
    }

    public function test_search_matches_plan_number(): void
    {
        $match = $this->plan(['plan_number' => 'UJCARI-' . uniqid()]);
        $other = $this->plan();

        $rows = $this->rows($this->admin(), ['search' => ['value' => $match->plan_number, 'regex' => 'false']]);

        $this->assertTrue($rows->has($match->id));
        $this->assertFalse($rows->has($other->id));
    }

    public function test_row_actions_follow_permissions_and_status(): void
    {
        $draft = $this->plan();
        $converted = $this->plan([], ProcurementPlanStatus::CONVERTED_TO_PO);
        $detail = route('warehouse/procurement-plan/detail', $draft->id);
        $purchaseOrders = route('warehouse/procurement-plan/purchase-orders', $draft->id);
        $delete = route('warehouse/procurement-plan/destroy', $draft->id);

        // Tanpa izin: tidak ada aksi sama sekali
        $none = $this->rows($this->admin())[$draft->id]['action'];
        $this->assertStringNotContainsString($detail, $none);
        $this->assertStringNotContainsString($delete, $none);

        $all = $this->rows($this->admin(['read warehouse/procurement-plan', 'delete warehouse/procurement-plan']));
        $this->assertStringContainsString($detail, $all[$draft->id]['action']);
        $this->assertStringContainsString($purchaseOrders, $all[$draft->id]['action']);
        $this->assertStringContainsString($delete, $all[$draft->id]['action']);

        // Plan yang bukan draft tidak bisa dihapus
        $this->assertStringNotContainsString(route('warehouse/procurement-plan/destroy', $converted->id), $all[$converted->id]['action']);

        // Izin hapus saja: tanpa detail
        $deleter = $this->rows($this->admin(['delete warehouse/procurement-plan']))[$draft->id]['action'];
        $this->assertStringContainsString($delete, $deleter);
        $this->assertStringNotContainsString($detail, $deleter);
    }

    public function test_destroy_returns_updated_stats(): void
    {
        $draft = $this->plan();
        $before = ProcurementPlans::count();

        $this->actingAs($this->admin(['delete warehouse/procurement-plan']))
            ->deleteJson(route('warehouse/procurement-plan/destroy', $draft->id))
            ->assertOk()
            ->assertJsonPath('data.total', $before - 1);
    }
}

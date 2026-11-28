<?php

namespace Tests\Feature\Warehouse;

use App\Enums\ProcurementMode;
use App\Models\Inventory;
use App\Models\InventoryRawMaterialStockBalance;
use App\Models\InventoryType;
use App\Models\Menu;
use App\Models\ProcurementPlanStatus;
use App\Models\ProcurementPlans;
use App\Models\RawMaterialCategories;
use App\Models\RawMaterialRequestStatus;
use App\Models\RawMaterialRequests;
use App\Models\RawMaterialUnitConversions;
use App\Models\RawMaterials;
use App\Models\RawStorageType;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\Supplier;
use App\Models\SupplierRawMaterials;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Route warehouse dengan nama method di luar standar (detail, submit, stock-balances, dll.)
 * harus ditolak tanpa izin "<aksi> <url menu>", bukan hanya tombolnya yang disembunyikan.
 */
class WarehouseRouteAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    private const MENUS = [
        'library' => ['library/satuan', 'library/kategori-bahan-baku', 'library/bahan-baku'],
        'warehouse' => ['warehouse/supplier', 'warehouse/inventory', 'warehouse/request-order', 'warehouse/procurement-plan'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // HasPermission hanya mengecek URL yang terdaftar sebagai menu; DB testing belum punya menu warehouse
        foreach (self::MENUS as $parentUrl => $children) {
            $parent = Menu::firstOrCreate(['url' => $parentUrl], ['name' => ucfirst($parentUrl), 'category' => strtoupper($parentUrl), 'active' => 1]);
            foreach ($children as $url) {
                Menu::firstOrCreate(['url' => $url], ['name' => $url, 'category' => $parent->category, 'main_menu_id' => $parent->id, 'active' => 1]);
            }
        }

        Cache::forget('menus');
        Cache::forget('urlMenu');
    }

    protected function tearDown(): void
    {
        Cache::forget('menus');
        Cache::forget('urlMenu');

        parent::tearDown();
    }

    private function user(array $permissions = []): User
    {
        $role = Role::firstOrCreate(['name' => 'Uji Tanpa Izin Warehouse', 'guard_name' => 'web']);
        $user = User::factory()->create(['role' => $role->id]);

        foreach ($permissions as $name) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }

        return $user;
    }

    /** Data minimal untuk parameter route. */
    private function fixtures(): array
    {
        $gram = Satuan::create(['name' => 'Uji Gram ' . uniqid(), 'symbol' => 'ujg', 'is_active' => 1]);
        $sack = Satuan::create(['name' => 'Uji Karung ' . uniqid(), 'symbol' => 'ujkrg', 'is_active' => 1]);
        $category = RawMaterialCategories::create(['name' => 'Uji Kategori ' . uniqid(), 'is_active' => true]);
        $material = RawMaterials::query()->forceCreate([
            'name' => 'Uji Bahan ' . uniqid(),
            'raw_material_category_id' => $category->id,
            'base_unit_id' => $gram->id,
            'storage_type_id' => RawStorageType::query()->forceCreate(['name' => 'Uji Simpan ' . uniqid()])->id,
            'is_active' => true,
        ]);
        // Bahan kedua untuk menambah saldo stok baru (bahan pertama sudah punya saldo di lokasi)
        $otherMaterial = $material->replicate()->fill(['name' => 'Uji Bahan Lain ' . uniqid()]);
        $otherMaterial->save();
        $conversion = RawMaterialUnitConversions::create(['raw_material_id' => $material->id, 'from_unit_id' => $sack->id, 'to_unit_id' => $gram->id, 'multiplier' => 25000]);
        $type = InventoryType::create(['name' => 'Uji Tipe ' . uniqid(), 'is_active' => true]);
        $inventory = Inventory::create(['name' => 'Uji Gudang ' . uniqid(), 'inventory_type_id' => $type->id, 'is_active' => true]);
        $balance = InventoryRawMaterialStockBalance::query()->forceCreate(['raw_material_id' => $material->id, 'inventory_id' => $inventory->id, 'qty_available' => 10, 'qty_reserved' => 0]);
        $supplier = Supplier::create(['name' => 'Uji Supplier ' . uniqid(), 'procurement_mode' => ProcurementMode::OFFLINE->value, 'is_active' => true]);
        $supplierMaterial = SupplierRawMaterials::query()->forceCreate([
            'supplier_id' => $supplier->id, 'raw_material_id' => $material->id, 'purchase_unit_id' => $sack->id,
            'minimum_order_qty' => 0, 'lead_time_days' => 0, 'is_preferred' => false, 'is_active' => true,
        ]);
        $requestOrder = RawMaterialRequests::create([
            'request_number' => 'UJ-RO-' . uniqid(),
            'requester_inventory_id' => $inventory->id,
            'status_id' => (int) RawMaterialRequestStatus::where('code', RawMaterialRequestStatus::DRAFT)->value('id'),
        ]);
        $plan = ProcurementPlans::create([
            'plan_number' => 'UJ-PP-' . uniqid(),
            'planning_location_id' => $inventory->id,
            'status_id' => (int) ProcurementPlanStatus::where('code', ProcurementPlanStatus::DRAFT)->value('id'),
        ]);

        return compact('material', 'otherMaterial', 'category', 'conversion', 'inventory', 'balance', 'supplier', 'supplierMaterial', 'requestOrder', 'plan');
    }

    /**
     * [http method, nama route, parameter, izin yang dibutuhkan, payload].
     * Route ber-FormRequest memvalidasi sebelum cek izin, jadi diberi payload valid
     * agar yang diuji memang penolakan izinnya (data tidak valid → 422, tetap tidak tersimpan).
     */
    private function cases(array $f): array
    {
        return [
            ['get', 'library/bahan-baku/detail', [$f['material']->id], 'read library/bahan-baku'],
            ['get', 'library/category-bahan-baku/detail', [$f['category']->id], 'read library/kategori-bahan-baku'],
            ['get', 'library/satuan/conversions', [], 'read library/satuan'],
            ['get', 'library/satuan/conversions/create', [], 'create library/satuan'],
            ['post', 'library/satuan/conversions/store', [], 'create library/satuan'],
            ['get', 'library/satuan/conversions/edit', [$f['conversion']->id], 'update library/satuan'],
            ['put', 'library/satuan/conversions/update', [$f['conversion']->id], 'update library/satuan'],
            ['delete', 'library/satuan/conversions/destroy', [$f['conversion']->id], 'delete library/satuan'],
            ['get', 'warehouse/inventory/detail', [$f['inventory']->id], 'read warehouse/inventory'],
            ['get', 'warehouse/inventory/stock-balances/create', [$f['inventory']->id], 'create warehouse/inventory'],
            ['post', 'warehouse/inventory/stock-balances/store', [$f['inventory']->id], 'create warehouse/inventory',
                ['raw_material_id' => $f['otherMaterial']->id, 'qty_available' => 5, 'qty_reserved' => 0]],
            ['get', 'warehouse/inventory/stock-balances/edit', [$f['inventory']->id, $f['balance']->id], 'update warehouse/inventory'],
            ['put', 'warehouse/inventory/stock-balances/update', [$f['inventory']->id, $f['balance']->id], 'update warehouse/inventory',
                ['raw_material_id' => $f['material']->id, 'qty_available' => 7, 'qty_reserved' => 0]],
            ['delete', 'warehouse/inventory/stock-balances/destroy', [$f['inventory']->id, $f['balance']->id], 'delete warehouse/inventory'],
            ['get', 'warehouse/supplier/raw-materials/create', [$f['supplier']->id], 'update warehouse/supplier'],
            ['post', 'warehouse/supplier/raw-materials/store', [$f['supplier']->id], 'update warehouse/supplier'],
            ['get', 'warehouse/supplier/raw-materials/edit', [$f['supplier']->id, $f['supplierMaterial']->id], 'update warehouse/supplier'],
            ['put', 'warehouse/supplier/raw-materials/update', [$f['supplier']->id, $f['supplierMaterial']->id], 'update warehouse/supplier'],
            ['delete', 'warehouse/supplier/raw-materials/destroy', [$f['supplier']->id, $f['supplierMaterial']->id], 'update warehouse/supplier'],
            ['get', 'warehouse/request-order/detail', [$f['requestOrder']->id], 'read warehouse/request-order'],
            ['post', 'warehouse/request-order/submit', [$f['requestOrder']->id], 'update warehouse/request-order'],
            ['post', 'warehouse/request-order/approve', [$f['requestOrder']->id], 'approve warehouse/request-order'],
            ['post', 'warehouse/request-order/reject', [$f['requestOrder']->id], 'approve warehouse/request-order'],
            ['post', 'warehouse/request-order/cancel', [$f['requestOrder']->id], 'update warehouse/request-order'],
            ['get', 'warehouse/procurement-plan/detail', [$f['plan']->id], 'read warehouse/procurement-plan'],
            ['get', 'warehouse/procurement-plan/purchase-orders', [$f['plan']->id], 'read warehouse/procurement-plan'],
        ];
    }

    private function hit(User $user, string $method, string $route, array $params, array $payload = [])
    {
        return $method === 'get'
            ? $this->actingAs($user)->getJson(route($route, $params))
            : $this->actingAs($user)->{$method . 'Json'}(route($route, $params), $payload);
    }

    public function test_routes_outside_standard_method_names_are_forbidden_without_permission(): void
    {
        $fixtures = $this->fixtures();
        $stranger = $this->user();
        $failures = [];

        foreach ($this->cases($fixtures) as $case) {
            [$method, $route, $params] = $case;
            $status = $this->hit($stranger, $method, $route, $params, $case[4] ?? [])->getStatusCode();
            if ($status !== 403) {
                $failures[] = strtoupper($method) . " {$route} → {$status}";
            }
        }

        $this->assertSame([], $failures, 'Route berikut masih bisa diakses tanpa izin');
    }

    public function test_routes_pass_authorization_with_the_matching_permission(): void
    {
        $failures = [];
        $total = count($this->cases($this->fixtures()));

        for ($i = 0; $i < $total; $i++) {
            // Data dibuat ulang per kasus agar aksi hapus/ajukan sebelumnya tidak memengaruhi kasus berikutnya
            $case = $this->cases($this->fixtures())[$i];
            [$method, $route, $params, $permission] = $case;
            $status = $this->hit($this->user([$permission]), $method, $route, $params, $case[4] ?? [])->getStatusCode();

            if ($status === 403) {
                $failures[] = strtoupper($method) . " {$route} dengan izin '{$permission}' → 403";
            }
        }

        $this->assertSame([], $failures, 'Route berikut menolak user yang punya izinnya');
    }

    public function test_other_permission_of_the_same_menu_is_not_enough(): void
    {
        $f = $this->fixtures();

        // Hanya bisa membaca: tidak boleh mengubah saldo stok, mengajukan request, atau menghapus konversi
        $reader = $this->user(['read warehouse/inventory', 'read warehouse/request-order', 'read library/satuan']);

        $this->hit($reader, 'put', 'warehouse/inventory/stock-balances/update', [$f['inventory']->id, $f['balance']->id],
            ['raw_material_id' => $f['material']->id, 'qty_available' => 7, 'qty_reserved' => 0])->assertForbidden();
        $this->assertEquals(10, (float) $f['balance']->fresh()->qty_available);
        $this->hit($reader, 'post', 'warehouse/request-order/submit', [$f['requestOrder']->id])->assertForbidden();
        // Izin ubah request order tidak cukup untuk menyetujui
        $updater = $this->user(['update warehouse/request-order']);
        $this->hit($updater, 'post', 'warehouse/request-order/approve', [$f['requestOrder']->id])->assertForbidden();
        $this->hit($updater, 'post', 'warehouse/request-order/reject', [$f['requestOrder']->id], ['closed_remarks' => 'Uji'])->assertForbidden();
        $this->hit($reader, 'delete', 'library/satuan/conversions/destroy', [$f['conversion']->id])->assertForbidden();
        $this->hit($reader, 'get', 'warehouse/inventory/detail', [$f['inventory']->id])->assertOk();
    }
}

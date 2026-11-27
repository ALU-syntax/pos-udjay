<?php

namespace Tests\Feature\Warehouse;

use App\Models\Inventory;
use App\Models\InventoryType;
use App\Models\RawMaterialRequestItems;
use App\Models\RawMaterialRequestStatus;
use App\Models\RawMaterialRequests;
use App\Models\RawMaterials;
use App\Models\RawStorageType;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RequestOrderFormTest extends TestCase
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

    private function unit(string $symbol = 'ujkg'): Satuan
    {
        return Satuan::create(['name' => 'Uji Satuan ' . uniqid(), 'symbol' => $symbol, 'is_active' => 1]);
    }

    private function material(array $attributes = [], ?Satuan $unit = null): RawMaterials
    {
        return RawMaterials::query()->forceCreate(array_merge([
            'name' => 'Uji Bahan ' . uniqid(),
            'base_unit_id' => ($unit ?? $this->unit())->id,
            'storage_type_id' => RawStorageType::query()->forceCreate(['name' => 'Uji Simpan ' . uniqid()])->id,
            'is_active' => true,
        ], $attributes));
    }

    private function draft(Inventory $requester, RawMaterials $material, float $qty): RawMaterialRequests
    {
        $requestOrder = RawMaterialRequests::create([
            'request_number' => 'UJ-RO-' . uniqid(),
            'requester_inventory_id' => $requester->id,
            'status_id' => RawMaterialRequestStatus::where('code', RawMaterialRequestStatus::DRAFT)->value('id'),
            'notes' => 'Catatan draft uji',
        ]);

        RawMaterialRequestItems::create([
            'raw_material_request_id' => $requestOrder->id,
            'raw_material_id' => $material->id,
            'requested_qty' => $qty,
            'requested_satuan_id' => $material->base_unit_id,
            'requested_satuan_name' => 'ujkg',
            'requested_conversion_to_base' => 1,
            'requested_base_qty' => $qty,
            'requested_base_satuan_id' => $material->base_unit_id,
            'requested_base_satuan_name' => 'ujkg',
            'fulfilled_base_qty' => 0,
            'fulfilled_base_satuan_id' => $material->base_unit_id,
            'fulfilled_base_satuan_name' => 'ujkg',
            'notes' => 'Catatan item uji',
        ]);

        return $requestOrder;
    }

    public function test_create_page_renders_report_style_form(): void
    {
        $active = $this->material(['name' => 'Uji Aktif ' . uniqid(), 'code' => 'UJ-AKT']);
        $inactive = $this->material(['name' => 'Uji Mati ' . uniqid(), 'is_active' => false]);

        $this->actingAs($this->admin(['create warehouse/request-order']))
            ->get(route('warehouse/request-order/create'))
            ->assertOk()
            ->assertSee('ob-page', false)
            ->assertSee('Tambah Request Order')
            ->assertSee('Informasi Request')
            ->assertSee('Kosongkan daftar')
            ->assertSee('Simpan Draft')
            ->assertSee($active->name . ' · UJ-AKT (ujkg)')
            ->assertDontSee($inactive->name)
            // Template baris memakai step="any" agar qty desimal (0,25) diterima browser
            ->assertSee('step="any"', false)
            ->assertDontSee('step="0.1"', false);
    }

    public function test_edit_page_shows_saved_items_with_decimal_qty(): void
    {
        $requester = $this->location('Uji Kitchen Edit ' . uniqid());
        $material = $this->material(['name' => 'Uji Bahan Edit ' . uniqid(), 'code' => 'UJ-EDT']);
        $requestOrder = $this->draft($requester, $material, 0.25);

        $this->actingAs($this->admin(['update warehouse/request-order']))
            ->get(route('warehouse/request-order/edit', $requestOrder->id))
            ->assertOk()
            ->assertSee('Edit Request Order')
            ->assertSee($requestOrder->request_number)
            ->assertSee('Simpan Perubahan')
            ->assertSee($material->name)
            ->assertSee('UJ-EDT · Satuan dasar: ujkg')
            ->assertSee('value="0.25"', false)
            ->assertSee('Catatan item uji')
            ->assertSee('Catatan draft uji');
    }

    public function test_store_accepts_decimal_qty_and_redirects_to_detail(): void
    {
        $requester = $this->location();
        $material = $this->material();

        $response = $this->actingAs($this->admin(['create warehouse/request-order']))
            ->post(route('warehouse/request-order/store'), [
                'requester_inventory_id' => $requester->id,
                'items' => [
                    ['raw_material_id' => $material->id, 'requested_qty' => '0.25', 'requested_satuan_id' => $material->base_unit_id],
                ],
            ]);

        $requestOrder = RawMaterialRequests::where('requester_inventory_id', $requester->id)->latest('id')->firstOrFail();
        $response->assertRedirect(route('warehouse/request-order/detail', $requestOrder->id));
        $this->assertSame(0.25, (float) $requestOrder->items()->value('requested_qty'));
    }

    public function test_validation_errors_show_notice_and_keep_input(): void
    {
        $requester = $this->location();
        $material = $this->material(['name' => 'Uji Bahan Gagal ' . uniqid()]);

        $this->actingAs($this->admin(['create warehouse/request-order']))
            ->from(route('warehouse/request-order/create'))
            ->followingRedirects()
            ->post(route('warehouse/request-order/store'), [
                'requester_inventory_id' => $requester->id,
                'items' => [
                    ['raw_material_id' => $material->id, 'requested_qty' => '0', 'requested_satuan_id' => $material->base_unit_id, 'notes' => 'Catatan tetap ada'],
                ],
            ])
            ->assertOk()
            ->assertSee('Ada data yang belum sesuai')
            ->assertSee('Qty request harus lebih dari 0.')
            ->assertSee($material->name)
            ->assertSee('Catatan tetap ada');
    }
}

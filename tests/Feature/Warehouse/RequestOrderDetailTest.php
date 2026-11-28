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

class RequestOrderDetailTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(array $permissions = [], string $name = null): User
    {
        $role = Role::firstOrCreate(['name' => 'Uji Admin Warehouse', 'guard_name' => 'web']);
        $admin = User::factory()->create(array_filter(['role' => $role->id, 'name' => $name]));

        foreach ($permissions as $permission) {
            $admin->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }

        return $admin;
    }

    private function location(string $name = null): Inventory
    {
        $type = InventoryType::create(['name' => 'Uji Tipe ' . uniqid(), 'is_active' => true]);

        return Inventory::create(['name' => $name ?? 'Uji Lokasi ' . uniqid(), 'inventory_type_id' => $type->id, 'is_active' => true]);
    }

    private function requestOrder(string $status, array $attributes = []): RawMaterialRequests
    {
        return RawMaterialRequests::create(array_merge([
            'request_number' => 'UJ-RO-' . uniqid(),
            'requester_inventory_id' => $this->location()->id,
            'status_id' => RawMaterialRequestStatus::where('code', $status)->value('id'),
        ], $attributes));
    }

    /** Item dengan satuan dasar "ujg"; satuan request bisa berbeda (mis. kg → g). */
    private function item(RawMaterialRequests $requestOrder, array $attributes = [], string $name = null): RawMaterialRequestItems
    {
        $base = Satuan::create(['name' => 'Uji Gram ' . uniqid(), 'symbol' => 'ujg', 'is_active' => 1]);
        $material = RawMaterials::query()->forceCreate([
            'name' => $name ?? 'Uji Bahan ' . uniqid(),
            'code' => 'UJ-' . strtoupper(substr(uniqid(), -5)),
            'base_unit_id' => $base->id,
            'storage_type_id' => RawStorageType::query()->forceCreate(['name' => 'Uji Simpan ' . uniqid()])->id,
            'is_active' => true,
        ]);

        return RawMaterialRequestItems::create(array_merge([
            'raw_material_request_id' => $requestOrder->id,
            'raw_material_id' => $material->id,
            'requested_qty' => 1,
            'requested_satuan_id' => $base->id,
            'requested_satuan_name' => 'ujg',
            'requested_conversion_to_base' => 1,
            'requested_base_qty' => 1,
            'requested_base_satuan_id' => $base->id,
            'requested_base_satuan_name' => 'ujg',
            'fulfilled_base_qty' => 0,
            'fulfilled_base_satuan_id' => $base->id,
            'fulfilled_base_satuan_name' => 'ujg',
        ], $attributes));
    }

    public function test_draft_detail_shows_indonesian_labels_and_actions_follow_permissions(): void
    {
        $requester = $this->location('<b>Uji Kitchen</b>');
        $draft = $this->requestOrder(RawMaterialRequestStatus::DRAFT, [
            'requester_inventory_id' => $requester->id,
            'notes' => 'Catatan request uji',
            'needed_at' => today()->subDay(),
        ]);
        $this->item($draft, ['notes' => 'Catatan item uji']);
        $edit = route('warehouse/request-order/edit', $draft->id);
        $submit = route('warehouse/request-order/submit', $draft->id);
        $destroy = route('warehouse/request-order/destroy', $draft->id);

        $this->actingAs($this->admin(['read warehouse/request-order', 'update warehouse/request-order', 'delete warehouse/request-order']))
            ->get(route('warehouse/request-order/detail', $draft->id))
            ->assertOk()
            ->assertSee('ob-page', false)
            ->assertSee($draft->request_number)
            ->assertSee('&lt;b&gt;Uji Kitchen&lt;/b&gt;', false)
            ->assertSee('Pemenuh belum ditentukan')
            ->assertSee('lewat tanggal')
            ->assertSee('Catatan request uji')
            ->assertSee('Catatan item uji')
            ->assertSee('Profil Request')
            ->assertSee($edit)
            ->assertSee($submit)
            ->assertSee($destroy)
            ->assertDontSee('Requested By')
            ->assertDontSee('Fulfillment Summary');

        $this->actingAs($this->admin(['read warehouse/request-order']))
            ->get(route('warehouse/request-order/detail', $draft->id))
            ->assertOk()
            ->assertDontSee($edit)
            ->assertDontSee($submit)
            ->assertDontSee($destroy);
    }

    public function test_review_form_keeps_exact_small_qty_and_needs_update_permission(): void
    {
        $submitted = $this->requestOrder(RawMaterialRequestStatus::SUBMITTED);
        // 0,05 g dulu dibulatkan jadi 0.1 sehingga melebihi qty diminta dan approval ditolak
        $small = $this->item($submitted, ['requested_qty' => 0.05, 'requested_base_qty' => 0.05]);
        $converted = $this->item($submitted, [
            'requested_qty' => 2.5,
            'requested_satuan_name' => 'ujkg',
            'requested_satuan_id' => Satuan::create(['name' => 'Uji Kg ' . uniqid(), 'symbol' => 'ujkg', 'is_active' => 1])->id,
            'requested_conversion_to_base' => 1000,
            'requested_base_qty' => 2500.25,
        ]);

        $this->actingAs($this->admin(['read warehouse/request-order', 'approve warehouse/request-order']))
            ->get(route('warehouse/request-order/detail', $submitted->id))
            ->assertOk()
            ->assertSee('Diajukan')
            ->assertSee('Review Item')
            ->assertSee(route('warehouse/request-order/approve', $submitted->id))
            ->assertSee('name="items[' . $small->id . '][approved_base_qty]"', false)
            ->assertSee('value="0.05" min="0" max="0.05" step="any"', false)
            ->assertSee('value="2500.25" min="0" max="2500.25" step="any"', false)
            // Satuan request berbeda dari satuan dasar: qty dasar ikut ditampilkan
            ->assertSee('2,5')
            ->assertSee('= 2.500,25 ujg')
            ->assertDontSee('step="0.1"', false);

        // Izin update saja tidak cukup: form approval butuh izin approve
        $this->actingAs($this->admin(['read warehouse/request-order', 'update warehouse/request-order']))
            ->get(route('warehouse/request-order/detail', $submitted->id))
            ->assertOk()
            ->assertDontSee(route('warehouse/request-order/approve', $submitted->id))
            ->assertDontSee('name="items[' . $small->id . '][approved_base_qty]"', false);

        $this->assertNotNull($converted);
    }

    public function test_approving_exact_small_qty_succeeds(): void
    {
        $submitted = $this->requestOrder(RawMaterialRequestStatus::SUBMITTED);
        $small = $this->item($submitted, ['requested_qty' => 0.05, 'requested_base_qty' => 0.05]);

        $this->actingAs($this->admin(['approve warehouse/request-order']))
            ->post(route('warehouse/request-order/approve', $submitted->id), [
                'items' => [$small->id => ['approved_base_qty' => '0.05']],
            ])
            ->assertRedirect(route('warehouse/request-order/detail', $submitted->id))
            ->assertSessionHasNoErrors();

        $this->assertSame(RawMaterialRequestStatus::APPROVED, $submitted->fresh()->status->code);
        $this->assertSame(0.05, (float) $small->fresh()->approved_base_qty);
    }

    public function test_approved_detail_shows_item_counts_and_history(): void
    {
        $approver = $this->admin([], 'Uji Penyetuju');
        $approved = $this->requestOrder(RawMaterialRequestStatus::APPROVED, [
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
        $full = $this->item($approved, ['approved_base_qty' => 1, 'approved_base_satuan_name' => 'ujg', 'fulfilled_base_qty' => 1]);
        $this->item($approved, ['approved_base_qty' => 0.5, 'approved_base_satuan_name' => 'ujg']);
        $this->item($approved, ['approved_base_qty' => 0, 'approved_base_satuan_name' => 'ujg']);

        $response = $this->actingAs($this->admin(['read warehouse/request-order', 'update warehouse/request-order']))
            ->get(route('warehouse/request-order/detail', $approved->id))
            ->assertOk()
            ->assertSee('Disetujui')
            ->assertSee('oleh Uji Penyetuju')
            ->assertSee('ro-rejected-qty', false)
            // Request yang sudah disetujui tidak bisa direview lagi
            ->assertDontSee(route('warehouse/request-order/approve', $approved->id));

        // Hitungan item, bukan jumlah qty lintas satuan
        $this->assertSame(['total_items' => 3, 'approved_items' => 2, 'fulfilled_items' => 1], $response->viewData('stats'));
        $this->assertNotNull($full);
    }

    public function test_cancel_and_reject_buttons_follow_status_and_permissions(): void
    {
        $draft = $this->requestOrder(RawMaterialRequestStatus::DRAFT);
        $submitted = $this->requestOrder(RawMaterialRequestStatus::SUBMITTED);
        $approved = $this->requestOrder(RawMaterialRequestStatus::APPROVED);
        $updater = $this->admin(['read warehouse/request-order', 'update warehouse/request-order']);
        $approver = $this->admin(['read warehouse/request-order', 'approve warehouse/request-order']);
        $reader = $this->admin(['read warehouse/request-order']);
        $see = fn (User $user, RawMaterialRequests $ro) => $this->actingAs($user)->get(route('warehouse/request-order/detail', $ro->id))->assertOk();

        // Batalkan: draft & diajukan, butuh izin update
        $see($updater, $draft)->assertSee(route('warehouse/request-order/cancel', $draft->id));
        $see($updater, $submitted)
            ->assertSee(route('warehouse/request-order/cancel', $submitted->id))
            ->assertDontSee(route('warehouse/request-order/reject', $submitted->id));
        $see($updater, $approved)->assertDontSee(route('warehouse/request-order/cancel', $approved->id));

        // Tolak: hanya diajukan, butuh izin approve
        $see($approver, $submitted)
            ->assertSee(route('warehouse/request-order/reject', $submitted->id))
            ->assertDontSee(route('warehouse/request-order/cancel', $submitted->id));
        $see($approver, $draft)->assertDontSee(route('warehouse/request-order/reject', $draft->id));

        $see($reader, $submitted)
            ->assertDontSee(route('warehouse/request-order/cancel', $submitted->id))
            ->assertDontSee(route('warehouse/request-order/reject', $submitted->id));
    }

    public function test_closed_request_shows_who_when_and_escaped_remarks_in_history(): void
    {
        $closer = $this->admin([], 'Uji Kepala Gudang');
        $rejected = $this->requestOrder(RawMaterialRequestStatus::REJECTED, [
            'closed_by' => $closer->id,
            'closed_at' => now(),
            'closed_remarks' => '<b>Stok kosong</b>',
        ]);
        $cancelled = $this->requestOrder(RawMaterialRequestStatus::CANCELLED, [
            'closed_by' => $closer->id,
            'closed_at' => now(),
            'closed_remarks' => 'Salah pilih lokasi',
        ]);
        $admin = $this->admin(['read warehouse/request-order', 'update warehouse/request-order', 'approve warehouse/request-order']);

        $this->actingAs($admin)
            ->get(route('warehouse/request-order/detail', $rejected->id))
            ->assertOk()
            ->assertSeeInOrder(['Riwayat', 'Ditolak', 'oleh Uji Kepala Gudang', 'Alasan: &lt;b&gt;Stok kosong&lt;/b&gt;'], false)
            ->assertDontSee(route('warehouse/request-order/cancel', $rejected->id))
            ->assertDontSee(route('warehouse/request-order/reject', $rejected->id));

        $this->actingAs($admin)
            ->get(route('warehouse/request-order/detail', $cancelled->id))
            ->assertOk()
            ->assertSeeInOrder(['Riwayat', 'Dibatalkan', 'oleh Uji Kepala Gudang', 'Alasan: Salah pilih lokasi']);
    }

    public function test_detail_shows_empty_state_without_items(): void
    {
        $draft = $this->requestOrder(RawMaterialRequestStatus::DRAFT);

        $this->actingAs($this->admin(['read warehouse/request-order']))
            ->get(route('warehouse/request-order/detail', $draft->id))
            ->assertOk()
            ->assertSee('Belum ada item')
            ->assertDontSee('id="request-order-items-detail-table"', false);
    }

    public function test_reject_needs_remarks_and_records_who_and_why(): void
    {
        $submitted = $this->requestOrder(RawMaterialRequestStatus::SUBMITTED);
        $approver = $this->admin(['approve warehouse/request-order']);

        $this->actingAs($approver)
            ->postJson(route('warehouse/request-order/reject', $submitted->id), ['closed_remarks' => '   '])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['closed_remarks' => 'Alasan wajib diisi.']);
        $this->assertSame(RawMaterialRequestStatus::SUBMITTED, $submitted->fresh()->status->code);

        $this->actingAs($approver)
            ->postJson(route('warehouse/request-order/reject', $submitted->id), ['closed_remarks' => ' Stok gudang pusat kosong '])
            ->assertOk();

        $submitted->refresh();
        $this->assertSame(RawMaterialRequestStatus::REJECTED, $submitted->status->code);
        $this->assertSame($approver->id, $submitted->closed_by);
        $this->assertNotNull($submitted->closed_at);
        $this->assertSame('Stok gudang pusat kosong', $submitted->closed_remarks);
    }

    /** Izin route (approve vs update) diuji di WarehouseRouteAuthorizationTest: DB testing tidak punya menu warehouse. */
    public function test_reject_only_for_submitted(): void
    {
        $draft = $this->requestOrder(RawMaterialRequestStatus::DRAFT);

        $this->actingAs($this->admin(['approve warehouse/request-order']))
            ->postJson(route('warehouse/request-order/reject', $draft->id), ['closed_remarks' => 'Uji'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status' => 'Request order hanya bisa ditolak saat berstatus diajukan.']);

        $this->assertSame(RawMaterialRequestStatus::DRAFT, $draft->fresh()->status->code);
    }

    public function test_cancel_draft_or_submitted_with_remarks(): void
    {
        $updater = $this->admin(['update warehouse/request-order']);

        foreach ([RawMaterialRequestStatus::DRAFT, RawMaterialRequestStatus::SUBMITTED] as $status) {
            $requestOrder = $this->requestOrder($status);

            $this->actingAs($updater)
                ->postJson(route('warehouse/request-order/cancel', $requestOrder->id), [])
                ->assertStatus(422)
                ->assertJsonValidationErrors('closed_remarks');

            $this->actingAs($updater)
                ->postJson(route('warehouse/request-order/cancel', $requestOrder->id), ['closed_remarks' => 'Salah pilih lokasi'])
                ->assertOk();

            $requestOrder->refresh();
            $this->assertSame(RawMaterialRequestStatus::CANCELLED, $requestOrder->status->code);
            $this->assertSame($updater->id, $requestOrder->closed_by);
            $this->assertSame('Salah pilih lokasi', $requestOrder->closed_remarks);
        }
    }

    public function test_cancel_rejected_for_closed_or_approved_request(): void
    {
        $updater = $this->admin(['update warehouse/request-order']);

        foreach ([RawMaterialRequestStatus::APPROVED, RawMaterialRequestStatus::REJECTED, RawMaterialRequestStatus::CANCELLED] as $status) {
            $requestOrder = $this->requestOrder($status);

            $this->actingAs($updater)
                ->postJson(route('warehouse/request-order/cancel', $requestOrder->id), ['closed_remarks' => 'Uji'])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['status' => 'Request order hanya bisa dibatalkan saat berstatus draft atau diajukan.']);

            $this->assertSame($status, $requestOrder->fresh()->status->code);
        }
    }
}

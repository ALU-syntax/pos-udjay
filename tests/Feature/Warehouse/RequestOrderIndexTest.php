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
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RequestOrderIndexTest extends TestCase
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
        return (int) RawMaterialRequestStatus::where('code', $code)->value('id');
    }

    private function requestOrder(array $attributes = [], string $status = RawMaterialRequestStatus::DRAFT): RawMaterialRequests
    {
        return RawMaterialRequests::create(array_merge([
            'request_number' => 'UJ-RO-' . uniqid(),
            'requester_inventory_id' => $attributes['requester_inventory_id'] ?? $this->location()->id,
            'status_id' => $this->statusId($status),
        ], $attributes));
    }

    private function item(RawMaterialRequests $requestOrder): void
    {
        $unit = Satuan::create(['name' => 'Uji Satuan ' . uniqid(), 'symbol' => 'ujs', 'is_active' => 1]);
        $material = RawMaterials::query()->forceCreate([
            'name' => 'Uji Bahan ' . uniqid(),
            'base_unit_id' => $unit->id,
            'storage_type_id' => RawStorageType::query()->forceCreate(['name' => 'Uji Simpan ' . uniqid()])->id,
            'is_active' => true,
        ]);

        RawMaterialRequestItems::create([
            'raw_material_request_id' => $requestOrder->id,
            'raw_material_id' => $material->id,
            'requested_qty' => 1,
            'requested_satuan_id' => $unit->id,
            'requested_satuan_name' => 'ujs',
            'requested_conversion_to_base' => 1,
            'requested_base_qty' => 1,
            'requested_base_satuan_id' => $unit->id,
            'requested_base_satuan_name' => 'ujs',
            'fulfilled_base_qty' => 0,
            'fulfilled_base_satuan_id' => $unit->id,
            'fulfilled_base_satuan_name' => 'ujs',
        ]);
    }

    private function rows(User $actor, array $params = []): Collection
    {
        // Yajra menyimpan request sebagai singleton; dilupakan agar tiap panggilan membaca parameternya sendiri
        app()->forgetInstance('datatables.request');

        // Kolom dikirim seperti browser, karena pencarian global hanya berlaku pada kolom yang searchable
        $columns = collect(['request_number', 'route', 'items_count_label', 'needed_at', 'status_name', 'updated_at', 'action'])
            ->map(fn ($column) => [
                'data' => $column,
                'name' => $column,
                'searchable' => $column === 'request_number' ? 'true' : 'false',
                'orderable' => 'false',
                'search' => ['value' => '', 'regex' => 'false'],
            ])
            ->all();

        return collect($this->actingAs($actor)->getJson(route('warehouse/request-order', array_merge([
            'draw' => 1, 'start' => 0, 'length' => 1000, 'columns' => $columns,
        ], $params)), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'))
            ->keyBy(fn ($row) => (int) $row['DT_RowId']);
    }

    public function test_index_page_renders_report_style_with_indonesian_statuses(): void
    {
        $this->actingAs($this->admin(['create warehouse/request-order']))
            ->get(route('warehouse/request-order'))
            ->assertOk()
            ->assertSee('ob-page', false)
            ->assertSee('Daftar Request')
            ->assertSee('filter-status')
            ->assertSee('filter-requester')
            ->assertSee('filter-fulfillment')
            ->assertSee('Diajukan')
            ->assertSee('Disetujui')
            ->assertSee(route('warehouse/request-order/create'));
    }

    public function test_add_button_is_hidden_without_create_permission(): void
    {
        $this->actingAs($this->admin())
            ->get(route('warehouse/request-order'))
            ->assertOk()
            ->assertDontSee(route('warehouse/request-order/create'));
    }

    public function test_rows_show_route_items_status_and_overdue_date(): void
    {
        $requester = $this->location('<b>Uji Kitchen</b>');
        $fulfillment = $this->location('Uji Gudang Pusat ' . uniqid());
        $overdue = $this->requestOrder([
            'requester_inventory_id' => $requester->id,
            'fulfillment_inventory_id' => $fulfillment->id,
            'needed_at' => today()->subDays(2),
        ], RawMaterialRequestStatus::SUBMITTED);
        $this->item($overdue);
        $this->item($overdue);
        $closed = $this->requestOrder(['needed_at' => today()->subDays(2)], RawMaterialRequestStatus::FULFILLED);
        $noFulfillment = $this->requestOrder();

        $rows = $this->rows($this->admin());

        $row = $rows[$overdue->id];
        $this->assertStringContainsString($overdue->request_number, $row['request_number']);
        $this->assertStringContainsString('&lt;b&gt;Uji Kitchen&lt;/b&gt;', $row['route']);
        $this->assertStringContainsString($fulfillment->name, $row['route']);
        $this->assertStringContainsString('2 item', $row['items_count_label']);
        $this->assertStringContainsString('Diajukan', $row['status_name']);
        $this->assertStringContainsString('Lewat tanggal', $row['needed_at']);

        // Request yang sudah selesai tidak ditandai terlambat
        $this->assertStringNotContainsString('Lewat tanggal', $rows[$closed->id]['needed_at']);
        $this->assertStringContainsString('Terpenuhi', $rows[$closed->id]['status_name']);
        $this->assertStringContainsString('Pemenuh belum ditentukan', $rows[$noFulfillment->id]['route']);
    }

    public function test_filters_limit_rows_and_can_be_combined(): void
    {
        $admin = $this->admin();
        $requester = $this->location();
        $fulfillment = $this->location();
        $draftFromRequester = $this->requestOrder(['requester_inventory_id' => $requester->id, 'fulfillment_inventory_id' => $fulfillment->id]);
        $submittedFromRequester = $this->requestOrder(['requester_inventory_id' => $requester->id], RawMaterialRequestStatus::SUBMITTED);
        $other = $this->requestOrder();

        $byRequester = $this->rows($admin, ['requester' => (string) $requester->id]);
        $this->assertTrue($byRequester->has($draftFromRequester->id));
        $this->assertTrue($byRequester->has($submittedFromRequester->id));
        $this->assertFalse($byRequester->has($other->id));

        $byFulfillment = $this->rows($admin, ['fulfillment' => (string) $fulfillment->id]);
        $this->assertTrue($byFulfillment->has($draftFromRequester->id));
        $this->assertFalse($byFulfillment->has($submittedFromRequester->id));

        $noFulfillment = $this->rows($admin, ['fulfillment' => 'none']);
        $this->assertTrue($noFulfillment->has($submittedFromRequester->id));
        $this->assertFalse($noFulfillment->has($draftFromRequester->id));

        $submitted = $this->rows($admin, ['requester' => (string) $requester->id, 'status' => (string) $this->statusId(RawMaterialRequestStatus::SUBMITTED)]);
        $this->assertTrue($submitted->has($submittedFromRequester->id));
        $this->assertFalse($submitted->has($draftFromRequester->id));
    }

    public function test_search_matches_request_number(): void
    {
        $match = $this->requestOrder(['request_number' => 'UJCARI-' . uniqid()]);
        $other = $this->requestOrder();

        $rows = $this->rows($this->admin(), ['search' => ['value' => $match->request_number, 'regex' => 'false']]);

        $this->assertTrue($rows->has($match->id));
        $this->assertFalse($rows->has($other->id));
    }

    public function test_row_actions_follow_permissions_and_status(): void
    {
        $draft = $this->requestOrder();
        $submitted = $this->requestOrder([], RawMaterialRequestStatus::SUBMITTED);
        $draftEdit = route('warehouse/request-order/edit', $draft->id);
        $draftSubmit = route('warehouse/request-order/submit', $draft->id);
        $draftDelete = route('warehouse/request-order/destroy', $draft->id);

        // Tanpa izin: tidak ada aksi sama sekali
        $none = $this->rows($this->admin());
        $this->assertStringNotContainsString(route('warehouse/request-order/detail', $draft->id), $none[$draft->id]['action']);
        $this->assertStringNotContainsString($draftEdit, $none[$draft->id]['action']);

        $all = $this->rows($this->admin(['read warehouse/request-order', 'update warehouse/request-order', 'delete warehouse/request-order']));
        $this->assertStringContainsString(route('warehouse/request-order/detail', $draft->id), $all[$draft->id]['action']);
        $this->assertStringContainsString($draftEdit, $all[$draft->id]['action']);
        $this->assertStringContainsString($draftSubmit, $all[$draft->id]['action']);
        $this->assertStringContainsString($draftDelete, $all[$draft->id]['action']);

        // Request yang sudah diajukan hanya bisa direview
        $submittedAction = $all[$submitted->id]['action'];
        $this->assertStringContainsString('Review request', $submittedAction);
        $this->assertStringNotContainsString(route('warehouse/request-order/edit', $submitted->id), $submittedAction);
        $this->assertStringNotContainsString(route('warehouse/request-order/submit', $submitted->id), $submittedAction);
        $this->assertStringNotContainsString(route('warehouse/request-order/destroy', $submitted->id), $submittedAction);

        // Izin update saja: edit & ajukan, tanpa hapus
        $updater = $this->rows($this->admin(['update warehouse/request-order']))[$draft->id]['action'];
        $this->assertStringContainsString($draftSubmit, $updater);
        $this->assertStringNotContainsString($draftDelete, $updater);
    }

    public function test_submit_returns_updated_stats(): void
    {
        $draft = $this->requestOrder();
        $this->item($draft);
        $before = RawMaterialRequests::where('status_id', $this->statusId(RawMaterialRequestStatus::SUBMITTED))->count();

        $this->actingAs($this->admin(['update warehouse/request-order']))
            ->postJson(route('warehouse/request-order/submit', $draft->id))
            ->assertOk()
            ->assertJsonPath('data.submitted', $before + 1);
    }
}

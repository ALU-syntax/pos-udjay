<?php

namespace Tests\Feature\Warehouse;

use App\Enums\ProcurementMode;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SupplierIndexTest extends TestCase
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
            'code' => null,
            'procurement_mode' => ProcurementMode::BOTH->value,
            'is_active' => true,
        ], $attributes));
    }

    private function rows(User $actor, array $params = []): Collection
    {
        // Yajra menyimpan request sebagai singleton; dilupakan agar tiap panggilan membaca parameternya sendiri
        app()->forgetInstance('datatables.request');

        // Kolom dikirim seperti browser, karena pencarian global hanya berlaku pada kolom yang searchable
        $columns = collect(['name', 'procurement_mode', 'operational_window', 'order_place', 'raw_material_names', 'is_active', 'action'])
            ->map(fn ($column) => [
                'data' => $column,
                'name' => $column,
                'searchable' => in_array($column, ['is_active', 'action'], true) ? 'false' : 'true',
                'orderable' => 'false',
                'search' => ['value' => '', 'regex' => 'false'],
            ])
            ->all();

        return collect($this->actingAs($actor)->getJson(route('warehouse/supplier', array_merge([
            'draw' => 1, 'start' => 0, 'length' => 500, 'columns' => $columns,
        ], $params)), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'))
            ->keyBy(fn ($row) => (int) $row['DT_RowId']);
    }

    public function test_index_page_renders_report_style(): void
    {
        $this->actingAs($this->admin(['create warehouse/supplier']))
            ->get(route('warehouse/supplier'))
            ->assertOk()
            ->assertSee('Daftar Supplier')
            ->assertSee('filter-mode')
            ->assertSee(route('warehouse/supplier/create'));
    }

    public function test_add_button_is_hidden_without_create_permission(): void
    {
        $this->actingAs($this->admin())
            ->get(route('warehouse/supplier'))
            ->assertOk()
            ->assertDontSee(route('warehouse/supplier/create'));
    }

    public function test_rows_escape_name_and_show_code_status_and_mode(): void
    {
        $admin = $this->admin();
        $supplier = $this->supplier([
            'name' => '<b>Uji Escape</b>',
            'code' => 'UJI-ESC-1',
            'procurement_mode' => ProcurementMode::ONLINE->value,
            'is_active' => false,
        ]);

        $row = $this->rows($admin)[$supplier->id];

        $this->assertStringContainsString('&lt;b&gt;Uji Escape&lt;/b&gt;', $row['name']);
        $this->assertStringContainsString('UJI-ESC-1', $row['name']);
        $this->assertStringContainsString('Nonaktif', $row['is_active']);
        $this->assertStringContainsString('Online', $row['procurement_mode']);
        $this->assertStringContainsString('Belum diatur', $row['operational_window']);
    }

    public function test_status_and_mode_filters_limit_rows(): void
    {
        $admin = $this->admin();
        $activeOnline = $this->supplier(['procurement_mode' => ProcurementMode::ONLINE->value, 'is_active' => true]);
        $activeOffline = $this->supplier(['procurement_mode' => ProcurementMode::OFFLINE->value, 'is_active' => true]);
        $inactiveOnline = $this->supplier(['procurement_mode' => ProcurementMode::ONLINE->value, 'is_active' => false]);

        $inactive = $this->rows($admin, ['status' => '0']);
        $this->assertTrue($inactive->has($inactiveOnline->id));
        $this->assertFalse($inactive->has($activeOnline->id));

        $online = $this->rows($admin, ['mode' => (string) ProcurementMode::ONLINE->value]);
        $this->assertTrue($online->has($activeOnline->id));
        $this->assertTrue($online->has($inactiveOnline->id));
        $this->assertFalse($online->has($activeOffline->id));

        $activeAndOnline = $this->rows($admin, ['status' => '1', 'mode' => (string) ProcurementMode::ONLINE->value]);
        $this->assertTrue($activeAndOnline->has($activeOnline->id));
        $this->assertFalse($activeAndOnline->has($inactiveOnline->id));
        $this->assertFalse($activeAndOnline->has($activeOffline->id));
    }

    public function test_search_matches_supplier_code(): void
    {
        $admin = $this->admin();
        $match = $this->supplier(['code' => 'UJI-CARI-777']);
        $other = $this->supplier(['code' => 'UJI-LAIN-1']);

        $rows = $this->rows($admin, ['search' => ['value' => 'UJI-CARI-777', 'regex' => 'false']]);

        $this->assertTrue($rows->has($match->id));
        $this->assertFalse($rows->has($other->id));
    }

    public function test_row_actions_follow_permissions(): void
    {
        $supplier = $this->supplier();

        $readOnly = $this->rows($this->admin(['read warehouse/supplier']))[$supplier->id]['action'];
        $this->assertStringContainsString(route('warehouse/supplier/show', $supplier->id), $readOnly);
        $this->assertStringNotContainsString(route('warehouse/supplier/edit', $supplier->id), $readOnly);
        $this->assertStringNotContainsString(route('warehouse/supplier/destroy', $supplier->id), $readOnly);

        $full = $this->rows($this->admin(['read warehouse/supplier', 'update warehouse/supplier', 'delete warehouse/supplier']))[$supplier->id]['action'];
        $this->assertStringContainsString(route('warehouse/supplier/edit', $supplier->id), $full);
        $this->assertStringContainsString(route('warehouse/supplier/destroy', $supplier->id), $full);
    }

    public function test_edit_modal_keeps_saved_values(): void
    {
        $supplier = $this->supplier([
            'code' => 'UJI-MODAL-1',
            'procurement_mode' => ProcurementMode::OFFLINE->value,
            'is_active' => false,
        ]);

        $this->actingAs($this->admin(['update warehouse/supplier']))
            ->get(route('warehouse/supplier/edit', $supplier->id))
            ->assertOk()
            ->assertSee('Edit Supplier')
            ->assertSee('value="UJI-MODAL-1"', false)
            ->assertSee('<option value="' . ProcurementMode::OFFLINE->value . '" selected>', false)
            ->assertSee('<option value="0" selected>Nonaktif</option>', false);
    }
}

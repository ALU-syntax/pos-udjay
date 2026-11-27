<?php

namespace Tests\Feature\Warehouse;

use App\Models\RawMaterials;
use App\Models\RawStorageType;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SatuanIndexTest extends TestCase
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

    private function satuan(array $attributes = []): Satuan
    {
        return Satuan::create(array_merge([
            'name' => 'Uji Satuan ' . uniqid(),
            'symbol' => null,
            'is_active' => 1,
        ], $attributes));
    }

    private function rawMaterial(Satuan $baseUnit): RawMaterials
    {
        $storage = RawStorageType::query()->forceCreate(['name' => 'Uji Simpan ' . uniqid()]);

        return RawMaterials::query()->forceCreate([
            'name' => 'Uji Bahan ' . uniqid(),
            'base_unit_id' => $baseUnit->id,
            'storage_type_id' => $storage->id,
            'is_active' => true,
        ]);
    }

    private function rows(User $actor, array $params = []): Collection
    {
        // Yajra menyimpan request sebagai singleton; dilupakan agar tiap panggilan membaca parameternya sendiri
        app()->forgetInstance('datatables.request');

        // Kolom dikirim seperti browser, karena pencarian global hanya berlaku pada kolom yang searchable
        $columns = collect(['name', 'raw_materials_count', 'is_active', 'action'])
            ->map(fn ($column) => [
                'data' => $column,
                'name' => $column,
                'searchable' => $column === 'name' ? 'true' : 'false',
                'orderable' => 'false',
                'search' => ['value' => '', 'regex' => 'false'],
            ])
            ->all();

        return collect($this->actingAs($actor)->getJson(route('library/satuan', array_merge([
            'draw' => 1, 'start' => 0, 'length' => 1000, 'columns' => $columns,
        ], $params)), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'))
            ->keyBy(fn ($row) => (int) $row['DT_RowId']);
    }

    public function test_index_page_renders_report_style(): void
    {
        $this->actingAs($this->admin(['create library/satuan']))
            ->get(route('library/satuan'))
            ->assertOk()
            ->assertSee('ob-page', false)
            ->assertSee('Master Satuan')
            ->assertSee('filter-status')
            ->assertSee(route('library/satuan/create'));
    }

    public function test_add_button_is_hidden_without_create_permission(): void
    {
        $this->actingAs($this->admin())
            ->get(route('library/satuan'))
            ->assertOk()
            ->assertDontSee(route('library/satuan/create'));
    }

    public function test_rows_escape_name_and_show_symbol_status_and_usage(): void
    {
        $admin = $this->admin();
        $used = $this->satuan(['name' => '<b>Uji Escape</b>', 'symbol' => 'ujk', 'is_active' => 0]);
        $unused = $this->satuan();
        $this->rawMaterial($used);
        $this->rawMaterial($used);
        $this->rawMaterial($used)->delete(); // soft delete tidak ikut dihitung

        $rows = $this->rows($admin);

        $this->assertStringContainsString('&lt;b&gt;Uji Escape&lt;/b&gt;', $rows[$used->id]['name']);
        $this->assertStringContainsString('ujk', $rows[$used->id]['name']);
        $this->assertStringContainsString('Nonaktif', $rows[$used->id]['is_active']);
        $this->assertStringContainsString('2 bahan baku', $rows[$used->id]['raw_materials_count']);

        $this->assertStringContainsString('Tanpa simbol', $rows[$unused->id]['name']);
        $this->assertStringContainsString('Aktif', $rows[$unused->id]['is_active']);
        $this->assertStringContainsString('Belum dipakai', $rows[$unused->id]['raw_materials_count']);
    }

    public function test_status_filter_limits_rows(): void
    {
        $admin = $this->admin();
        $active = $this->satuan(['is_active' => 1]);
        $inactive = $this->satuan(['is_active' => 0]);

        $onlyInactive = $this->rows($admin, ['status' => '0']);
        $this->assertTrue($onlyInactive->has($inactive->id));
        $this->assertFalse($onlyInactive->has($active->id));

        $onlyActive = $this->rows($admin, ['status' => '1']);
        $this->assertTrue($onlyActive->has($active->id));
        $this->assertFalse($onlyActive->has($inactive->id));
    }

    public function test_search_matches_symbol(): void
    {
        $admin = $this->admin();
        $match = $this->satuan(['symbol' => 'ujcr7']);
        $other = $this->satuan(['symbol' => 'ujln1']);

        $rows = $this->rows($admin, ['search' => ['value' => 'ujcr7', 'regex' => 'false']]);

        $this->assertTrue($rows->has($match->id));
        $this->assertFalse($rows->has($other->id));
    }

    public function test_edit_action_follows_permission(): void
    {
        $satuan = $this->satuan();

        $readOnly = $this->rows($this->admin(['read library/satuan']))[$satuan->id]['action'];
        $this->assertStringNotContainsString(route('library/satuan/edit', $satuan->id), $readOnly);

        $editor = $this->rows($this->admin(['update library/satuan']))[$satuan->id]['action'];
        $this->assertStringContainsString(route('library/satuan/edit', $satuan->id), $editor);
    }

    public function test_edit_modal_keeps_saved_values(): void
    {
        $satuan = $this->satuan(['symbol' => 'ujmd', 'is_active' => 0]);

        $this->actingAs($this->admin(['update library/satuan']))
            ->get(route('library/satuan/edit', $satuan->id))
            ->assertOk()
            ->assertSee('Edit Satuan')
            ->assertSee('ob-modal', false)
            ->assertSee('value="ujmd"', false)
            ->assertSee('<option value="0" selected>Nonaktif</option>', false);
    }
}

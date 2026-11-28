<?php

namespace Tests\Feature\Warehouse;

use App\Models\RawMaterials;
use App\Models\RawMaterialUnitConversions;
use App\Models\RawStorageType;
use App\Models\Role;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SatuanConversionTest extends TestCase
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

    private function satuan(string $name, ?string $symbol = null): Satuan
    {
        return Satuan::create(['name' => $name . ' ' . uniqid(), 'symbol' => $symbol, 'is_active' => 1]);
    }

    private function rawMaterial(string $name, Satuan $baseUnit): RawMaterials
    {
        $storage = RawStorageType::query()->forceCreate(['name' => 'Uji Simpan ' . uniqid()]);

        return RawMaterials::query()->forceCreate([
            'name' => $name . ' ' . uniqid(),
            'base_unit_id' => $baseUnit->id,
            'storage_type_id' => $storage->id,
            'is_active' => true,
        ]);
    }

    private function conversion(RawMaterials $material, Satuan $from, Satuan $to, float $multiplier, ?string $notes = null): RawMaterialUnitConversions
    {
        return RawMaterialUnitConversions::create([
            'raw_material_id' => $material->id,
            'from_unit_id' => $from->id,
            'to_unit_id' => $to->id,
            'multiplier' => $multiplier,
            'notes' => $notes,
        ]);
    }

    private function rows(User $actor, array $params = []): Collection
    {
        // Yajra menyimpan request sebagai singleton; dilupakan agar tiap panggilan membaca parameternya sendiri
        app()->forgetInstance('datatables.request');

        // Kolom dikirim seperti browser, termasuk nama kolom join untuk pencarian & urutan
        $columns = collect([
            'raw_material_name' => 'rm.name',
            'formula' => 'formula',
            'notes' => 'raw_material_unit_conversions.notes',
            'updated_at' => 'raw_material_unit_conversions.updated_at',
            'action' => 'action',
        ])->map(fn ($name, $data) => [
            'data' => $data,
            'name' => $name,
            'searchable' => in_array($data, ['updated_at', 'action'], true) ? 'false' : 'true',
            'orderable' => 'false',
            'search' => ['value' => '', 'regex' => 'false'],
        ])->values()->all();

        return collect($this->actingAs($actor)->getJson(route('library/satuan/conversions', array_merge([
            'draw' => 1, 'start' => 0, 'length' => 1000, 'columns' => $columns,
        ], $params)), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'))
            ->keyBy(fn ($row) => (int) $row['DT_RowId']);
    }

    public function test_index_page_renders_conversion_table_without_preloaded_rows(): void
    {
        $kg = $this->satuan('Uji Kilogram', 'kg');
        $box = $this->satuan('Uji Box', 'box');
        $this->conversion($this->rawMaterial('Uji Telur', $kg), $box, $kg, 15, 'Catatan halaman uji');

        $this->actingAs($this->admin())
            ->get(route('library/satuan'))
            ->assertOk()
            ->assertSee('conversion-table')
            ->assertSee('filter-material')
            // URL ajax ditulis sebagai JSON di script DataTable
            ->assertSee(str_replace('/', '\/', route('library/satuan/conversions')), false)
            // Baris konversi diambil lewat AJAX, tidak lagi ikut di HTML halaman
            ->assertDontSee('Catatan halaman uji');
    }

    public function test_rows_show_formula_base_unit_and_escape_name(): void
    {
        $kg = $this->satuan('Uji Kilogram', 'kg');
        $gram = $this->satuan('Uji Gram', 'g');
        $material = $this->rawMaterial('<b>Uji Tepung</b>', $kg);
        $conversion = $this->conversion($material, $kg, $gram, 1000, 'Takaran dapur');

        $row = $this->rows($this->admin())[$conversion->id];

        $this->assertStringContainsString('&lt;b&gt;Uji Tepung', $row['raw_material_name']);
        $this->assertStringContainsString('Satuan dasar: kg', $row['raw_material_name']);
        $this->assertStringContainsString('1 kg = 1.000 g', $row['formula']);
        $this->assertStringContainsString('Takaran dapur', $row['notes']);
    }

    public function test_decimal_multiplier_is_formatted_without_trailing_zeros(): void
    {
        $kg = $this->satuan('Uji Kilogram', 'kg');
        $pack = $this->satuan('Uji Pack');
        $conversion = $this->conversion($this->rawMaterial('Uji Gula', $kg), $pack, $kg, 0.25);

        $row = $this->rows($this->admin())[$conversion->id];

        // Satuan tanpa simbol memakai namanya
        $this->assertStringContainsString('1 ' . $pack->name . ' = 0,25 kg', $row['formula']);
    }

    public function test_trashed_raw_material_is_marked(): void
    {
        $kg = $this->satuan('Uji Kilogram', 'kg');
        $box = $this->satuan('Uji Box', 'box');
        $material = $this->rawMaterial('Uji Hapus', $kg);
        $conversion = $this->conversion($material, $box, $kg, 10);
        $material->delete();

        $row = $this->rows($this->admin())[$conversion->id];

        $this->assertStringContainsString('Bahan baku dihapus', $row['raw_material_name']);
    }

    public function test_material_filter_limits_rows(): void
    {
        $kg = $this->satuan('Uji Kilogram', 'kg');
        $box = $this->satuan('Uji Box', 'box');
        $egg = $this->rawMaterial('Uji Telur', $kg);
        $flour = $this->rawMaterial('Uji Terigu', $kg);
        $eggConversion = $this->conversion($egg, $box, $kg, 15);
        $flourConversion = $this->conversion($flour, $box, $kg, 25);

        $rows = $this->rows($this->admin(), ['raw_material' => (string) $egg->id]);

        $this->assertTrue($rows->has($eggConversion->id));
        $this->assertFalse($rows->has($flourConversion->id));
    }

    public function test_search_matches_material_and_unit(): void
    {
        $kg = $this->satuan('Uji Kilogram', 'kg');
        $crate = $this->satuan('Uji Peti', 'ujpeti');
        $box = $this->satuan('Uji Box', 'box');
        $byUnit = $this->conversion($this->rawMaterial('Uji Apel', $kg), $crate, $kg, 20);
        $byName = $this->conversion($this->rawMaterial('Uji Kelengkeng Cari', $kg), $box, $kg, 5);
        $other = $this->conversion($this->rawMaterial('Uji Jeruk', $kg), $box, $kg, 8);

        $unitRows = $this->rows($this->admin(), ['search' => ['value' => 'ujpeti', 'regex' => 'false']]);
        $this->assertTrue($unitRows->has($byUnit->id));
        $this->assertFalse($unitRows->has($other->id));

        $nameRows = $this->rows($this->admin(), ['search' => ['value' => 'Kelengkeng Cari', 'regex' => 'false']]);
        $this->assertTrue($nameRows->has($byName->id));
        $this->assertFalse($nameRows->has($other->id));
    }

    public function test_row_actions_follow_permissions(): void
    {
        $kg = $this->satuan('Uji Kilogram', 'kg');
        $box = $this->satuan('Uji Box', 'box');
        $conversion = $this->conversion($this->rawMaterial('Uji Telur', $kg), $box, $kg, 15);

        $none = $this->rows($this->admin(['read library/satuan']))[$conversion->id]['action'];
        $this->assertStringNotContainsString(route('library/satuan/conversions/edit', $conversion->id), $none);
        $this->assertStringNotContainsString(route('library/satuan/conversions/destroy', $conversion->id), $none);

        $full = $this->rows($this->admin(['update library/satuan', 'delete library/satuan']))[$conversion->id]['action'];
        $this->assertStringContainsString(route('library/satuan/conversions/edit', $conversion->id), $full);
        $this->assertStringContainsString(route('library/satuan/conversions/destroy', $conversion->id), $full);
    }

    public function test_destroy_returns_updated_stats(): void
    {
        $kg = $this->satuan('Uji Kilogram', 'kg');
        $box = $this->satuan('Uji Box', 'box');
        $conversion = $this->conversion($this->rawMaterial('Uji Telur', $kg), $box, $kg, 15);
        $before = RawMaterialUnitConversions::count();

        $this->actingAs($this->admin(['delete library/satuan']))
            ->deleteJson(route('library/satuan/conversions/destroy', $conversion->id))
            ->assertOk()
            ->assertJsonPath('message', 'Konversi bahan baku berhasil dihapus')
            ->assertJsonPath('data.conversion_total', $before - 1)
            ->assertJsonPath('data.refresh_conversions', true);

        $this->assertDatabaseMissing('raw_material_unit_conversions', ['id' => $conversion->id]);
    }

    public function test_edit_modal_keeps_saved_values(): void
    {
        $kg = $this->satuan('Uji Kilogram', 'kg');
        $box = $this->satuan('Uji Box', 'box');
        $material = $this->rawMaterial('Uji Telur', $kg);
        $conversion = $this->conversion($material, $box, $kg, 15, 'Catatan modal');

        $this->actingAs($this->admin(['update library/satuan']))
            ->get(route('library/satuan/conversions/edit', $conversion->id))
            ->assertOk()
            ->assertSee('Edit Konversi Bahan Baku')
            ->assertSee('ob-modal', false)
            ->assertSee('value="15"', false)
            ->assertSee('data-base-unit="kg"', false)
            ->assertSee('Catatan modal');
    }

    public function test_create_modal_hides_inactive_unit(): void
    {
        $active = $this->satuan('Uji Sat Aktif');
        $inactive = $this->satuan('Uji Sat Mati');
        $inactive->update(['is_active' => 0]);

        $this->actingAs($this->admin(['update library/satuan']))
            ->get(route('library/satuan/conversions/create'))
            ->assertOk()
            ->assertSee($active->name)
            ->assertDontSee($inactive->name);
    }

    public function test_edit_modal_keeps_saved_inactive_unit_marked(): void
    {
        $kg = $this->satuan('Uji Kilogram', 'kg');
        $box = $this->satuan('Uji Box Lama', 'box');
        $other = $this->satuan('Uji Sat Lain');
        $conversion = $this->conversion($this->rawMaterial('Uji Telur', $kg), $box, $kg, 15);
        $box->update(['is_active' => 0]);
        $other->update(['is_active' => 0]);

        $this->actingAs($this->admin(['update library/satuan']))
            ->get(route('library/satuan/conversions/edit', $conversion->id))
            ->assertOk()
            ->assertSee($box->name . ' (box) (Nonaktif)')
            ->assertDontSee($other->name);
    }

    public function test_store_rejects_inactive_unit(): void
    {
        $kg = $this->satuan('Uji Kilogram', 'kg');
        $box = $this->satuan('Uji Box Mati', 'box');
        $box->update(['is_active' => 0]);
        $material = $this->rawMaterial('Uji Telur', $kg);

        $this->actingAs($this->admin(['update library/satuan']))
            ->postJson(route('library/satuan/conversions/store'), [
                'raw_material_id' => $material->id,
                'from_unit_id' => $box->id,
                'to_unit_id' => $kg->id,
                'multiplier' => 15,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['from_unit_id' => 'Satuan asal tidak ditemukan atau sudah nonaktif.']);

        $this->assertFalse(RawMaterialUnitConversions::where('raw_material_id', $material->id)->exists());
    }

    public function test_update_keeps_saved_inactive_unit_but_rejects_other_inactive(): void
    {
        $kg = $this->satuan('Uji Kilogram', 'kg');
        $box = $this->satuan('Uji Box Lama', 'box');
        $other = $this->satuan('Uji Sat Lain');
        $material = $this->rawMaterial('Uji Telur', $kg);
        $conversion = $this->conversion($material, $box, $kg, 15);
        $box->update(['is_active' => 0]);
        $other->update(['is_active' => 0]);
        $admin = $this->admin(['update library/satuan']);
        $payload = ['raw_material_id' => $material->id, 'from_unit_id' => $box->id, 'to_unit_id' => $kg->id, 'multiplier' => 12];

        $this->actingAs($admin)
            ->putJson(route('library/satuan/conversions/update', $conversion->id), $payload)
            ->assertOk();
        $this->assertEquals(12, (float) $conversion->fresh()->multiplier);

        $this->actingAs($admin)
            ->putJson(route('library/satuan/conversions/update', $conversion->id), array_merge($payload, ['from_unit_id' => $other->id]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('from_unit_id');
        $this->assertSame($box->id, $conversion->fresh()->from_unit_id);
    }
}

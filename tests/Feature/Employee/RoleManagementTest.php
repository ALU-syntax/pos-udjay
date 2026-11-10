<?php

namespace Tests\Feature\Employee;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(array $permissions = []): User
    {
        // Role sendiri, bukan default factory (role = 1): setelah migrate:fresh dari test Auth,
        // role pertama yang dibuat test bisa mendapat id 1 dan admin ikut terhitung sebagai pemakainya
        $admin = User::factory()->create(['role' => $this->role('Uji Admin')->id]);

        foreach ($permissions as $name) {
            $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }

        return $admin;
    }

    private function role(string $name): Role
    {
        return Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    private function rows(User $actor): \Illuminate\Support\Collection
    {
        return collect($this->actingAs($actor)->getJson(route('employee/roles', [
            'draw' => 1, 'start' => 0, 'length' => 500,
        ]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'))
            ->keyBy(fn ($row) => (int) $row['DT_RowId']);
    }

    public function test_index_page_renders_report_style(): void
    {
        $this->actingAs($this->admin(['create employee/roles']))
            ->get(route('employee/roles'))
            ->assertOk()
            ->assertSee('Daftar Role')
            ->assertSee(route('employee/roles/create'));
    }

    public function test_add_button_is_hidden_without_create_permission(): void
    {
        $this->actingAs($this->admin())
            ->get(route('employee/roles'))
            ->assertOk()
            ->assertDontSee(route('employee/roles/create'));
    }

    public function test_rows_show_usage_counts_and_lock_system_roles(): void
    {
        $admin = $this->admin(['update employee/roles', 'delete employee/roles', 'update employee/hak-akses']);
        $unused = $this->role('<i>Uji Kosong</i>');
        $used = $this->role('Uji Dipakai');
        $kasir = $this->role('kasir');
        User::factory()->count(2)->create(['role' => $used->id]);
        User::factory()->create(['role' => $used->id, 'deleted' => 1]);

        $rows = $this->rows($admin);

        $this->assertStringContainsString('&lt;i&gt;Uji Kosong&lt;/i&gt;', $rows[$unused->id]['name']);
        $this->assertStringContainsString('Belum dipakai', $rows[$unused->id]['users_count']);
        $this->assertStringContainsString(route('employee/roles/destroy', $unused->id), $rows[$unused->id]['action']);

        // User yang sudah dihapus tidak dihitung
        $this->assertStringContainsString('2 user', $rows[$used->id]['users_count']);
        $this->assertStringNotContainsString(route('employee/roles/destroy', $used->id), $rows[$used->id]['action']);

        $this->assertStringContainsString('Role sistem', $rows[$kasir->id]['name']);
        $this->assertStringNotContainsString(route('employee/roles/edit', $kasir->id), $rows[$kasir->id]['action']);
        $this->assertStringNotContainsString(route('employee/roles/destroy', $kasir->id), $rows[$kasir->id]['action']);
        $this->assertStringContainsString(route('employee/hak-akses/role/edit', $kasir->id), $rows[$kasir->id]['action']);
    }

    public function test_store_always_uses_web_guard(): void
    {
        $this->actingAs($this->admin())
            ->post(route('employee/roles/store'), ['name' => 'Uji Supervisor', 'guard_name' => 'API'])
            ->assertRedirect(route('employee/roles'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('roles', ['name' => 'Uji Supervisor', 'guard_name' => 'web']);
    }

    public function test_store_rejects_duplicate_and_system_names(): void
    {
        $admin = $this->admin();
        $this->role('Uji Duplikat');

        $this->actingAs($admin)
            ->post(route('employee/roles/store'), ['name' => 'Uji Duplikat'])
            ->assertSessionHasErrors('name');

        $this->actingAs($admin)
            ->post(route('employee/roles/store'), ['name' => 'Super Admin'])
            ->assertSessionHasErrors('name');
    }

    public function test_update_renames_role(): void
    {
        $role = $this->role('Uji Lama');

        $this->actingAs($this->admin())
            ->post(route('employee/roles/update', $role->id), ['name' => 'Uji Baru'])
            ->assertRedirect(route('employee/roles'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Uji Baru', $role->fresh()->name);
    }

    public function test_system_role_cannot_be_edited_or_deleted(): void
    {
        $admin = $this->admin();
        $kasir = $this->role('kasir');

        $this->actingAs($admin)
            ->get(route('employee/roles/edit', $kasir->id))
            ->assertRedirect(route('employee/roles'))
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->post(route('employee/roles/update', $kasir->id), ['name' => 'Uji Kasir Baru'])
            ->assertRedirect(route('employee/roles'));
        $this->assertSame('kasir', strtolower($kasir->fresh()->name));

        $this->actingAs($admin)
            ->deleteJson(route('employee/roles/destroy', $kasir->id))
            ->assertUnprocessable();
        $this->assertNotSoftDeleted($kasir);
    }

    public function test_role_in_use_cannot_be_deleted_but_unused_role_can(): void
    {
        $admin = $this->admin();
        $used = $this->role('Uji Masih Dipakai');
        $unused = $this->role('Uji Bebas');
        User::factory()->create(['role' => $used->id]);

        $this->actingAs($admin)
            ->deleteJson(route('employee/roles/destroy', $used->id))
            ->assertUnprocessable()
            ->assertJsonPath('status', 'error');
        $this->assertNotSoftDeleted($used);

        $this->actingAs($admin)
            ->deleteJson(route('employee/roles/destroy', $unused->id))
            ->assertOk()
            ->assertJsonPath('status', 'success');
        $this->assertSoftDeleted($unused);
    }
}

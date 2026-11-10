<?php

namespace Tests\Feature\Employee;

use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HakAksesTest extends TestCase
{
    use DatabaseTransactions;

    private function role(string $name): Role
    {
        return Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    private function permission(string $name): Permission
    {
        return Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    private function admin(array $permissions = []): User
    {
        // Role sendiri agar tidak bentrok dengan role uji yang kebetulan mendapat id 1
        $admin = User::factory()->create(['role' => $this->role('Uji Admin Akses')->id]);
        foreach ($permissions as $name) {
            $admin->givePermissionTo($this->permission($name));
        }

        return $admin;
    }

    /** Menu utama + satu submenu, masing-masing dengan permission read/update */
    private function menuTree(): array
    {
        $main = Menu::create(['name' => 'Uji Laporan', 'url' => 'uji-laporan', 'orders' => 1]);
        $sub = Menu::create(['name' => 'Uji Penjualan', 'url' => 'uji-laporan/penjualan', 'main_menu_id' => $main->id, 'orders' => 1]);

        $mainRead = $this->permission('read uji-laporan');
        $subRead = $this->permission('read uji-laporan/penjualan');
        $subUpdate = $this->permission('update uji-laporan/penjualan');
        $main->permissions()->attach($mainRead);
        $sub->permissions()->attach([$subRead->id, $subUpdate->id]);

        return compact('main', 'sub', 'mainRead', 'subRead', 'subUpdate');
    }

    private function rows(User $actor, array $query = []): \Illuminate\Support\Collection
    {
        return collect($this->actingAs($actor)->getJson(route('employee/hak-akses', array_merge([
            'draw' => 1, 'start' => 0, 'length' => 500,
        ], $query)), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'))
            ->keyBy(fn ($row) => (int) $row['DT_RowId']);
    }

    public function test_index_shows_role_cards_and_locks_super_admin(): void
    {
        $admin = $this->admin(['update employee/hak-akses']);
        $supervisor = $this->role('Uji Supervisor');
        $superAdmin = $this->role('super admin');
        User::factory()->count(4)->create(['role' => $supervisor->id]);

        $this->actingAs($admin)
            ->get(route('employee/hak-akses'))
            ->assertOk()
            ->assertSee('Hak Akses Role')
            ->assertSee('Akses Tambahan User')
            ->assertSee(route('employee/hak-akses/role/edit', $supervisor->id))
            ->assertDontSee(route('employee/hak-akses/role/edit', $superAdmin->id))
            ->assertSee('+1');
    }

    public function test_user_rows_count_direct_permissions_and_survive_missing_role(): void
    {
        $admin = $this->admin();
        $role = $this->role('Uji Kasir Akses');
        $withExtra = User::factory()->create(['role' => $role->id, 'name' => '<b>Budi</b>']);
        $withExtra->givePermissionTo($this->permission('read uji-tambahan'));
        $orphan = User::factory()->create(['role' => 999999]);
        $deleted = User::factory()->create(['role' => $role->id, 'deleted' => 1]);

        $rows = $this->rows($admin);

        $this->assertStringContainsString('&lt;b&gt;Budi&lt;/b&gt;', $rows[$withExtra->id]['name']);
        $this->assertStringContainsString('+1 akses', $rows[$withExtra->id]['permissions_count']);
        $this->assertStringContainsString('Tanpa role', $rows[$orphan->id]['role_name']);
        $this->assertStringContainsString('Mengikuti role', $rows[$orphan->id]['permissions_count']);
        $this->assertFalse($rows->has($deleted->id));

        $filtered = $this->rows($admin, ['role_id' => $role->id]);
        $this->assertTrue($filtered->has($withExtra->id));
        $this->assertFalse($filtered->has($orphan->id));
    }

    public function test_role_edit_page_checks_current_permissions(): void
    {
        $tree = $this->menuTree();
        $role = $this->role('Uji Gudang');
        $role->givePermissionTo($tree['subRead']);

        $this->actingAs($this->admin())
            ->get(route('employee/hak-akses/role/edit', $role->id))
            ->assertOk()
            ->assertSee('Hak Akses Role: Uji Gudang')
            ->assertSee('Uji Penjualan')
            ->assertSee('value="read uji-laporan/penjualan" data-group="menu-'.$tree['main']->id.'" data-row="sub-'.$tree['sub']->id.'" checked', false)
            ->assertSee('value="update uji-laporan/penjualan" data-group="menu-'.$tree['main']->id.'" data-row="sub-'.$tree['sub']->id.'" >', false);
    }

    public function test_role_permissions_are_synced(): void
    {
        $tree = $this->menuTree();
        $role = $this->role('Uji Gudang');
        $role->givePermissionTo($tree['mainRead']);

        $this->actingAs($this->admin())
            ->post(route('employee/hak-akses/role/update', $role->id), [
                'permissions' => [$tree['subRead']->name, $tree['subUpdate']->name],
            ])
            ->assertRedirect(route('employee/hak-akses'))
            ->assertSessionHas('success');

        $this->assertEqualsCanonicalizing(
            [$tree['subRead']->name, $tree['subUpdate']->name],
            $role->fresh()->permissions->pluck('name')->all()
        );
    }

    public function test_unknown_permission_is_rejected(): void
    {
        $role = $this->role('Uji Gudang');

        $this->actingAs($this->admin())
            ->post(route('employee/hak-akses/role/update', $role->id), ['permissions' => ['hapus semua data']])
            ->assertSessionHasErrors('permissions.0');
    }

    public function test_super_admin_role_cannot_be_configured(): void
    {
        $tree = $this->menuTree();
        $superAdmin = $this->role('super admin');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('employee/hak-akses/role/edit', $superAdmin->id))
            ->assertRedirect(route('employee/hak-akses'))
            ->assertSessionHas('error');

        $this->actingAs($admin)
            ->post(route('employee/hak-akses/role/update', $superAdmin->id), ['permissions' => [$tree['mainRead']->name]])
            ->assertRedirect(route('employee/hak-akses'));

        $this->assertCount(0, $superAdmin->fresh()->permissions);
    }

    public function test_user_page_locks_permissions_from_role(): void
    {
        $tree = $this->menuTree();
        $role = $this->role('Uji Gudang');
        $role->givePermissionTo($tree['subRead']);
        $user = User::factory()->create(['role' => $role->id]);
        $user->assignRole($role->name);
        $user->givePermissionTo($tree['subUpdate']);

        $response = $this->actingAs($this->admin())
            ->get(route('employee/hak-akses/user/edit', $user->id))
            ->assertOk()
            ->assertSee('Role saat ini: <strong>Uji Gudang</strong>', false)
            ->assertSee('ob-perm--inherited', false)
            ->assertSee('value="update uji-laporan/penjualan" data-group="menu-'.$tree['main']->id.'" data-row="sub-'.$tree['sub']->id.'" checked', false);

        // Permission dari role tidak boleh ikut terkirim sebagai permission langsung
        $response->assertDontSee('name="permissions[]" value="read uji-laporan/penjualan"', false);
    }

    public function test_user_update_only_syncs_direct_permissions(): void
    {
        $tree = $this->menuTree();
        $role = $this->role('Uji Gudang');
        $role->givePermissionTo($tree['subRead']);
        $user = User::factory()->create(['role' => $role->id]);
        $user->assignRole($role->name);
        $user->givePermissionTo($tree['subUpdate']);

        $this->actingAs($this->admin())
            ->post(route('employee/hak-akses/user/update', $user->id), ['permissions' => [$tree['mainRead']->name]])
            ->assertRedirect(route('employee/hak-akses'))
            ->assertSessionHas('success');

        $user = $user->fresh();
        $this->assertSame([$tree['mainRead']->name], $user->getDirectPermissions()->pluck('name')->all());
        $this->assertTrue($user->hasPermissionTo($tree['subRead']->name));
        $this->assertSame(0, $role->fresh()->permissions()->where('name', $tree['mainRead']->name)->count());
    }
}

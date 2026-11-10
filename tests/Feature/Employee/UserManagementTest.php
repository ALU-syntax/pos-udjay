<?php

namespace Tests\Feature\Employee;

use App\Models\Outlets;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(array $permissions = []): User
    {
        $admin = User::factory()->create();

        foreach ($permissions as $name) {
            $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }

        return $admin;
    }

    private function role(string $name): Role
    {
        return Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }

    private function outlet(string $suffix): Outlets
    {
        return Outlets::create(['name' => 'User Outlet '.$suffix, 'address' => 'Test address', 'phone' => '0800000000']);
    }

    private function member(Role $role, array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['role' => $role->id], $attributes));
        $user->assignRole($role->name);

        return $user;
    }

    private function rows(User $actor, array $query = []): \Illuminate\Support\Collection
    {
        return collect($this->actingAs($actor)->getJson(route('employee/user', array_merge([
            'draw' => 1, 'start' => 0, 'length' => 100,
        ], $query)), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data'))
            ->keyBy(fn ($row) => (int) $row['DT_RowId']);
    }

    private function payload(Role $role, array $outletIds, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Pengguna Baru',
            'username' => 'pengguna_'.uniqid(),
            'password' => 'rahasia123',
            'email' => '',
            'status' => '1',
            'role' => $role->id,
            'outlet_id' => array_map('strval', $outletIds),
            'pin' => '',
        ], $overrides);
    }

    public function test_index_page_renders_report_style_with_filters(): void
    {
        $role = $this->role('Supervisor Uji');

        $this->actingAs($this->admin(['create employee/users']))
            ->get(route('employee/user'))
            ->assertOk()
            ->assertSee('Daftar User')
            ->assertSee('id="role-filter"', false)
            ->assertSee('id="status-filter"', false)
            ->assertSee($role->name)
            ->assertSee(route('employee/user/create'));
    }

    public function test_add_button_is_hidden_without_create_permission(): void
    {
        $this->actingAs($this->admin())
            ->get(route('employee/user'))
            ->assertOk()
            ->assertDontSee(route('employee/user/create'));
    }

    public function test_rows_are_filtered_by_role_and_status_and_escaped(): void
    {
        $admin = $this->admin(['update employee/users', 'delete employee/users']);
        $kasir = $this->role('Kasir');
        $supervisor = $this->role('Supervisor Uji');
        $outlet = $this->outlet('Rows');

        $active = $this->member($kasir, ['name' => '<b>Andi</b>', 'status' => '1', 'outlet_id' => json_encode([(string) $outlet->id])]);
        $inactive = $this->member($kasir, ['status' => '0']);
        $other = $this->member($supervisor);
        $deleted = $this->member($kasir, ['deleted' => 1]);

        $rows = $this->rows($admin, ['role_id' => $kasir->id]);
        $this->assertTrue($rows->has($active->id));
        $this->assertTrue($rows->has($inactive->id));
        $this->assertFalse($rows->has($other->id));
        $this->assertFalse($rows->has($deleted->id));

        $row = $rows[$active->id];
        $this->assertStringContainsString('&lt;b&gt;Andi&lt;/b&gt;', $row['name']);
        $this->assertStringContainsString('Kasir', $row['role_name']);
        $this->assertStringContainsString($outlet->name, $row['outlets']);
        $this->assertStringContainsString('Aktif', $row['status']);
        $this->assertStringContainsString(route('employee/user/edit', $active->id), $row['action']);

        $inactiveRows = $this->rows($admin, ['role_id' => $kasir->id, 'status' => '0']);
        $this->assertTrue($inactiveRows->has($inactive->id));
        $this->assertFalse($inactiveRows->has($active->id));
    }

    public function test_row_without_existing_role_does_not_break_the_table(): void
    {
        $admin = $this->admin();
        $orphan = User::factory()->create(['role' => 999999]);

        $rows = $this->rows($admin);

        $this->assertStringContainsString('Tanpa role', $rows[$orphan->id]['role_name']);
    }

    public function test_store_accepts_empty_email_and_pin_and_keeps_legacy_outlet_format(): void
    {
        $role = $this->role('Supervisor Uji');
        $outlets = [$this->outlet('Store A'), $this->outlet('Store B')];
        $payload = $this->payload($role, [$outlets[0]->id, $outlets[1]->id]);

        $this->actingAs($this->admin())
            ->post(route('employee/user/store'), $payload)
            ->assertRedirect(route('employee/user'))
            ->assertSessionHasNoErrors();

        $user = User::where('username', $payload['username'])->firstOrFail();
        $this->assertNull($user->email);
        $this->assertNull($user->pin);
        $this->assertTrue($user->hasRole($role->name));
        // Kode lama membaca kolom ini dengan json_decode(), jadi formatnya harus tetap
        $this->assertEqualsCanonicalizing([$outlets[0]->id, $outlets[1]->id], array_map('intval', json_decode($user->outlet_id)));
    }

    public function test_kasir_can_only_be_assigned_to_one_outlet(): void
    {
        $kasir = $this->role('Kasir');
        $payload = $this->payload($kasir, [$this->outlet('Kasir A')->id, $this->outlet('Kasir B')->id]);

        $this->actingAs($this->admin())
            ->post(route('employee/user/store'), $payload)
            ->assertSessionHasErrors('outlet_id');

        $this->assertDatabaseMissing('users', ['username' => $payload['username']]);
    }

    public function test_store_rejects_pin_that_is_not_six_digits(): void
    {
        $role = $this->role('Supervisor Uji');

        $this->actingAs($this->admin())
            ->post(route('employee/user/store'), $this->payload($role, [$this->outlet('Pin')->id], ['pin' => '12ab']))
            ->assertSessionHasErrors('pin');
    }

    public function test_edit_page_preselects_current_status_role_and_outlet(): void
    {
        $role = $this->role('Supervisor Uji');
        $outlet = $this->outlet('Edit');
        $user = $this->member($role, ['status' => '0', 'outlet_id' => json_encode([(string) $outlet->id])]);

        $this->actingAs($this->admin())
            ->get(route('employee/user/edit', $user->id))
            ->assertOk()
            ->assertSee('<option value="0" selected', false)
            ->assertSee('<option value="'.$outlet->id.'" selected', false)
            ->assertSee('data-kasir="0"', false);
    }

    public function test_update_keeps_password_and_pin_when_left_blank(): void
    {
        $role = $this->role('Supervisor Uji');
        $outlet = $this->outlet('Update');
        $user = $this->member($role, ['pin' => Hash::make('123456')]);
        $oldPassword = $user->password;
        $oldPin = $user->pin;

        $this->actingAs($this->admin())
            ->post(route('employee/user/update', $user->id), $this->payload($role, [$outlet->id], [
                'name' => 'Nama Diubah',
                'username' => $user->username,
                'status' => '0',
                'password' => '',
            ]))
            ->assertRedirect(route('employee/user'))
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('Nama Diubah', $user->name);
        $this->assertSame('0', (string) $user->status);
        $this->assertSame($oldPassword, $user->password);
        $this->assertSame($oldPin, $user->pin);
    }

    public function test_update_rejects_username_used_by_another_user(): void
    {
        $role = $this->role('Supervisor Uji');
        $taken = $this->member($role);
        $user = $this->member($role);

        $this->actingAs($this->admin())
            ->post(route('employee/user/update', $user->id), $this->payload($role, [$this->outlet('Unique')->id], [
                'username' => $taken->username,
            ]))
            ->assertSessionHasErrors('username');
    }

    public function test_destroy_deactivates_user_but_refuses_own_account(): void
    {
        $admin = $this->admin(['delete employee/users']);
        $user = $this->member($this->role('Supervisor Uji'));

        $this->actingAs($admin)
            ->deleteJson(route('employee/user/destroy', $user->id))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $user->refresh();
        $this->assertSame(1, (int) $user->deleted);
        $this->assertSame('0', (string) $user->status);

        $this->actingAs($admin)
            ->deleteJson(route('employee/user/destroy', $admin->id))
            ->assertUnprocessable();

        $this->assertNotSame(1, (int) $admin->fresh()->deleted);
    }
}

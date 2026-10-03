<?php

namespace Tests\Feature\OrderTable;

use App\Models\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission;

class OrderTableHakAksesTest extends OrderTableTestCase
{
    public function test_role_permissions_assigned_through_hak_akses_are_used_by_order_table_authorization(): void
    {
        $outlet = $this->outlet('Role Access');
        $operator = $this->userFor($outlet);
        $targetUser = User::factory()->create(['outlet_id' => [$outlet->id]]);
        $role = Role::create(['name' => 'Order Table Operator', 'guard_name' => 'web']);
        $targetUser->assignRole($role);
        $permission = Permission::firstOrCreate([
            'name' => 'read order-table/dining-tables',
            'guard_name' => 'web',
        ]);

        $this->actingAs($operator)->post(route('employee/hak-akses/role/update', $role->id), [
            'permissions' => [$permission->name],
        ])->assertRedirect(route('employee/hak-akses'));

        $this->assertTrue($role->fresh()->hasPermissionTo($permission->name));
        $this->actingAs($targetUser)
            ->get(route('order-table/dining-tables'))
            ->assertOk();
    }

    public function test_direct_user_permissions_assigned_through_hak_akses_are_available_immediately(): void
    {
        $outlet = $this->outlet('User Access');
        $operator = $this->userFor($outlet);
        $targetUser = User::factory()->create(['outlet_id' => [$outlet->id]]);
        $permission = Permission::firstOrCreate([
            'name' => 'read order-table/payment-methods',
            'guard_name' => 'web',
        ]);

        $this->actingAs($operator)->post(route('employee/hak-akses/user/update', $targetUser->id), [
            'permissions' => [$permission->name],
        ])->assertRedirect(route('employee/hak-akses'));

        $this->assertTrue($targetUser->fresh()->hasDirectPermission($permission->name));
        $this->actingAs($targetUser)
            ->get(route('order-table/payment-methods'))
            ->assertOk();
    }
}

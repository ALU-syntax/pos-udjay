<?php

namespace Tests\Feature\OrderTable;

use App\Models\Menu;
use App\Models\Role;
use Database\Seeders\OrderTableMenuSeeder;

class OrderTableMenuSeederTest extends OrderTableTestCase
{
    public function test_seeder_registers_permissions_used_by_hak_akses_and_order_table_routes(): void
    {
        $admin = Role::query()->whereRaw('LOWER(name) = ?', ['admin'])->first()
            ?? Role::create(['name' => 'Admin', 'guard_name' => 'web']);

        $this->seed(OrderTableMenuSeeder::class);

        $diningMenu = Menu::query()
            ->where('url', 'order-table/dining-tables')
            ->with('permissions')
            ->firstOrFail();

        $expected = [
            'create order-table/dining-tables',
            'read order-table/dining-tables',
            'update order-table/dining-tables',
            'delete order-table/dining-tables',
            'rotate order-table/dining-tables',
            'download order-table/dining-tables',
            'print order-table/dining-tables',
        ];

        $this->assertEqualsCanonicalizing($expected, $diningMenu->permissions->pluck('name')->all());
        foreach ($expected as $permission) {
            $this->assertTrue($admin->fresh()->hasPermissionTo($permission));
        }
    }
}

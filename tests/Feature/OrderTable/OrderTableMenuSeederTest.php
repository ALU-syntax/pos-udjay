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

        $expectedByMenu = [
            'order-table' => ['read order-table'],
            'order-table/outlet-settings' => ['create order-table/outlet-settings', 'read order-table/outlet-settings', 'update order-table/outlet-settings'],
            'order-table/dining-tables' => ['create order-table/dining-tables', 'read order-table/dining-tables', 'update order-table/dining-tables', 'delete order-table/dining-tables', 'rotate order-table/dining-tables', 'download order-table/dining-tables', 'print order-table/dining-tables'],
            'order-table/payment-methods' => ['create order-table/payment-methods', 'read order-table/payment-methods', 'update order-table/payment-methods'],
            'order-table/vouchers' => ['create order-table/vouchers', 'read order-table/vouchers', 'update order-table/vouchers'],
            'order-table/voucher-redemptions' => ['read order-table/voucher-redemptions'],
            'order-table/banners' => ['create order-table/banners', 'read order-table/banners', 'update order-table/banners'],
            'order-table/orders' => ['read order-table/orders', 'serve order-table/orders', 'cancel order-table/orders'],
            'order-table/sessions' => ['read order-table/sessions', 'close order-table/sessions'],
            'order-table/payments' => ['read order-table/payments'],
            'order-table/bridge' => ['read order-table/bridge', 'retry order-table/bridge'],
        ];

        foreach ($expectedByMenu as $url => $expected) {
            $menu = Menu::query()->where('url', $url)->with('permissions')->firstOrFail();
            $this->assertEqualsCanonicalizing($expected, $menu->permissions->pluck('name')->all(), $url);
            foreach ($expected as $permission) {
                $this->assertTrue($admin->fresh()->hasPermissionTo($permission), $permission);
            }
        }
    }
}

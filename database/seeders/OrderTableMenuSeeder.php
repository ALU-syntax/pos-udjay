<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;

class OrderTableMenuSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::query()->whereRaw('LOWER(name) = ?', ['admin'])->first();
        $roles = $adminRole ? [$adminRole->name] : null;

        $mainMenu = Menu::updateOrCreate(
            ['url' => 'order-table'],
            ['name' => 'Order Table', 'category' => 'ORDER TABLE', 'icon' => 'fa-utensils']
        );
        $this->syncMenuPermissions($mainMenu, ['read order-table'], $roles);

        $menus = [
            'order-table/outlet-settings' => [
                'name' => 'Pengaturan Outlet',
                'permissions' => ['create ', 'read ', 'update '],
            ],
            'order-table/dining-tables' => [
                'name' => 'Meja & QR',
                'permissions' => ['create ', 'read ', 'update ', 'delete ', 'rotate ', 'download ', 'print '],
            ],
            'order-table/payment-methods' => [
                'name' => 'Metode Pembayaran',
                'permissions' => ['create ', 'read ', 'update '],
            ],
            'order-table/vouchers' => [
                'name' => 'Voucher',
                'permissions' => ['create ', 'read ', 'update '],
            ],
            'order-table/voucher-redemptions' => [
                'name' => 'Redemption Voucher',
                'permissions' => ['read '],
            ],
            'order-table/banners' => [
                'name' => 'Banner',
                'permissions' => ['create ', 'read ', 'update '],
            ],
            'order-table/orders' => [
                'name' => 'Monitor Order',
                'permissions' => ['read ', 'serve ', 'cancel '],
            ],
            'order-table/sessions' => [
                'name' => 'Monitor Sesi',
                'permissions' => ['read ', 'close '],
            ],
            'order-table/payments' => [
                'name' => 'Monitor Pembayaran',
                'permissions' => ['read '],
            ],
            'order-table/bridge' => [
                'name' => 'Monitor Bridge',
                'permissions' => ['read ', 'retry '],
            ],
        ];

        foreach ($menus as $url => $definition) {
            $subMenu = $mainMenu->subMenus()->updateOrCreate(
                ['url' => $url],
                ['name' => $definition['name'], 'category' => $mainMenu->category]
            );
            $this->syncMenuPermissions(
                $subMenu,
                array_map(fn (string $action) => $action.$url, $definition['permissions']),
                $roles
            );
        }

        Cache::forget('menus');
        Cache::forget('urlMenu');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function syncMenuPermissions(Menu $menu, array $permissionNames, ?array $roles): void
    {
        $permissionIds = collect($permissionNames)->map(function (string $name) use ($roles) {
            $permission = Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['name' => $name, 'guard_name' => 'web']
            );

            if ($roles) {
                $permission->assignRole($roles);
            }

            return $permission->id;
        });

        $menu->permissions()->sync($permissionIds);
    }
}

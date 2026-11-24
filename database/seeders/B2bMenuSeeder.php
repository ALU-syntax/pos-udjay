<?php

namespace Database\Seeders;

use App\Models\Config;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\Role;
use App\Services\B2b\B2bSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;

/**
 * Menu, permission, dan config Kasir B2B (fase 1–2). Aman dijalankan berulang:
 * php artisan db:seed --class=B2bMenuSeeder
 *
 * Menu pembayaran dan pengaturan B2B ditambahkan saat fiturnya dibangun.
 */
class B2bMenuSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::query()->whereRaw('LOWER(name) = ?', ['admin'])->first();
        $roles = $adminRole ? [$adminRole->name] : null;

        $mainMenu = Menu::updateOrCreate(
            ['url' => 'b2b'],
            ['name' => 'Kasir B2B', 'category' => 'B2B', 'icon' => 'fa-handshake']
        );
        $this->syncMenuPermissions($mainMenu, ['read b2b'], $roles);

        $menus = [
            'b2b/cashier' => [
                'name' => 'Kasir B2B',
                'permissions' => ['read ', 'create '],
            ],
            'b2b/orders' => [
                'name' => 'Pesanan',
                // cancel = batalkan pesanan dikonfirmasi; cancel-completed = batalkan pesanan yang sudah selesai
                'permissions' => ['read ', 'complete ', 'cancel ', 'cancel-completed '],
            ],
            'b2b/invoices' => [
                'name' => 'Invoice',
                // pay = catat dan batalkan pelunasan invoice tempo
                'permissions' => ['read ', 'pay '],
            ],
            'b2b/reminders' => [
                'name' => 'Reminder',
                // send = kirim ulang / kirim manual; toggle = saklar darurat per channel;
                // manage-whatsapp = hubungkan / putuskan nomor WhatsApp penagihan dan kirim pesan uji
                'permissions' => ['read ', 'send ', 'toggle ', 'manage-whatsapp '],
            ],
            'b2b/reports' => [
                'name' => 'Laporan',
                'permissions' => ['read '],
            ],
            'b2b/customers' => [
                'name' => 'Customer',
                'permissions' => ['create ', 'read ', 'update ', 'delete '],
            ],
            'b2b/contracts' => [
                'name' => 'Kontrak',
                'permissions' => ['create ', 'read ', 'update ', 'delete ', 'terminate '],
            ],
        ];

        $order = 1;
        foreach ($menus as $url => $definition) {
            $subMenu = $mainMenu->subMenus()->updateOrCreate(
                ['url' => $url],
                ['name' => $definition['name'], 'category' => $mainMenu->category, 'orders' => $order++]
            );
            $this->syncMenuPermissions(
                $subMenu,
                array_map(fn (string $action) => $action.$url, $definition['permissions']),
                $roles
            );
        }

        // firstOrCreate: nilai yang sudah diubah admin tidak ter-reset. Tanpa baris ini kode memakai cadangan 7 hari.
        Config::firstOrCreate(
            ['name' => 'b2b.default_payment_term_days'],
            ['type' => 'integer', 'value' => '7', 'description' => 'Saran jatuh tempo (hari) di Kasir B2B; kasir tetap bisa mengganti tanggalnya'],
        );

        // Identitas cetak invoice + kode di nomor invoice; firstOrCreate supaya isi yang sudah diubah tidak tertimpa.
        foreach (B2bSettings::INVOICE_PROFILE_DEFAULTS as $name => [$value, $description]) {
            Config::firstOrCreate(['name' => $name], ['type' => 'string', 'value' => $value, 'description' => $description]);
        }
        Config::firstOrCreate(
            ['name' => B2bSettings::INVOICE_NUMBER_CODE_KEY],
            ['type' => 'string', 'value' => B2bSettings::FALLBACK_INVOICE_NUMBER_CODE, 'description' => 'Kode di nomor invoice, mis. 07/INV/BGR/11/2026'],
        );

        // Saklar darurat reminder per channel; firstOrCreate supaya posisi saklar yang sudah diubah tidak ter-reset.
        foreach (B2bSettings::REMINDER_SWITCH_KEYS as $channel => $key) {
            Config::firstOrCreate(
                ['name' => $key],
                ['type' => 'boolean', 'value' => '1', 'description' => 'Saklar darurat reminder B2B '.ucfirst($channel).' (0 = hentikan semua kiriman)'],
            );
        }

        // Master bank & e-wallet untuk rekening customer (tabel umum account_providers).
        $this->call(AccountProviderSeeder::class);

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

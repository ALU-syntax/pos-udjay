<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use App\Traits\HasMenuPermission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;


class MenuSeeder extends Seeder
{
    use HasMenuPermission;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Cache::forget('menus');
        /** 
         * @var Menu $mm
         */

        //  KONFIGURASI
        $mm = Menu::firstOrCreate(['url' => 'konfigurasi'], ['name' => 'Konfigurasi', 'category' => 'KONFIGURASI', 'icon' => 'fa-cogs']);
        $this->attachMenuPermission($mm, ['read '], ['admin']);

        $sm = $mm->subMenus()->create(['name' => 'Menu', 'url' => $mm->url . '/menu', 'category' => $mm->category]);
        $this->attachMenuPermission($sm, ['create ', 'read ', 'update ', 'delete ', 'sort '], ['admin']);

        $sm = $mm->subMenus()->create(['name' => 'Role', 'url' => $mm->url . '/roles', 'category' => $mm->category]);
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->create(['name' => 'Permission', 'url' => $mm->url . '/permissions', 'category' => $mm->category]);
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->create(['name' => 'Hak Akses', 'url' => $mm->url . '/hak-akses', 'category' => $mm->category]);
        $this->attachMenuPermission($sm, null, ['admin']);

        // END KONFIGURASI

        // USERS
        $mm = Menu::firstOrCreate(['url' => 'users'], ['name' => 'Users', 'category' => 'USERS', 'icon' => 'bx-user']);
        $this->attachMenuPermission($mm, null, ['admin']);
        // END USERS
    }
}

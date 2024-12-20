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

        // LIBRARY
        $mm = Menu::firstOrCreate(['url' => 'library'], ['name' => 'Library', 'category' => 'LIBRARY', 'icon' => 'fa-book']);
        $this->attachMenuPermission($mm, null, ['admin']);

        $sm = $mm->subMenus()->create(['name' => 'Category', 'url' => $mm->url . '/category', 'category' => $mm->category]);
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->create(['name' => 'Product', 'url' => $mm->url . '/product', 'category' => $mm->category]);
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->create(['name' => 'Taxes', 'url' => $mm->url . '/tax', 'category' => $mm->category]);
        $this->attachMenuPermission($sm, null, ['admin']);

        // $sm = $mm->subMenus()->create(['name' => 'Promo', 'url' => $mm->url . '/promo', 'category' => $mm->category]);
        // $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->create(['name' => 'Modifiers', 'url' => $mm->url . '/modifiers', 'category' => $mm->category]);
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->create(['name' => 'Discount', 'url' => $mm->url . '/discount', 'category' => $mm->category]);
        $this->attachMenuPermission($sm, null, ['admin']);
        // END LIBRARY

        // EMPLOYEES
        $mm = Menu::firstOrCreate(['url' => 'employee'], ['name' => 'Employee', 'category' => 'EMPLOYEE', 'icon' => 'fa-users']);
        $this->attachMenuPermission($mm, null, ['admin']);

        $sm = $mm->subMenus()->create(['name' => 'Users', 'url' => $mm->url . '/users', 'category' => $mm->category]);
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->create(['name' => 'Role', 'url' => $mm->url . '/roles', 'category' => $mm->category]);
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->create(['name' => 'Hak Akses', 'url' => $mm->url . '/hak-akses', 'category' => $mm->category]);
        $this->attachMenuPermission($sm, null, ['admin']);

        // END EMPLOYEE

        //  KONFIGURASI
        $mm = Menu::firstOrCreate(['url' => 'konfigurasi'], ['name' => 'Konfigurasi', 'category' => 'KONFIGURASI', 'icon' => 'fa-cogs']);
        $this->attachMenuPermission($mm, ['read '], ['admin']);

        $sm = $mm->subMenus()->create(['name' => 'Menu', 'url' => $mm->url . '/menu', 'category' => $mm->category]);
        $this->attachMenuPermission($sm, ['create ', 'read ', 'update ', 'delete ', 'sort '], ['admin']);

        $sm = $mm->subMenus()->create(['name' => 'Permission', 'url' => $mm->url . '/permissions', 'category' => $mm->category]);
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->create(['name' => 'Outlets', 'url' => $mm->url . '/outlets', 'category' => $mm->category]);
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->create(['name' => 'Checkout', 'url' => $mm->url . '/checkout', 'category' => $mm->category]);
        $this->attachMenuPermission($sm, null, ['admin']);

        // END KONFIGURASI

        //KASIR
            $mm = Menu::firstOrCreate(['url' => 'kasir'], ['name' => 'Kasir', 'category' => 'KASIR', 'icon' => 'fa-money-bill']);
            $this->attachMenuPermission($mm, ['read '], ['admin']);
        //END KASIR

    }
}

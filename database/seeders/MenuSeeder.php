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

        //  REPORT
        $mm = Menu::firstOrCreate(['url' => 'report'], ['name' => 'Reports', 'category' => 'REPORTS', 'icon' => 'fa-file']);
        $this->attachMenuPermission($mm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/sales'],
            ['name' => 'Sales', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/transactions'],
            ['name' => 'Transactions', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/openbill'],
            ['name' => 'Open Bill', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/rush-hour'],
            ['name' => 'Rush Hour', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);
        // END REPORT

        // LIBRARY
        $mm = Menu::firstOrCreate(['url' => 'library'], ['name' => 'Library', 'category' => 'LIBRARY', 'icon' => 'fa-book']);
        $this->attachMenuPermission($mm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/category'],
            ['name' => 'Category', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/product'],
            ['name' => 'Product', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/tax'],
            ['name' => 'Taxes', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/promo'],
            ['name' => 'Promo', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/modifiers'],
            ['name' => 'Modifiers', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/discount'],
            ['name' => 'Discount', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/salestype'],
            ['name' => 'Sales Type', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/pilihan'],
            ['name' => 'Pilihan Item', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/note-receipt-scheduling'],
            ['name' => 'Note Receipt Scheduling', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);
        // END LIBRARY

        // ACCOUNTING
        $mm = Menu::firstOrCreate(['url' => 'accounting'], ['name' => 'Accounting', 'category' => 'ACCOUNTING', 'icon' => 'fa-calculator']);
        $this->attachMenuPermission($mm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/pengeluaran'],
            ['name' => 'Pengeluaran', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/pemasukan'],
            ['name' => 'Pendapatan Diluar Transaksi', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/piutang'],
            ['name' => 'Piutang / Kasbon', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);
        // END ACCOUNTING

        // EMPLOYEES
        $mm = Menu::firstOrCreate(['url' => 'employee'], ['name' => 'Employee', 'category' => 'EMPLOYEE', 'icon' => 'fa-user-tie']);
        $this->attachMenuPermission($mm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/users'],
            ['name' => 'Users', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/roles'],
            ['name' => 'Role', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/hak-akses'],
            ['name' => 'Hak Akses', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        // END EMPLOYEE

        // CUSTOMER MANAGEMENT

        $mm = Menu::firstOrCreate(['url' => 'membership'], ['name' => 'Membership', 'category' => 'MEMBERSHIP', 'icon' => 'fa-users']);
        $this->attachMenuPermission($mm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/customer'],
            ['name' => 'Customer', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/community'],
            ['name' => 'Community', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/level-membership'],
            ['name' => 'Level Membership', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/list-icon'],
            ['name' => 'List Icon', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        // END CUSTOMER MANAGEMENT

        //  KONFIGURASI
        $mm = Menu::firstOrCreate(['url' => 'konfigurasi'], ['name' => 'Konfigurasi', 'category' => 'KONFIGURASI', 'icon' => 'fa-cogs']);
        $this->attachMenuPermission($mm, ['read '], ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/menu'],
            ['name' => 'Menu', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, ['create ', 'read ', 'update ', 'delete ', 'sort '], ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/permissions'],
            ['name' => 'Permission', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/outlets'],
            ['name' => 'Outlets', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/checkout'],
            ['name' => 'Checkout', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/category-payment'],
            ['name' => 'Category Payment', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        $sm = $mm->subMenus()->updateOrCreate(
            ['url' => $mm->url . '/payment'],
            ['name' => 'Payment', 'category' => $mm->category]
        );
        $this->attachMenuPermission($sm, null, ['admin']);

        // END KONFIGURASI

        //KASIR
        $mm = Menu::firstOrCreate(['url' => 'kasir'], ['name' => 'Kasir', 'category' => 'KASIR', 'icon' => 'fa-money-bill']);
        $this->attachMenuPermission($mm, ['read '], ['admin']);
        //END KASIR

    }
}

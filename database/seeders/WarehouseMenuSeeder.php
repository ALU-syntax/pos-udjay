<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use App\Traits\HasMenuPermission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WarehouseMenuSeeder extends Seeder
{
    use HasMenuPermission;

    public function run(): void
    {
        DB::transaction(function () {
            Cache::forget('menus');

            // Penambahan Library
            $mm = Menu::firstOrCreate(['url' => 'library'], ['name' => 'Library', 'category' => 'LIBRARY', 'icon' => 'fa-book']);
            $this->attachMenuPermission($mm, null, ['admin']);
            $sm = $mm->subMenus()->updateOrCreate(
                ['url' => 'warehouse/satuan'],
                [
                    'name' => 'Satuan',
                    'category' => $mm->category,
                ]
            );

            $this->attachMenuPermission($sm, null, ['admin']);

            $sm = $mm->subMenus()->updateOrCreate(
                ['url' => 'library/category-bahan-baku'],
                [
                    'name' => 'Kategori Bahan Baku',
                    'category' => $mm->category,
                ]
            );
            $this->attachMenuPermission($sm, null, ['admin']);

            $sm = $mm->subMenus()->updateOrCreate(
                ['url' => 'library/bahan-baku'],
                [
                    'name' => 'Bahan Baku',
                    'category' => $mm->category,
                ]
            );
            $this->attachMenuPermission($sm, null, ['admin']);

            $mm = Menu::updateOrCreate(
                ['url' => 'warehouse'],
                [
                    'name' => 'Warehouse',
                    'category' => 'WAREHOUSE',
                    'icon' => 'fa-warehouse',
                ]
            );
            $this->attachMenuPermission($mm, null, ['admin']);

            $sm = $mm->subMenus()->updateOrCreate(
                ['url' => 'warehouse/supplier'],
                [
                    'name' => 'Supplier',
                    'category' => $mm->category,
                ]
            );
            $this->attachMenuPermission($sm, null, ['admin']);


        });
    }
}

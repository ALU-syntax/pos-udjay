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
                ['url' => 'warehouse/satuan'],
                [
                    'name' => 'Satuan',
                    'category' => $mm->category,
                ]
            );

            $this->attachMenuPermission($sm, null, ['admin']);
        });
    }
}

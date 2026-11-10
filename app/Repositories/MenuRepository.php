<?php

namespace App\Repositories;

use App\Models\Menu;

class MenuRepository{

    public function getMainMenuWithPermissions(){
        // Urutan sama dengan sidebar agar mudah dicocokkan saat mengatur hak akses
        return Menu::with(['permissions', 'subMenus' => fn ($query) => $query->orderBy('orders'), 'subMenus.permissions'])
            ->whereNull('main_menu_id')
            ->orderBy('orders')
            ->get();
    }

    public function getMainMenus(){
        return Menu::whereNull('main_menu_id')->select('id', 'name')->get()
            ->flatmap(function($item){
                return [$item->name => $item->id];
            });
    }

    public function getMenus(){
        return Menu::active()->with(['subMenus' => function($query){
            $query->orderBy('orders');
        }])->whereNull('main_menu_id')
        ->orderBy('orders')
        ->get();
    }

}
<?php

use App\Repositories\MenuRepository;
use Illuminate\Support\Facades\Cache;

if(!function_exists('menus')){
    /**
     * @return Collection
     */
    function menus(){
        if(!Cache::has('menus')){
            $menus = (new MenuRepository())->getMenus()->groupBy('category');

            Cache::forever('menus', $menus);
        }else{
            $menus = Cache::get('menus');
        }

        return $menus;
    }

    if(!function_exists('user')){
        /**
         * @param string $id
         * @return \App\Models\User | String
         */
        function user($id = null){
            if($id){
                return request()->user()->{$id};
            }
            return request()->user();
        }
    }

    if(!function_exists('responseSuccess')){
        function responseSuccess($isEdit = false){
    
            return response()->json([
                'status' => 'success',
                'message' => $isEdit ? 'Update data Successfully' : 'Create data Successfully',
            ]);
    
        }
    }

    if(!function_exists('urlMenu')){
        function urlMenu(){
            if(!Cache::has('urlMenu')){
                
                $menus = menus()->flatMap(fn ($item) => $item);
    
                $url = [];
    
                foreach($menus as $mm){
                    $url[] = $mm->url;
                    foreach($mm->subMenus as $sm){
                        $url[] = $sm->url;
                    }
                }
    
                Cache::forever('urlMenu', $url);
            }else{
                $url = Cache::get('urlMenu');
            }
    
            return $url;
        }
    }
    
}
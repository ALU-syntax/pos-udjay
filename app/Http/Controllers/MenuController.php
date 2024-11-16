<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;
use App\DataTables\MenuDataTable;
use App\Models\Permission;
use App\Repositories\MenuRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Mavinoo\Batch\BatchFacade;

class MenuController extends Controller
{
    public function __construct(private MenuRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(MenuDataTable $menuDataTable)
    {
        return $menuDataTable->render('layouts.menu.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('layouts.menu.create', [
            'mainMenus' => $this->repository->getMainMenus()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Menu $menu)
    {

        $request->validate([
            'name' => 'required',
            'url' => 'required',
            'orders' => 'required|integer',
            'icon' => 'required',
            'category' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $this->authorize('create konfigurasi/menu');

            $menu->fill([
                'name' => $request->name,
                'url' => $request->url,
                'orders' => $request->orders,
                'icon' => $request->icon,
                'category' => $request->category,
                'main_menu_id' => $request->main_menu,
            ]);
            $menu->save();


            foreach ($request->permissions ?? [] as $permission) {
                Permission::create(['name' => $permission . " {$menu->url}"])->menus()->attach($menu);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();

            return redirect()->back()->with('error', $th->getMessage());
        }
        return redirect()->route('konfigurasi/menu')->with('success', 'Data Menu Berhasil Dibuat!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Menu $menu)
    {
        $this->authorize('update konfigurasi/menu');

        $permissionCreate = Permission::where('name', "create $menu->url")->get('id');
        $permissionRead = Permission::where('name', "read $menu->url")->get('id');
        $permissionUpdate = Permission::where('name', "update $menu->url")->get('id');
        $permissionDelete = Permission::where('name', "delete $menu->url")->get('id');

        $permissions = [
            'create' => $permissionCreate->isEmpty() ? null : $permissionCreate[0]->id,
            'read' => $permissionRead->isEmpty() ? null : $permissionRead[0]->id,
            'update' => $permissionUpdate->isEmpty() ? null : $permissionUpdate[0]->id,
            'delete' => $permissionDelete->isEmpty() ? null : $permissionDelete[0]->id
        ];

        return view('layouts.menu.edit', [
            'action' => route('konfigurasi/menu/update', $menu->id),
            'data' => $menu,
            'permissions' => $permissions,
            'mainMenus' => $this->repository->getMainMenus()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validateData = $request->validate([
            'name' => 'required',
            'url' => 'required',
            'orders' => 'required|integer',
            'icon' => 'required',
            'category' => 'required',
        ]);

        $menu = Menu::find($id);
        // $crud = ['create', 'read', 'update', 'delete'];

        DB::beginTransaction();
        try {
            $this->authorize('update konfigurasi/menu');

            // foreach ($crud as $data) {
            //     $oldPermission = Permission::where('name', "$data $menu->url")->first();
            //     if ($oldPermission != null) {
            //         $oldPermission->delete();
            //     }
            // }

            if($request->level_menu == 'main_menu'){   
                $menu->main_menu_id = null;
            }

            $menu->update($validateData);

            // foreach ($request->permissions ?? [] as $permission) {
            //     Permission::create(['name' => $permission . " {$menu->url}"])->menus()->attach($menu);
            // }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();

            return redirect()->back()->with('error', $th->getMessage());
        }
        return redirect()->route('konfigurasi/menu')->with('success', 'Data Menu Berhasil Diupdate!');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // $item = Menu::findOrFail($id);
        // $item->delete();

        // return redirect()->route('konfigurasi/menu')->with('success', 'Menu deleted successfully.');
    }

    public function sort()
    {
        $menus = $this->repository->getMenus();

        $data = [];
        $i = 0;
        foreach ($menus as $mm) {
            $i++;
            $data[] = ['id' => $mm->id, 'orders' => $i];
            foreach ($mm->subMenus as $sm) {
                $i++;
                $data[] = ['id' => $mm->id, 'orders' => $i];
            }
        }

        Cache::forget('menus');

        BatchFacade::update(new Menu(), $data, 'id');
        return redirect()->back()->with('success', 'Data Berhasil Disorting');
    }
}

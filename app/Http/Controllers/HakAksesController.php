<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use App\Repositories\MenuRepository;
use App\DataTables\UserPermissionDataTable;

class HakAksesController extends Controller
{

    public function __construct(protected MenuRepository $menuRepository) {
        $this->menuRepository = $menuRepository;
    }
    
    public function index(UserPermissionDataTable $hakAksesDataTable){
        return $hakAksesDataTable->render('layouts.hak_akses.index',[
            'roles' => Role::all()
        ]);
    }

    public function editAksesRole($id){
        $role=Role::find($id); 
        $roles = Role::where('id', '!=', $role->id)->get()->pluck('id', 'name');
        return view('layouts.hak_akses.edit-role-akses',[
            'data' => $role,
            'menus' => $this->menuRepository->getMainMenuWithPermissions(),
            'roles' => $roles,
        ]);

    }

    public function updateAksesRole(Request $request, $id){
        // dd('masuk pak eko');
        $role = Role::find($id);
        $role->syncPermissions($request->permissions);

        return redirect()->route('konfigurasi/hak-akses')->with('success', 'Role Permission Berhasil Diupdate');
    }

    public function editAksesUser($id){
        $user = User::find($id);
        $users = User::where('id', '!=', $user->id)->get()->pluck('id', 'name');
        return view('layouts.hak_akses.edit-user-akses',[
            'data' => $user,
            'users' => $users,
            'menus' => $this->menuRepository->getMainMenuWithPermissions()
        ]);
    }

    public function updateAksesUser(Request $request,$id){
        $user = User::find($id);
        $user->syncPermissions($request->permissions);

        return redirect()->route('konfigurasi/hak-akses')->with('success', 'User Permission Berhasil Diupdate');
    }
}

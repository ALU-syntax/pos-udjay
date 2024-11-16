<?php

namespace App\Http\Controllers;

use App\DataTables\RoleDataTable;
use App\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(RoleDataTable $roleDataTable)
    {
        return $roleDataTable->render('layouts.role.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('layouts.role.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $requestValidate = $request->validate([
            'name' => 'required|unique:roles,name',
            'guard_name' => 'required'
        ]);

        $requestValidate['guard_name'] = strtolower($request->guard_name);
        $role = new Role($requestValidate);
        $role->save();

        return redirect()->route('konfigurasi/roles')->with('success','Role Berhasil dibuat!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $role = Role::find($id);
        return view('layouts.role.edit',[
            'data'=> $role
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $requestValidate = $request->validate([
            'name' => [
                'required',
                Rule::unique('roles')->ignore($id), // pengecualian untuk role yang sedang di-update
            ],
            'guard_name' => 'required'
        ]);

        $role = Role::find($id);
        $role->update([
            'name' => $requestValidate['name'],
            'guard_name' => strtolower($requestValidate['guard_name'])
        ]);

        return redirect()->route('konfigurasi/roles')->with('success','Role Berhasil diupdate!');
        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $role = Role::find($id);
        $role->delete();
        return redirect()->back()->with('success','Role Berhasil dihapus!');
        // return response()->json(['success' => true, 'message' => 'Item deleted successfully']);
    }
}

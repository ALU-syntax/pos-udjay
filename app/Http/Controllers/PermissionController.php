<?php

namespace App\Http\Controllers;

use App\DataTables\PermissionDataTable;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(PermissionDataTable $permissionDataTable)
    {
        return $permissionDataTable->render('layouts.permission.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('layouts.permission.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $requestValidate = $request->validate([
            'name' => 'required|unique:permissions,name',
            'guard_name' => 'required'
        ]);

        $permission = new Permission($requestValidate);
        $permission->save();

        return redirect()->route('konfigurasi/permissions')->with('success','Permission Berhasil dibuat!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('layouts.permission.edit',['data' => Permission::find($id)]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $requestValidate = $request->validate([
            'name' => 'required|unique:permissions,name',
            'guard_name' => 'required'
        ]);

        $permission = Permission::find($id);
        $permission->update($requestValidate);

        return redirect()->route('konfigurasi/permissions')->with('success','Permission Berhasil diupdate!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $permission = Permission::find($id);
        $permission->delete();
        return redirect()->back()->with('success','Permission Berhasil dihapus!');
    }
}

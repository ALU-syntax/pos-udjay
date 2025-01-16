<?php

namespace App\Http\Controllers;

use App\DataTables\UserDataTable;
use App\Models\Kelurahan;
use App\Models\Outlets;
use App\Models\Role;
use App\Models\User;
use App\Models\Partai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(UserDataTable $userDataTable)
    {
        return $userDataTable->render('layouts.users.index');
    }

    public function create()
    {
        return view('layouts.users.create', [
            'listRole' => Role::whereNull('deleted_at')->get(),
            'outlets' => Outlets::all()
        ]);
    }

    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|unique:users,username',
            'password' => 'required',
            'email' => 'nullable',
            'status' => 'required',
            'role' => 'required',
            'outlet_id' => 'required',
            'pin' => 'nullable|string|size:6',
        ], [
            'username.unique' => 'username sudah terdaftar'
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'password' => bcrypt($request->password),
            'email' => $request->email,
            'status' => $request->status,
            'role' => $request->role,
            'outlet_id' => json_encode($request->outlet_id),
            'pin' => Hash::make($request->pin)
        ]);

        $role = Role::find($request->role);
        $user->assignRole($role->name);

        return redirect()->route('employee/user')->with('success', 'Data User Berhasil Dibuat!');
    }

    public function edit($id)
    {
        $user = User::find($id);
        return view('layouts.users.edit', [
            'menu' => 'users',
            'subMenu' => 'user',
            'data' => $user,
            'roles' => Role::whereNull('deleted_at')->get(),
            'outlets' => Outlets::all()
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        // Validasi input sesuai kebutuhan

        \Log::info($request->all());

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable',
            'username' => 'required',
            'status' => 'required',
            'role' => 'required',
            'outlet_id' => 'required'
        ]);


        $role = Role::find($validatedData['role']);
        $user->syncRoles([$role->name]);

        $validatedData['outlet_id'] = json_encode($request->outlet_id);
        \Log::info($validatedData);
        // Perbarui data pengguna yang tidak termasuk kata sandi
        $user->update($validatedData);
        // Periksa apakah kata sandi diisi atau tidak
        if (!empty($request->password)) {
            $user->update([
                'password' => bcrypt($request->password)
            ]);
            // $validatedData['password'] = bcrypt($request->password);
        }



        return redirect()->route('employee/user')->with('success', 'Data User Berhasil Diupdate!');
    }

    public function destroy($id)
    {
        $user = User::find($id);

        $user->deleted = 1;
        $user->status = 0;
        $user->save();

        return redirect()->route('employee/user')->with('success', 'Data User Berhasil Dihapus!');
    }
}

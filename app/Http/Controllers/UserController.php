<?php

namespace App\Http\Controllers;

use App\DataTables\UserDataTable;
use App\Models\Outlets;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UserController extends Controller
{
    public function index(UserDataTable $userDataTable)
    {
        return $userDataTable->render('layouts.users.index', [
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('layouts.users.create', $this->formData(new User(['status' => '1'])));
    }

    public function store(Request $request)
    {
        $validated = $this->validateUser($request);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => bcrypt($validated['password']),
            'email' => $validated['email'] ?? null,
            'status' => $validated['status'],
            'role' => $validated['role'],
            // Tetap di-encode manual: banyak kode lama membaca kolom ini dengan json_decode()
            'outlet_id' => json_encode($validated['outlet_id']),
            'pin' => filled($validated['pin'] ?? null) ? Hash::make($validated['pin']) : null,
        ]);

        $user->assignRole(Role::findOrFail($validated['role'])->name);

        return redirect()->route('employee/user')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit($id)
    {
        return view('layouts.users.edit', $this->formData(User::findOrFail($id)));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $validated = $this->validateUser($request, $user);

        $user->update([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'status' => $validated['status'],
            'role' => $validated['role'],
            'outlet_id' => json_encode($validated['outlet_id']),
        ]);
        $user->syncRoles([Role::findOrFail($validated['role'])->name]);

        // Password dan PIN hanya diganti bila diisi
        if (filled($validated['password'] ?? null)) {
            $user->update(['password' => bcrypt($validated['password'])]);
        }
        if (filled($validated['pin'] ?? null)) {
            $user->update(['pin' => Hash::make($validated['pin'])]);
        }

        return redirect()->route('employee/user')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->is(auth()->user())) {
            return response()->json(['status' => 'error', 'message' => 'Akun yang sedang dipakai tidak dapat dihapus.'], 422);
        }

        $user->deleted = 1;
        $user->status = 0;
        $user->save();

        return responseSuccess(false, 'User berhasil dihapus.');
    }

    private function formData(User $user): array
    {
        return [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
            'outlets' => Outlets::orderBy('name')->get(),
        ];
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        $validator = validator($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', Rule::in(['0', '1'])],
            'role' => ['required', Rule::exists('roles', 'id')->whereNull('deleted_at')],
            'outlet_id' => ['required', 'array', 'min:1'],
            'outlet_id.*' => ['integer', 'exists:outlets,id'],
            'pin' => ['nullable', 'digits:6'],
        ], [
            'username.unique' => 'Username sudah terdaftar.',
            'outlet_id.required' => 'Pilih minimal satu outlet.',
            'pin.digits' => 'PIN harus 6 digit angka.',
        ], [
            'name' => 'nama',
            'outlet_id' => 'outlet',
        ]);

        // Kasir hanya boleh terhubung ke satu outlet
        $validator->after(function (Validator $validator) use ($request) {
            $role = Role::find($request->input('role'));
            if ($role && strtolower($role->name) === 'kasir' && count((array) $request->input('outlet_id')) > 1) {
                $validator->errors()->add('outlet_id', 'User dengan role Kasir hanya boleh memilih satu outlet.');
            }
        });

        return $validator->validate();
    }
}

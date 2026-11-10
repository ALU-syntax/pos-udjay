<?php

namespace App\Http\Controllers;

use App\DataTables\RoleDataTable;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index(RoleDataTable $roleDataTable)
    {
        return $roleDataTable->render('layouts.role.index');
    }

    public function create()
    {
        return view('layouts.role.create', ['role' => new Role]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRole($request);

        Role::create([
            'name' => $validated['name'],
            // Aplikasi hanya punya guard web; dulu diketik manual dan rawan salah isi
            'guard_name' => config('auth.defaults.guard'),
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('employee/roles')->with('success', 'Role berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);

        if ($role->isSystemRole()) {
            return redirect()->route('employee/roles')->with('error', 'Role sistem tidak dapat diubah.');
        }

        return view('layouts.role.edit', ['role' => $role]);
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        if ($role->isSystemRole()) {
            return redirect()->route('employee/roles')->with('error', 'Role sistem tidak dapat diubah.');
        }

        $role->update(['name' => $this->validateRole($request, $role)['name']]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('employee/roles')->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        if ($role->isSystemRole()) {
            return response()->json(['status' => 'error', 'message' => 'Role sistem tidak dapat dihapus.'], 422);
        }

        $usedBy = User::where('role', $role->id)->where('deleted', '!=', 1)->count();
        if ($usedBy > 0) {
            return response()->json([
                'status' => 'error',
                'message' => "Role masih dipakai {$usedBy} user. Pindahkan user ke role lain terlebih dahulu.",
            ], 422);
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return responseSuccess(false, 'Role berhasil dihapus.');
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:125',
                // Role yang sudah dihapus tetap menempati nama, karena Spatie mencari role tanpa melihat soft delete
                Rule::unique('roles', 'name')->ignore($role?->id),
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (in_array(strtolower(trim((string) $value)), Role::SYSTEM_ROLES, true)) {
                        $fail('Nama ini dipakai role sistem.');
                    }
                },
            ],
        ], [
            'name.required' => 'Nama role wajib diisi.',
            'name.unique' => 'Nama role sudah dipakai (termasuk role yang pernah dihapus).',
        ]);
    }
}

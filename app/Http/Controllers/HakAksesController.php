<?php

namespace App\Http\Controllers;

use App\DataTables\UserPermissionDataTable;
use App\Models\Role;
use App\Models\User;
use App\Repositories\MenuRepository;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class HakAksesController extends Controller
{
    public function __construct(protected MenuRepository $menuRepository)
    {
    }

    public function index(UserPermissionDataTable $hakAksesDataTable)
    {
        // Jumlah dan contoh user diambil dari kolom users.role, sumber yang sama dengan halaman Users dan Role
        $usersByRole = User::where('deleted', '!=', 1)->orderBy('name')->get(['id', 'name', 'role'])->groupBy('role');

        return $hakAksesDataTable->render('layouts.hak_akses.index', [
            'roles' => Role::withCount('permissions')->orderBy('name')->get(),
            'usersByRole' => $usersByRole,
        ]);
    }

    public function editAksesRole($id)
    {
        $role = Role::findOrFail($id);

        if ($this->isSuperAdmin($role)) {
            return redirect()->route('employee/hak-akses')->with('error', 'Role super admin sudah memiliki semua akses.');
        }

        return view('layouts.hak_akses.edit', [
            'type' => 'role',
            'subject' => $role,
            'menus' => $this->menuRepository->getMainMenuWithPermissions(),
            'selected' => $role->permissions->pluck('name')->all(),
            'inherited' => [],
            'action' => route('employee/hak-akses/role/update', $role->id),
        ]);
    }

    public function updateAksesRole(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        if ($this->isSuperAdmin($role)) {
            return redirect()->route('employee/hak-akses')->with('error', 'Role super admin sudah memiliki semua akses.');
        }

        $role->syncPermissions($this->validatePermissions($request));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('employee/hak-akses')->with('success', "Hak akses role {$role->name} berhasil diperbarui.");
    }

    public function editAksesUser($id)
    {
        $user = User::findOrFail($id);

        return view('layouts.hak_akses.edit', [
            'type' => 'user',
            'subject' => $user,
            'roleName' => Role::find($user->role)?->name,
            'menus' => $this->menuRepository->getMainMenuWithPermissions(),
            // Hanya permission langsung yang bisa diubah di sini; permission dari role ditampilkan terkunci
            'selected' => $user->getDirectPermissions()->pluck('name')->all(),
            'inherited' => $user->getPermissionsViaRoles()->pluck('name')->all(),
            'action' => route('employee/hak-akses/user/update', $user->id),
        ]);
    }

    public function updateAksesUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->syncPermissions($this->validatePermissions($request));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('employee/hak-akses')->with('success', "Akses tambahan {$user->name} berhasil diperbarui.");
    }

    private function validatePermissions(Request $request): array
    {
        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        return $validated['permissions'] ?? [];
    }

    private function isSuperAdmin(Role $role): bool
    {
        return strtolower($role->name) === 'super admin';
    }
}

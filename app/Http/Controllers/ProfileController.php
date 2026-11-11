<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Outlets;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Halaman profil milik user yang sedang login.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('layouts.profile.index', [
            'user' => $user,
            'roleName' => Role::withTrashed()->find($user->role)?->name,
            'outletNames' => Outlets::whereIn('id', $user->outletIds())->orderBy('name')->pluck('name'),
        ]);
    }

    /**
     * Perbarui nama, username, dan email milik sendiri.
     * Role, outlet, dan status tetap dikelola admin lewat Employee > Users.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()->route('profile.edit')->with('success', 'Profil berhasil diperbarui.');
    }

    /**
     * Ganti PIN kasir milik sendiri. Dikonfirmasi dengan password, bukan PIN lama,
     * supaya user yang belum punya PIN atau lupa PIN tetap bisa mengaturnya.
     */
    public function updatePin(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePin', [
            'current_password' => ['required', 'current_password'],
            'pin' => ['required', 'digits:6', 'confirmed'],
        ], [
            'current_password.required' => 'Password wajib diisi.',
            'current_password.current_password' => 'Password salah.',
            'pin.required' => 'PIN baru wajib diisi.',
            'pin.digits' => 'PIN harus 6 digit angka.',
            'pin.confirmed' => 'Konfirmasi PIN tidak sama.',
        ]);

        $request->user()->update([
            'pin' => Hash::make($validated['pin']),
        ]);

        return redirect()->route('profile.edit')->with('success', 'PIN berhasil diganti.');
    }
}

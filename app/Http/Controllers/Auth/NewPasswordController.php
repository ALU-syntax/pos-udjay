<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Halaman atur ulang password (dibuka dari link di email).
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Simpan password baru.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'token.required' => 'Link atur ulang password tidak valid.',
            'email.required' => 'Link atur ulang password tidak valid.',
            'email.email' => 'Link atur ulang password tidak valid.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal :min karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        // Sama seperti login: user yang sudah dihapus (deleted = 1) tidak dilayani
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token') + ['deleted' => 0],
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', 'Password berhasil diatur ulang. Silakan login dengan password baru.');
        }

        // Token salah, sudah dipakai, kedaluwarsa, atau email tidak cocok: pesannya disamakan
        return back()->withInput($request->only('email'))->withErrors([
            'email' => 'Link atur ulang password tidak valid atau sudah kedaluwarsa.',
        ]);
    }
}

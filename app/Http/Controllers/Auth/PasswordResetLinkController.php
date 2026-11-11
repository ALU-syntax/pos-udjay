<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PasswordResetLinkController extends Controller
{
    /** Maksimal permintaan link per email + IP dalam satu jendela waktu. */
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 600;

    /**
     * Halaman "Lupa password".
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Kirim link atur ulang password ke email.
     *
     * Jawabannya sengaja selalu sama, baik email terdaftar maupun tidak,
     * supaya halaman ini tidak bisa dipakai untuk menebak email siapa yang punya akun.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $email = $request->input('email');
        $throttleKey = 'password-reset-link:'.Str::lower($email).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($throttleKey) / 60);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak permintaan. Coba lagi dalam {$minutes} menit.",
            ]);
        }

        RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

        try {
            // Sama seperti login: user yang sudah dihapus (deleted = 1) tidak dilayani
            Password::sendResetLink(['email' => $email, 'deleted' => 0]);
        } catch (Throwable $e) {
            report($e);

            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Email gagal dikirim. Coba lagi beberapa saat, atau hubungi admin.',
            ]);
        }

        return back()->with(
            'status',
            'Jika email tersebut terdaftar, link untuk mengatur ulang password sudah dikirim. '
                .'Periksa kotak masuk dan folder spam. Link berlaku '.config('auth.passwords.users.expire').' menit.'
        );
    }
}

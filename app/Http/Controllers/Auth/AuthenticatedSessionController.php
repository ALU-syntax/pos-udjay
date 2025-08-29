<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\LoginKasirRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    // public function store(LoginRequest $request): RedirectResponse
    // {
    //     $request->authenticate();

    //     $request->session()->regenerate();

    //     return redirect()->intended(RouteServiceProvider::HOME);
    // }

    public function store(Request $request)
    {
        // validasi dasar
        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        // tambahkan filter deleted = 0
        $credentials = [
            'email'    => $validated['email'],
            'password' => $validated['password'],
            'deleted'  => 0,               // <— kuncinya di sini
        ];

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // Jika AJAX -> balas JSON
            if ($request->expectsJson()) {
                // bisa kirimkan intended url kalau ada
                return response()->json([
                    'ok'       => true,
                    'redirect' => url()->previous() === route('login') ? url('/') : url()->previous()
                ]);

                // return redirect()->intended(RouteServiceProvider::HOME);
            }

            return redirect()->intended('/dashboard');
        }

        // Gagal login
        if ($request->expectsJson()) {
            return response()->json([
                'ok'      => false,
                'message' => 'Email atau password salah'
            ], 401);
        }

        // Fallback non-AJAX
        throw ValidationException::withMessages([
            'email' => 'Email atau password salah',
        ]);
    }

    public function storeKasir(LoginKasirRequest $request)
    {
        // dd($request);
        $request->authenticate();

        $request->session()->regenerate();

        // Mendapatkan pengguna yang sedang login
        // $user = $request->user();

        // // Memeriksa role ID
        // if ($user->role == 3) {
        //     // dd($user);
        //     // Jika role ID adalah 3, arahkan ke '/kasir'
        //     return redirect()->intended('/kasir');
        // }

        return redirect()->intended('/kasir');

        // Jika bukan, arahkan ke '/'
        // return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}

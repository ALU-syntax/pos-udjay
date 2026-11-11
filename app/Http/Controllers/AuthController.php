<?php

namespace App\Http\Controllers;

class AuthController extends Controller
{
    public function login()
    {
        // Kasir sudah memakai aplikasi Android, jadi semua perangkat
        // (termasuk HP/tablet) memakai halaman login backoffice.
        return view('layouts.login-new');
    }
}

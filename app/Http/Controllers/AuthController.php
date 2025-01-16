<?php

namespace App\Http\Controllers;

use App\Models\Outlets;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class AuthController extends Controller
{
    public function login()
    {
        $agent = new Agent(); // Membuat instance dari Agent  
  
        // Cek apakah perangkat mobile atau tablet  
        if ($agent->isMobile() || $agent->isTablet()) {  
            // Jika menggunakan mobile atau tablet, arahkan ke login-kasir  
            return view('layouts.login-kasir', [  
                'outlets' => Outlets::all()  
            ]);  
        }  
   
        // Jika menggunakan desktop atau laptop, arahkan ke login-new  
        return view('layouts.login-new'); 
    }
}

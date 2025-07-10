<?php

namespace App\Http\Controllers;

use App\Models\Checkout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CheckoutController extends Controller
{
    public function index()
    {
        return view('layouts.checkout.index', [
            'data' =>Checkout::find(1)
        ]);
    }

    public function store(Request $request)
    {
        $data['rounded'] = $request->rounded == "on" ? "true" : null;
        $data['rounded_benchmark'] = $request->rounded == "on" ? $request->rounded_benchmark : null;
        $data['rounded_type'] = $request->rounded == "on" ? $request->rounded_type : null;

        $dataCheckout = Checkout::find(1);
        if ($dataCheckout) {
            $dataCheckout->update($data);
            return responseSuccess(true);
        } else {
            Checkout::create($data);
            return responseSuccess(false);
        }
    }

    public function showForm()
    {
        return view('email_test');
    }

    public function sendTestEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $toEmail = $request->input('email');

        Mail::raw('Ini adalah email uji coba dari Laravel.', function ($message) use ($toEmail) {
            $message->to($toEmail)
                    ->subject('Test Email Laravel');
        });

        return back()->with('success', 'Email test berhasil dikirim ke ' . $toEmail);
    }
}

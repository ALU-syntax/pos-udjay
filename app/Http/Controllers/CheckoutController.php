<?php

namespace App\Http\Controllers;

use App\Imports\BackupImport;
use App\Mail\TestEmail;
use App\Models\Checkout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

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
        // Mail::to("test@gmail.com")->send(new TestEmail());
        // return view('email_test');
    }

    public function sendTestEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $toEmail = $request->input('email');

        Mail::to($toEmail)->send(new TestEmail());
        // Mail::raw('Ini adalah email uji coba dari Laravel.', function ($message) use ($toEmail) {
        //     $message->to($toEmail)
        //             ->subject('Test Email Laravel');
        // });

        return back()->with('success', 'Email test berhasil dikirim ke ' . $toEmail);
    }

    // public function importDataBackup(Request $request)
    // {
    //     $request->validate([
    //         'file' => 'required|file',
    //     ]);

    //     $file = $request->file('file');

    //     // dd($file);
    //     try {
    //         Excel::import(new BackupImport, $file);
    //         return back()->with('success', 'Import selesai! Data berhasil disimpan.');
    //     } catch (\Exception $e) {
    //         return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
    //     }
    // }

    public function importDataBackup(Request $request)
    {
        $request->validate(['file' => 'required|file']);

        try {
            // jalan sebagai job background — tidak kena timeout web server
            \Maatwebsite\Excel\Facades\Excel::queueImport(new \App\Imports\BackupImport, $request->file('file'));
            return back()->with('success', 'Import sedang diproses di background. Cek notifikasi/log.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

}

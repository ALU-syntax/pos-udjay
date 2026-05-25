<?php

namespace App\Http\Controllers;

use App\Imports\BackupImport;
use App\Jobs\ProcessBackupImportJob;
use App\Mail\BirthdayMail;
use App\Models\Checkout;
use App\Models\Outlets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function index()
    {
        $outletIds = json_decode(auth()->user()->outlet_id ?? '[]') ?: [];

        return view('layouts.checkout.index', [
            'data' =>Checkout::find(1),
            'outlets' => Outlets::whereIn('id', $outletIds)->get(),
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

    // public function showForm()
    // {
    //     // Mail::to("test@gmail.com")->send(new TestEmail());
    //     // return view('email_test');
    // }

    public function sendTestEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $toEmail = $request->input('email');

        $data = ['name' => "Ardian"];
        Mail::to($toEmail)->send(new BirthdayMail($data));
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
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
            'import_id' => 'nullable|string|max:80',
            'fallback_outlet_id' => 'nullable|exists:outlets,id',
        ]);

        $importId = $request->input('import_id') ?: (string) Str::uuid();
        $cacheKey = BackupImport::progressCacheKey(auth()->id(), $importId);

        try {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension() ?: 'csv';
            $storedPath = $file->storeAs('imports/backup', $importId . '.' . $extension);

            Cache::put($cacheKey, array_merge(BackupImport::defaultProgress(), [
                'status' => 'queued',
                'message' => 'File diterima. Import masuk antrean.',
            ]), now()->addHour());

            ProcessBackupImportJob::dispatch(
                $storedPath,
                $importId,
                auth()->id(),
                $request->integer('fallback_outlet_id') ?: null
            );

            $message = 'Import dimulai di background. Progress akan berjalan otomatis.';

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => $message,
                    'data' => [
                        'import_id' => $importId,
                    ],
                ], 202);
            }

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            if (isset($storedPath)) {
                Storage::delete($storedPath);
            }

            Cache::put($cacheKey, array_merge(BackupImport::defaultProgress(), [
                'status' => 'failed',
                'message' => 'Import gagal.',
                'error' => $e->getMessage(),
            ]), now()->addHour());

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function importDataBackupProgress(string $importId)
    {
        return response()->json(
            Cache::get(
                BackupImport::progressCacheKey(auth()->id(), $importId),
                BackupImport::defaultProgress()
            )
        );
    }

}

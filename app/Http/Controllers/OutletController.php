<?php

namespace App\Http\Controllers;

use App\DataTables\OutletsDatatables;
use App\Http\Requests\OutletRequest;
use App\Models\Outlets;
use App\Models\User;
use Illuminate\Http\Request;

class OutletController extends Controller
{
    public function index(OutletsDatatables $dataTable)
    {
        return $dataTable->render('layouts.outlet.index');
    }


    public function create()
    {
        return view("layouts.outlet.outlet-modal", [
            "data" => new Outlets(),
            "action" => route("konfigurasi/outlets/store")
        ]);
    }

    public function store(OutletRequest $request)
    {
        $Outlets = new Outlets($request->validated());
        $Outlets->save();

        return responseSuccess(false);
    }

    public function edit(Outlets $outlet)
    {
        return view('layouts.outlet.outlet-modal', [
            'data' => $outlet,
            'action' => route('konfigurasi/outlets/update', $outlet),
        ]);
    }

    public function update(Outlets $outlet, OutletRequest $request)
    {
        $outlet->fill($request->validated());
        $outlet->save();

        return responseSuccess(true);
    }

    public function destroy(Outlets $outlet)
    {
        $outlet->delete();

        return responseSuccessDelete();
    }

    public function getAkun($outlet_id)
    {
        // Ambil semua pengguna  
        $users = User::where('role', 3)->get(['name', 'email', 'outlet_id']);

        // Filter pengguna berdasarkan outlet_id  
        $akuns = $users->filter(function ($user) use ($outlet_id) {
            $outletIds = json_decode($user->outlet_id); // Decode outlet_id  
            // dd($outletIds);
            return in_array($outlet_id, $outletIds); // Cek apakah outlet_id ada  
        })->values(); // Reset keys  

        return response()->json($akuns);
    }
}

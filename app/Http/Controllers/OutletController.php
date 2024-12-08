<?php

namespace App\Http\Controllers;

use App\DataTables\OutletsDatatables;
use App\Http\Requests\OutletRequest;
use App\Models\Outlets;
use Illuminate\Http\Request;

class OutletController extends Controller
{
    public function index(OutletsDatatables $dataTable){
        return $dataTable->render('layouts.outlet.index');
    }

    
    public function create(){
        return view("layouts.outlet.outlet-modal",[
            "data" => new Outlets(),
            "action" => route("konfigurasi/outlets/store")
        ]);
    }

    public function store(OutletRequest $request){
        $Outlets = new Outlets($request->validated());
        $Outlets->save();

        return responseSuccess(false);
        
    }

    public function edit(Outlets $outlet){
        return view('layouts.outlet.outlet-modal',[
            'data' => $outlet,
            'action' => route('konfigurasi/outlets/update', $outlet),
        ]);
    }

    public function update(Outlets $outlet,OutletRequest $request){
        $outlet->fill($request->validated());
        $outlet->save();

        return responseSuccess(true);
    }

    public function destroy(Outlets $outlet){
        $outlet->delete();

        return responseSuccessDelete();
    }

}

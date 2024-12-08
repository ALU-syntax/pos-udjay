<?php

namespace App\Http\Controllers;

use App\DataTables\TaxDatatables;
use App\Http\Requests\TaxRequest;
use App\Models\Outlets;
use App\Models\Taxes;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    public function index(TaxDatatables $taxDatatables){
        return $taxDatatables->render('layouts.tax.index');
    }

    public function create(){
        return view("layouts.tax.tax-modal", [
            "data" => new Taxes(),
            "action" => route("library/tax/store"),
            "outlets" => Outlets::all()
        ]);
    }

    public function store(TaxRequest $request){
        $validatedData = $request->validated();

        $data = [];
        foreach($validatedData['outlet_id'] as $outletId){
            $tax = new Taxes([
                'name' => $validatedData['name'],
                'amount' => $validatedData['amount'],
                'satuan' => $validatedData['satuan'],
                'outlet_id' => $outletId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $tax->save();
        }

        return responseSuccess(false);
    }

    public function edit(Taxes $tax){
        return view('layouts.tax.tax-modal',[
            'data' => $tax,
            'action' => route('library/tax/update', $tax),
            'outlets' => Outlets::all()
        ]);
    }

    public function update(TaxRequest $request, Taxes $tax){
        $tax->fill($request->validated());
        $tax['outlet_id'] = $request['outlet_id'][0];
        $tax->save();

        return responseSuccess(true);
    }

    public function destroy(Taxes $tax){
        $tax->delete();

        return responseSuccessDelete();
    }
}

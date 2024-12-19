<?php

namespace App\Http\Controllers;

use App\DataTables\DiscountDatatables;
use App\Models\Discount;
use App\Models\Outlets;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index(DiscountDatatables $datatable){
        return $datatable->render('layouts.discount.index', [
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
        ]);
    }

    public function create(){
        return view("layouts.discount.discount-modal",[
            "data" => new Discount(),
            "action" => route("library/discount/store"),
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
        ]);   
    }

    public function store(Request $request){
        $dataValidate = $request->validate([
            'name' => 'required',
            'type_input' => 'required',
            'satuan_discount_custom' => 'nullable',
            'amount' => 'nullable',
            'satuan' => 'nullable',
            'outlet_id' => 'required|array'
        ]);


        foreach($dataValidate['outlet_id'] as $outlet){

            if($dataValidate['type_input'] == "fixed"){
                $data = [
                    'name' => $dataValidate['name'],
                    'type_input' => $dataValidate['type_input'],
                    'satuan_discount_custom' => null,
                    'amount' => getAmount(strval($dataValidate['amount'])),
                    'satuan' => $dataValidate['satuan'],
                    'outlet_id' =>$outlet
                ];
            }else{
                $data = [
                    'name' => $dataValidate['name'],
                    'type_input' => $dataValidate['type_input'],
                    'satuan_discount_custom' => $dataValidate['satuan_discount_custom'],
                    'amount' => null,
                    'satuan' => null,
                    'outlet_id' =>$outlet
                ];
            }

            Discount::create($data);
        }

        return responseSuccess(false);
    }

    public function edit(Discount $discount){
        return view('layouts.discount.discount-modal',[
            'data' => $discount,
            'action' => route('library/discount/update', $discount->id),
        ]);

    }

    public function update(Request $request, Discount $discount) {
        $dataValidate = $request->validate([
            'name' => 'required',
            'type_input' => 'required',
            'satuan_discount_custom' => 'nullable',
            'amount' => 'nullable',
            'satuan' => 'nullable',
        ]);

        if($dataValidate['type_input'] == "fixed"){
            $data = [
                'name' => $dataValidate['name'],
                'type_input' => $dataValidate['type_input'],
                'satuan_discount_custom' => null,
                'amount' => getAmount(strval($dataValidate['amount'])),
                'satuan' => $dataValidate['satuan'],
            ];
        }else{
            $data = [
                'name' => $dataValidate['name'],
                'type_input' => $dataValidate['type_input'],
                'satuan_discount_custom' => $dataValidate['satuan_discount_custom'],
                'amount' => null,
                'satuan' => null,
            ];
        }

        $discount->update($data);

        return responseSuccess(true);
    }

    public function destroy(Discount $discount){
        $discount->delete();

        return responseSuccessDelete();
    }
}

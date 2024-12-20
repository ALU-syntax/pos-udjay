<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\PettyCash;
use App\Models\Product;
use App\Models\Taxes;
use Illuminate\Http\Request;

class KasirController extends Controller
{
    public function index(){
        $outletUser = auth()->user()->outlet_id;
        $dataOutletUser = json_decode($outletUser);

        $rounding = Checkout::find(1);
        // dd($rounding);

        $pajak = Taxes::where('outlet_id', $dataOutletUser[0])->get();

        return view('layouts.kasir.index', [
            'categorys' => Category::with('products')->get(),
            'pajak' => $pajak,
            'rounding' => $rounding
        ]);
    }

    public function findProduct(Product $product){
        $outletUser = auth()->user()->outlet_id;
        $dataOutletUser = json_decode($outletUser);

        $dataProduct = $product;
        $dataProduct['harga_jual'] = round($dataProduct['harga_jual']);

        $modifiers = $product->modifierGroups()->with(['modifier'])->get();

        $discounts = Discount::where('type_input', 'fixed')->where('outlet_id', $dataOutletUser[0])->get();
        return view('layouts.kasir.kasir-modal-product', [
            'data' => $dataProduct,
            'discounts' => $discounts,
            'modifiers' => $modifiers
        ]);
    }

    public function choosePayment(Request $request){
        $userData = auth()->user();
        $outletUser = json_decode($userData->outlet_id);

        $pettyCash = PettyCash::where('outlet_id', $outletUser[0])->where('close', null)->get();

        if(count($pettyCash) > 0){
            return view('layouts.kasir.modal-choose-payment');
        }else{
            return view('layouts.kasir.modal-petty-cash');
        }
    }

    public function pattyCash(Request $request){
        $userData = auth()->user();
        $outletUser = json_decode($userData->outlet_id);

        $data = [
            'outlet_id' => $outletUser[0],
            'amount_awal' => getAmount($request->saldo_awal),
            'user_id_started' => $userData->id,
            'open' => now()
        ];

        PettyCash::create($data);

        return responseSuccess(false, "Shift Berhasil dibuka");
    }
}

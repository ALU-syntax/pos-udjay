<?php

namespace App\Http\Controllers;

use App\Models\Checkout;
use Illuminate\Http\Request;

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
}

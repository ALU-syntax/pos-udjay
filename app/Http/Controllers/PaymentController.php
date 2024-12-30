<?php

namespace App\Http\Controllers;

use App\DataTables\PaymentDataTable;
use App\Http\Requests\PaymentRequest;
use App\Models\CategoryPayment;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(PaymentDataTable $datatable){
        return $datatable->render('layouts.payment.index');
    }

    public function create(){
        return view('layouts.payment.payment-modal',[
            'action' => Route('konfigurasi/payment/store'),
            'data' => new Payment(),
            'categoryPayment' => CategoryPayment::where('status', true)->get()
        ]);
    }

    public function store(PaymentRequest $request){
        $payment = new Payment($request->validated());
        $payment->save();

        return responseSuccess(false);
    }

    public function edit(Payment $payment){
        return view('layouts.payment.payment-modal',[
            'data' => $payment,
            'action' => route('konfigurasi/payment/update', $payment->id),
            'categoryPayment' => CategoryPayment::where('status', true)->get()
        ]);
    }

    public function update(PaymentRequest $request, Payment $payment){
        $payment->fill($request->validated());
        $payment->save();

        return responseSuccess(true);
    }

    public function destroy(Payment $payment){
        $payment->delete();

        return responseSuccessDelete();
    }
}

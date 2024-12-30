<?php

namespace App\Http\Controllers;

use App\DataTables\CategoryPaymentDataTable;
use App\Http\Requests\CategoryPaymentRequest;
use App\Models\CategoryPayment;

class CategoryPaymentController extends Controller
{
    public function index(CategoryPaymentDataTable $datatable){
        return $datatable->render('layouts.category_payment.index', [

        ]);
    }

    public function create(){
        return view('layouts.category_payment.category-payment-modal', [
            'action' => route('konfigurasi/category-payment/store'),
            'data' => new CategoryPayment()
        ]);
    }

    public function store(CategoryPaymentRequest $request){
        $categoryPayment = new CategoryPayment($request->validated());
        $categoryPayment->save();

        return responseSuccess(false);
    }

    public function edit(CategoryPayment $categoryPayment){
        return view('layouts.category_payment.category-payment-modal',[
            'data' => $categoryPayment,
            'action' => route('konfigurasi/category-payment/update', $categoryPayment->id)
        ]);
    }

    public function update(CategoryPaymentRequest $request, CategoryPayment $categoryPayment){
        $categoryPayment->fill($request->validated());
        $categoryPayment->save();

        return responseSuccess(true);
    }

    public function destroy(CategoryPayment $categoryPayment){
        $categoryPayment->delete();

        return responseSuccessDelete();
    }
}

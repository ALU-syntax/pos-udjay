<?php

namespace App\Http\Controllers;

use App\DataTables\CustomerDataTable;
use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(CustomerDataTable $datatable)
    {
        return $datatable->render('layouts.customer.index');
    }

    public function create()
    {
        return view('layouts.customer.customer-modal', [
            'data' => new Customer(),
            'action' => route('customer/store')
        ]);
    }

    public function store(CustomerRequest $request)
    {
        $customer = new Customer($request->validated());
        $customer->save();

        return responseSuccess(false);
    }

    public function edit(Customer $customer){
        return view('layouts.customer.customer-modal',[
            'data' => $customer,
            'action' => route('customer/update', $customer->id),
        ]);
    }

    public function update(CustomerRequest $request, Customer $customer){
        $customer->fill($request->validated());
        $customer->save();

        return responseSuccess(true);
    }

    public function destroy(Customer $customer){
        $customer->delete();

        return responseSuccessDelete();

    }
}

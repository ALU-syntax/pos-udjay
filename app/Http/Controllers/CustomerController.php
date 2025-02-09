<?php

namespace App\Http\Controllers;

use App\DataTables\CustomerDataTable;
use App\DataTables\ListRefereeDataTable;
use App\Http\Requests\CustomerRequest;
use App\Mail\CustomerRegistered;
use App\Models\Community;
use App\Models\Customer;
use App\Models\CustomerPoinExp;
use App\Models\CustomerReferral;
use App\Models\LevelMembership;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
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
            'action' => route('membership/customer/store'),
            'communities' => Community::all(),
            'customer' => Customer::all()
        ]);
    }

    public function store(CustomerRequest $request)
    {
        $customer = new Customer($request->validated());

        $lowestBenchmarkValue = LevelMembership::min('benchmark');
        $lowestBenchmarkRecords = LevelMembership::where('benchmark', $lowestBenchmarkValue)->first();

        $customer->level_memberships_id = $lowestBenchmarkRecords->id;
    
        $customer->save();

        if(isset($request['referral_id'])){
            $dataReferral = [
                'customer_id' => $customer->id,
                'referral_id' => $request['referral_id'],
                'user_id' => auth()->user()->id
            ];

            $customerReferral = new CustomerReferral($dataReferral);
            $customerReferral->save();

            $dataPointReferral = [
                'customer_id' => $request['referral_id'],
                'point' => 75,
                'referee_id' => $customer->id,
                'log' => 'mendapatkan poin dari referee sebesar 75 poin'
            ];

            $referralPoint = new CustomerPoinExp($dataPointReferral);
            $referralPoint->save();
        }

        $data = [
            'name' => $request['name'],
            'email' => $request['email'],
            'level_member' => $lowestBenchmarkRecords->name,
            'expired' => Carbon::parse($customer->created_at)->addYear()->format('d-m-Y')
        ];

        Mail::to($request['email'])->send(new CustomerRegistered($data));
        

        return responseSuccess(false);
    }

    public function edit(Customer $customer){
        return view('layouts.customer.customer-modal',[
            'data' => $customer,
            'action' => route('membership/customer/update', $customer->id),
            'communities' => Community::all()
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

    public function detail(Customer $customer){
        $customer->load(['community', 'referral']);
        return view('layouts.customer.detail-modal', [
            'data' => $customer,
        ]);
    }

    public function listReferee(Customer $customer, ListRefereeDataTable $datatable){
        return $datatable->with('customerId', $customer->id)->render('layouts.customer.list-referee-modal');
    }
}

<?php

namespace App\Http\Controllers;

use App\DataTables\CustomerDataTable;
use App\DataTables\DetailItemTransactionDataTable;
use App\DataTables\ListCustomerTransactionDataTable;
use App\DataTables\ListRefereeDataTable;
use App\Http\Requests\CustomerRequest;
use App\Mail\CustomerRegistered;
use App\Mail\RegistrasiMembershipKomunitas;
use App\Models\Community;
use App\Models\Customer;
use App\Models\CustomerPoinExp;
use App\Models\CustomerReferral;
use App\Models\HistoryExpMembershipLevel;
use App\Models\LevelMembership;
use App\Models\RewardConfirmation;
use App\Models\Transaction;
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
            'customer' => Customer::all(),
            'update' => false
        ]);
    }

    public function store(CustomerRequest $request)
    {
        $customer = new Customer($request->validated());

        $lowestBenchmarkValue = LevelMembership::min('benchmark');
        $lowestBenchmarkRecords = LevelMembership::where('benchmark', $lowestBenchmarkValue)->first();

        $customer->level_memberships_id = $lowestBenchmarkRecords->id;
        $customer->created_by = auth()->user()->id;

        $customer->save();

        if(isset($request['community_id'])){
            $community = Community::find($request['community_id']);
            $dataRegistCommunity = [
                'name' => $request['name'],
                'namaKomunitas' => $community->name,
                'poin' => 0,
                'exp' => 0,
                'expCommunity' => $community->exp,
                'expired' => Carbon::parse($customer->created_at)->addYear()->format('d-m-Y')
            ];

            Mail::to($request['email'])->send(new RegistrasiMembershipKomunitas($dataRegistCommunity));
        }

        if(isset($request['referral_id'])){
            $dataReferral = [
                'customer_id' => $customer->id,
                'referral_id' => $request['referral_id'],
                'user_id' => auth()->user()->id
            ];

            $customerReferral = new CustomerReferral($dataReferral);
            $customerReferral->save();

            $referallCustomer = Customer::find($request['referral_id']);
            $referallCustomer->point += 75;

            $referallCustomer->save();

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

        $historyMembership = new HistoryExpMembershipLevel([
            'customer_id' => $customer->id,
            'level_memberships_id' => $lowestBenchmarkRecords->id,
            'exp' => $lowestBenchmarkRecords->benchmark,
        ]);

        $historyMembership->save();


        return responseSuccess(false);
    }

    public function edit(Customer $customer){
        return view('layouts.customer.customer-modal',[
            'data' => $customer,
            'action' => route('membership/customer/update', $customer->id),
            'update' => true,
            'communities' => Community::all(),
            'customer' => Customer::all()
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

    public function detailCustomer(Customer $customer){
        $customer->load(['community', 'referral']);
        return view('layouts.customer.detail-modal', [
            'data' => $customer,
        ]);
    }

    public function listReferee(Customer $customer, ListRefereeDataTable $datatable){
        return $datatable->with('customerId', $customer->id)->render('layouts.customer.list-referee-modal');
    }

    public function detail(Customer $customer, ListCustomerTransactionDataTable $datatable){
        $customer->load(['transactions']);

        $transactionNominal = 0;
        foreach($customer->transactions as $transaction){
            $transactionNominal += $transaction->total;
        }

        return $datatable->with('customerId', $customer->id)->render('layouts.customer.detail',[
            'data' => $customer,
            'transactionNominal' => $transactionNominal
        ]);
    }

    public function detailTransaction(Transaction $transaction, DetailItemTransactionDataTable $datatable){

        return $datatable->with('transactionId', $transaction->id)->render('layouts.customer.list-item-transaction');
    }

    public function checkRewardConfirmation(Customer $customer){
        $customer->load(['levelMembership']);
        $listReward = $customer->levelMembership()->first()->with(['rewards'])->first();
        $listRewardAccept = RewardConfirmation::where('customer_id', $customer->id)->where('level_membership_id', $customer->levelMembership->id)->get();

        $listPhoto = [];
        $dataReward = [];

        foreach($listRewardAccept as $rewardAccept){
            $listPhoto[] = $rewardAccept->photo;
        }

        foreach($listReward->rewards as $reward){
            $tmpDataReward = [
                'id' => $reward->id,
                'name' => $reward->name,
                'accept' => false,
            ];

            foreach($listRewardAccept as $rewardAccept){

                if($rewardAccept->reward_memberships_id == $reward->id){
                    $tmpDataReward['accept'] = true;
                    continue;
                }
            }

            array_push($dataReward, $tmpDataReward);
        }

        // dd($dataReward);
        return view('layouts.customer.reward-confirmation-modal',[
            'data' => $dataReward,
            'action' => route('membership/customer/rewardConfirmation'),
            'customerId' => $customer->id,
            'levelMembershipId' => $customer->levelMembership()->first()->id,
            'listPhotos' => $listPhoto
        ]);
    }

    public function rewardConfirmation(Request $request){
        $dataValidated = $request->validate([
            'gambar' => 'required|image|mimes:jpeg,png,jpg|max:4096',
            'customer_id' => 'required',
        ],[
            'gambar.required' => "Foto Bukti wajib dimasukan"
        ]);


        if($request->has('accept')){
            $photo = null;

            if ($request->hasFile('gambar')) {
                $photo = $request->file('gambar')->store('public/reward_confirmation');
            }

            $data = [];

            foreach($request['reward_id'] as $item){
                $tmpData = [
                    'level_membership_id' => $request['level_membership_id'],
                    'reward_memberships_id' => $item,
                    'customer_id' => $dataValidated['customer_id'],
                    'user_id' => auth()->user()->id,
                ];

                if(isset($photo)){
                    $tmpData['photo'] = $photo;
                }

                array_push($data, $tmpData);
            }

            RewardConfirmation::insert($data);

            return responseSuccess(false, "Reward Berhasil diupdate");
        }

        return true;
    }

    public function historyPointUse(Customer $customer){
        $customer->load(['transactions']);

        $dataTransaction = [];
        foreach($customer->transactions()->get() as $transaction){
            $tmpDataTransaction = $transaction;
            $tmpDataTransaction['date_formated'] = Carbon::parse($transaction->created_at)->diffForHumans();
            $tmpDataTransaction['point'] = intval($transaction->total) / 100;

            $dataTransaction[] = $tmpDataTransaction;
        }
        return view('layouts.customer.history-point-use-modal', [
            'transactions' => $dataTransaction
        ]);
    }

}

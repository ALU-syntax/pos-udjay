<?php

namespace App\Http\Controllers;

use App\DataTables\CommunityDataTable;
use App\DataTables\CommunityExpExchangeDataTable;
use App\DataTables\DetailCommunityDataTable;
use App\Http\Requests\CommunityStore;
use App\Models\Community;
use App\Models\CommunityExpExchange;
use App\Models\Customer;
use App\Models\Outlets;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    public function index(CommunityDataTable $datatable){
        return $datatable->render('layouts.community.index');
    }

    public function create(){
        return view('layouts.community.community-modal', [
            'data' => new Community(),
            'action' => route('membership/community/store'),
            'customers' => Customer::all(),
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
        ]);
    }

    public function store(CommunityStore $request){
        $community = new Community($request->validated());
        $community->user_id = auth()->user()->id; 
        $community->save();

        return responseSuccess(false);
    }

    public function edit(Community $community){
        return view('layouts.community.community-modal', [
            'data' => $community,
            'action' => route('membership/community/update', $community->id),
            'customers' => Customer::all(),
        ]);
    }

    public function destroy(Community $community){
        $community->delete();

        return responseSuccessDelete();
    }

    public function update(Community $community, CommunityStore $request){
        $community->fill($request->validated());
        $community->save();

        if($community->customer_id != $request->customer_id){
            $newTeamLeader = Customer::find($request->customer_id);
            $newTeamLeader->community_id = $community->id;

            $newTeamLeader->save();
        }

        return responseSuccess(true);
    }

    public function detail($id, DetailCommunityDataTable $datatable)
    {
        $community = Community::find($id);

        $sumTransaction = 0;
        $transactionNominal = 0;

        foreach($community->customers() as $customer){
            $sumTransaction += count($customer->transactions);
            foreach($customer->transactions() as $transaction){
                $transactionNominal += $transaction->total;
            }
        }

        $resultTransactionNominal = $transactionNominal == 0 ? "Rp. 0" : formatRupiah(strval($transactionNominal), "Rp. ");

        // Mengembalikan view dengan data komunitas dan DataTable
        return $datatable->with('communityId', $id)->render('layouts.community.detail', [
            'data' => $community,
            'sumTransaction' => $sumTransaction,
            'transactionNominal' => $resultTransactionNominal
        ]);
    }

    public function createExchangeExp($id){
        $community = Community::find($id);
        return view('layouts.community.use-exp-modal', [
            'data' => new CommunityExpExchange,
            'action' => route('membership/community/store'),
            'maxLimitExp' => $community->exp,
            'idCommunity' => $id,
        ]);
    }

    public function storeExchangeExp(Request $request){
        $dataValidated = $request->validate([
            'exp_used' => 'numeric|required|min:1',
            'catatan' => 'string|required',
            'idCommunity' => 'numeric|required',
        ]);

        $dataExchange = [
            'community_id' => $dataValidated['idCommunity'],
            'user_id' => auth()->user()->id,
            'exp_use'=> $dataValidated['exp_used'],
            'catatan' => $dataValidated['catatan'],
        ];

        $community = Community::find($dataValidated['idCommunity']);

        $result = $community->exp - $dataValidated['exp_used'];
        $community->exp = $result;

        $communityExchange = new CommunityExpExchange($dataExchange);

        return responseSuccess(false);
    }

    public function historyUseExp($id, CommunityExpExchangeDataTable $datatable){
        return $datatable->with('idCommunity', $id)->render('layouts.community.history-use-exp-modal');
    }

}

<?php

namespace App\Http\Controllers;

use App\DataTables\OpenBillDataTable;
use App\Models\OpenBill;
use App\Models\Outlets;
use App\Models\Taxes;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpenBillController extends Controller
{
    public function index(OpenBillDataTable $datatable){
        $userOutlet = json_decode(auth()->user()->outlet_id);
        $openBill = OpenBill::with(['outlet'])->where('outlet_id', $userOutlet[0])
        ->get();

        return $datatable->render('layouts.reports.openbill', [
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
            "data" => $openBill,
        ]);
    }

    public function getOpenBillData(Request $request){
        $idOutlet = $request->input('idOutlet');

        $openBill = OpenBill::with(['outlet'])->where('outlet_id', $idOutlet)->get();

        return response()->json([
            'data' => $openBill
        ]);
    }

    public function getOpenBillDataDetail(Request $request){
        $idOpenBill = $request->input('idOpenBill');
        $idOutlet = $request->input('idOutlet');
        $openBill = OpenBill::withTrashed()->find($idOpenBill);
        $openBill->load(['user', 'item' => function($itemOpenBill){
            $itemOpenBill->withTrashed()->with(['variant', 'product']);
        }]);

        $tax = Taxes::where('outlet_id', $idOutlet)->get();
        $totalTax = 0;
        foreach($tax as $taxItem){
            $totalTax += intval($taxItem->amount);
        }
        $dataPajak = [
            'value' => $totalTax,
        ];

        $openBill->create_formated = Carbon::parse($openBill->created_at)->format('d-M-Y H:i');
        return response()->json([
            'data' => $openBill,
            'pajak' => $dataPajak
        ]);
    }

    public function deleteOpenBill($idOpenBill){
        $openBill = OpenBill::withTrashed()->find($idOpenBill);
        $openBill->fill([
            'delete_permanen' => Carbon::now(),
            'id_user_deleted' => auth()->user()->id
        ]);

        $openBill->save();
    }
}

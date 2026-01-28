<?php

namespace App\Http\Controllers;

use App\DataTables\OpenBillDataTable;
use App\DataTables\OpenBillDeletedDataTable;
use App\Models\OpenBill;
use App\Models\Outlets;
use App\Models\Taxes;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpenBillController extends Controller
{
    public function index(OpenBillDataTable $openBillDataTable, OpenBillDeletedDataTable $openBillDeletedDataTable){
        $userOutlet = json_decode(auth()->user()->outlet_id);
        $openBill = OpenBill::with(['outlet'])->where('outlet_id', $userOutlet[0])
        ->get();

        // Check if request is Ajax specifically for OpenBillDataTable
        if (request()->ajax() && request()->has('datatable') && request('datatable') == 'openbill') {
            return $openBillDataTable->ajax();
        }

        // Check if request is Ajax specifically for OpenBillDeletedDataTable
        if (request()->ajax() && request()->has('datatable') && request('datatable') == 'openbill-deleted') {
            return $openBillDeletedDataTable->ajax();
        }

         // Ambil HtmlBuilder dari kedua DataTable
        $datatable = $openBillDataTable->html();
        $openBillDeletedHtml = $openBillDeletedDataTable->html();

        return view('layouts.reports.openbill', [
            'dataTable' => $datatable,
            "openBillDeletedDataTable" => $openBillDeletedHtml,
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
        $openBill->load(['user', 'transactions', 'item' => function($itemOpenBill){
            $itemOpenBill->withTrashed()->with(['variant' => function($variant){
                $variant->withTrashed();
            }, 'product', 'itemTransactions']);
        }]);

        // Add create_formated to each item after loading
        $openBill->item->map(function($bill) {
            $bill->create_formated = Carbon::parse($bill->created_at)->format('d-M-Y H:i');
        });

        $tax = Taxes::where('outlet_id', $openBill->outlet_id)->get();
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

    public function getOpenBillDataTable(OpenBillDataTable $openBillDataTable)
    {
        return $openBillDataTable->ajax();
    }

    public function getOpenBillDeletedData(OpenBillDeletedDataTable $openBillDeletedDataTable)
    {
        return $openBillDeletedDataTable->ajax();
    }

    public function restoreOpenBill(Request $request)
    {

        $openBill = OpenBill::withTrashed()->find($request->idOpenBill);
        $openBill->delete_permanen = null;
        $openBill->id_user_deleted = null;
        $openBill->save();

        return response()->json(['message' => 'Open Bill restored successfully']);
    }

}

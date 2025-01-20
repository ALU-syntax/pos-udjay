<?php

namespace App\Http\Controllers;

use App\DataTables\TransactionsDataTable;
use App\Models\Outlets;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionsController extends Controller
{
    public function index(TransactionsDataTable $datatable){
        $userOutlet = json_decode(auth()->user()->outlet_id);
        $transaction = Transaction::with(['outlet'])->where('outlet_id', $userOutlet[0])->whereDate('created_at', Carbon::today()) // Menggunakan Carbon untuk mendapatkan tanggal hari ini  
        ->get();

        return $datatable->render('layouts.reports.transaction', [
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
            "data" => $transaction,
        ]);
    }

    public function getTransactionData(Request $request){
        $idOutlet = $request->input('idOutlet');
        $dates = explode(' - ', $request->input('date'));

        if(count($dates) == 2){
            $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();
        }

        $transaction = Transaction::with(['outlet'])->where('outlet_id', $idOutlet)->whereBetween('created_at', [$startDate, $endDate])->get();


        return response()->json([
            'data' => $transaction
        ]);
    }
}

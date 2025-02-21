<?php

namespace App\Http\Controllers;

use App\DataTables\TransactionsDataTable;
use App\Models\Outlets;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function getTransactionDataDetail(Request $request){
        $idTransaction = $request->input('idTransaction');
        $transaction = Transaction::find($idTransaction);
        $transaction->load(['user', 'itemTransaction' => function($itemTransaction){
            $itemTransaction->select(
                'variant_id',
                DB::raw('COUNT(*) as total_count'),
                'product_id',
                'discount_id',
                'modifier_id',
                'promo_id',
                'sales_type_id',
                'transaction_id',
                'catatan',
                'reward_item'
            )
            ->with(['variant', 'product'])
            ->groupBy('variant_id', 'product_id', 'discount_id', 'modifier_id', 'promo_id', 'sales_type_id', 'transaction_id', 'catatan', 'deleted_at', 'created_at', 'updated_at', 'reward_item');
        }]);

        $transaction->create_formated = Carbon::parse($transaction->created_at)->format('d-M-Y H:i');
        return response()->json([
            'data' => $transaction
        ]);
    }

    public function showReceipt(Request $request, Transaction $idTransaction){
        return view('layouts.reports.struk',[
            'data' => $idTransaction
        ]);
    }
}

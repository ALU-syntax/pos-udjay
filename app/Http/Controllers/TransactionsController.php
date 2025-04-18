<?php

namespace App\Http\Controllers;

use App\DataTables\TransactionsDataTable;
use App\Mail\ResendReceiptMail;
use App\Models\Outlets;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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
        $idTransaction->total_pajak = json_decode($idTransaction->total_pajak);
        $idTransaction->diskon_all_item = json_decode($idTransaction->diskon_all_item);
        $idTransaction->load(['outlet', 'user', 'customer','itemTransaction' => function($itemTransaction){
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
            ->groupBy('variant_id', 'product_id', 'discount_id', 'modifier_id', 'promo_id', 'sales_type_id', 'transaction_id', 'catatan', 'reward_item');
        }]);
        $idTransaction->tanggal_beli = Carbon::parse($idTransaction->created_at)->format('d M Y');
        $idTransaction->waktu_beli = Carbon::parse($idTransaction->created_at)->format('H:i');

        $dataTransaction = $idTransaction;

        $totalNominalPajak = 0;
        $totalNominalDiskon = 0;
        $totalNominalModifier = 0;
        $subTotal = 0;

        foreach($idTransaction->total_pajak as $pajak){
            $totalNominalPajak += $pajak->total;
        }

        $idTransaction->total_nominal_pajak = $totalNominalPajak;

        foreach($idTransaction->diskon_all_item as $diskonAllItem){
            $totalNominalDiskon += $diskonAllItem->value;
        }

        // dd($idTransaction->itemTransaction()->with(['variant'])->get());

        foreach($idTransaction->itemTransaction()->with(['variant'])->get() as $item){
            $tmpDataDiskonItem = json_decode($item->discount_id);
            $tmpDataModifierItem = json_decode($item->modifier_id);
            $subTotal += $item->variant ? $item->variant->harga : ($item->harga ? $item->harga : 0);

            foreach($tmpDataModifierItem as $modifier){
                $totalNominalModifier += $modifier->harga;
                $subTotal += $modifier->harga;
            }

            foreach($tmpDataDiskonItem as $diskonItem){
                $totalNominalDiskon += $diskonItem->result;
            }
        }

        $idTransaction->total_nominal_diskon = $totalNominalDiskon;
        $idTransaction->sub_total = $subTotal;
        $idTransaction->total_nominal_modifier = $totalNominalModifier;
        // dd($idTransaction);
        return view('layouts.reports.struk',[
            'data' => $idTransaction
        ]);
    }

    public function destroy(Transaction $idTransaction){
        $idTransaction->itemTransaction()->delete();
        $idTransaction->delete();

        return responseSuccessDelete();
    }

    public function modalResendReceipt($idTransaction){

        return view("layouts.reports.modal-resend-receipt", [
            "action" => route('report/transaction/resendReceipt', $idTransaction)
        ]);
    }

    public function resendReceipt(Request $request, Transaction $idTransaction){
        // dd($idTransaction);
        $idTransaction->total_pajak = json_decode($idTransaction->total_pajak);
        $idTransaction->diskon_all_item = json_decode($idTransaction->diskon_all_item);
        $idTransaction->load(['outlet', 'user', 'customer','itemTransaction' => function($itemTransaction){
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
            ->groupBy('variant_id', 'product_id', 'discount_id', 'modifier_id', 'promo_id', 'sales_type_id', 'transaction_id', 'catatan', 'reward_item');
        }]);
        $idTransaction->tanggal_beli = Carbon::parse($idTransaction->created_at)->format('d M Y');
        $idTransaction->waktu_beli = Carbon::parse($idTransaction->created_at)->format('H:i');

        $dataTransaction = $idTransaction;

        $totalNominalPajak = 0;
        $totalNominalDiskon = 0;
        $totalNominalModifier = 0;
        $subTotal = 0;

        foreach($idTransaction->total_pajak as $pajak){
            $totalNominalPajak += $pajak->total;
        }

        $idTransaction->total_nominal_pajak = $totalNominalPajak;

        foreach($idTransaction->diskon_all_item as $diskonAllItem){
            $totalNominalDiskon += $diskonAllItem->value;
        }

        // dd($idTransaction->itemTransaction()->with(['variant'])->get());

        foreach($idTransaction->itemTransaction()->with(['variant'])->get() as $item){
            $tmpDataDiskonItem = json_decode($item->discount_id);
            $tmpDataModifierItem = json_decode($item->modifier_id);
            $subTotal += $item->variant ? $item->variant->harga : ($item->harga ? $item->harga : 0);

            foreach($tmpDataModifierItem as $modifier){
                $totalNominalModifier += $modifier->harga;
                $subTotal += $modifier->harga;
            }

            foreach($tmpDataDiskonItem as $diskonItem){
                $totalNominalDiskon += $diskonItem->result;
            }
        }

        $idTransaction->total_nominal_diskon = $totalNominalDiskon;
        $idTransaction->sub_total = $subTotal;
        $idTransaction->total_nominal_modifier = $totalNominalModifier;
        $email = $request->email;
        Mail::to($email)->send(new ResendReceiptMail($idTransaction));

        return responseSuccess(false);
    }

}

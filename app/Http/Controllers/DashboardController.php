<?php

namespace App\Http\Controllers;

use App\Models\Outlets;
use App\Models\Transaction;
use App\Models\VariantProduct;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(){
        $startDate = Carbon::now()->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $dataTransaction = Transaction::with(['itemTransaction'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get(); // Ambil data sesuai kebutuhan

        $grossSales = 0;
        $discount = 0;
        $netSales = 0;

        foreach ($dataTransaction as $transaction) {
            $discount += $transaction->total_diskon;

            $totalTax = 0;
            foreach (json_decode($transaction->total_pajak) as $itemPajak) {
                $totalTax += $itemPajak->total;
            }
            $grossSales += $transaction->total + $transaction->total_diskon - $totalTax;

            $netSales += $transaction->total - $totalTax;
        }

        $outlets = Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get();

         // Inisialisasi array data per outlet, tiap outlet punya array 24 jam dengan nilai 0
        $hourlyGrossSalesPerOutlet = [];
        foreach ($outlets as $outlet) {
            $hourlyGrossSalesPerOutlet[$outlet->id] = array_fill(0, 24, 0);
        }

        // Hitung gross sales per jam per outlet
        foreach ($dataTransaction as $transaction) {
            $totalTax = 0;
            $pajakItems = json_decode($transaction->total_pajak);
            if (is_array($pajakItems)) {
                foreach ($pajakItems as $itemPajak) {
                    $totalTax += $itemPajak->total;
                }
            }

            $grossSalesPerTransaction = $transaction->total + $transaction->total_diskon - $totalTax;
            $hour = $transaction->created_at->hour;
            $outletId = $transaction->outlet_id;

            if (isset($hourlyGrossSalesPerOutlet[$outletId])) {
                $hourlyGrossSalesPerOutlet[$outletId][$hour] += $grossSalesPerTransaction;
            }
        }

        // Buat array label jam "00" sampai "23"
        $hours = [];
        for ($i = 0; $i < 24; $i++) {
            $hours[] = str_pad($i, 2, '0', STR_PAD_LEFT);
        }

        // dd($hourlyGrossSales);

        return view('dashboard',[
            "outlets" => $outlets,
            "grossSales" => $grossSales,
            "netSales" => $netSales,
            "transactions" => count($dataTransaction),
            "hourlyGrossSalesPerOutlet" => $hourlyGrossSalesPerOutlet,
            "hours" => $hours,
        ]);
    }

    public function getDataSummary(Request $request){
        $startDate = Carbon::now()->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $dates = explode(' - ', $request->input('date'));
        if (count($dates) == 2) {
            $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();
        } else {
            // Tetapkan tanggal default jika input 'date' hilang atau tidak valid
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }

        $outlet = $request->input('outlet');

        // dd($outlet);
        if($outlet == "all"){
            $dataTransaction = Transaction::with(['itemTransaction'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get(); // Ambil data sesuai kebutuhan

            $outlets = Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get();
        }else{
            $dataTransaction = Transaction::with(['itemTransaction'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('outlet_id', $outlet)->get(); // Ambil data sesuai kebutuhan

            $outlets = Outlets::where('id', $outlet)->get();
        }

        $grossSales = 0;
        $discount = 0;
        $netSales = 0;

        foreach ($dataTransaction as $transaction) {
            $discount += $transaction->total_diskon;

            $totalTax = 0;
            foreach (json_decode($transaction->total_pajak) as $itemPajak) {
                $totalTax += $itemPajak->total;
            }
            $grossSales += $transaction->total + $transaction->total_diskon - $totalTax;

            $netSales += $transaction->total - $totalTax;
        }

         // Inisialisasi array data per outlet, tiap outlet punya array 24 jam dengan nilai 0
        $hourlyGrossSalesPerOutlet = [];
        foreach ($outlets as $outlet) {
            $hourlyGrossSalesPerOutlet[$outlet->id] = array_fill(0, 24, 0);
        }

        // Hitung gross sales per jam per outlet
        foreach ($dataTransaction as $transaction) {
            $totalTax = 0;
            $pajakItems = json_decode($transaction->total_pajak);
            if (is_array($pajakItems)) {
                foreach ($pajakItems as $itemPajak) {
                    $totalTax += $itemPajak->total;
                }
            }

            $grossSalesPerTransaction = $transaction->total + $transaction->total_diskon - $totalTax;
            $hour = $transaction->created_at->hour;
            $outletId = $transaction->outlet_id;

            if (isset($hourlyGrossSalesPerOutlet[$outletId])) {
                $hourlyGrossSalesPerOutlet[$outletId][$hour] += $grossSalesPerTransaction;
            }
        }

        // Buat array label jam "00" sampai "23"
        $hours = [];
        for ($i = 0; $i < 24; $i++) {
            $hours[] = str_pad($i, 2, '0', STR_PAD_LEFT);
        }

        return response()->json([
            "outlets" => $outlets,
            "grossSales" => $grossSales,
            "netSales" => $netSales,
            "transactions" => count($dataTransaction),
            "hourlyGrossSalesPerOutlet" => $hourlyGrossSalesPerOutlet,
            "hours" => $hours,
        ]);
    }
}

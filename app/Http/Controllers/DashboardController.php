<?php

namespace App\Http\Controllers;

use App\Models\Outlets;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\VariantProduct;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $dataTransaction = Transaction::with(['itemTransaction'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('outlet_id', $outlet)->get(); // Ambil data sesuai kebutuhan

        $outlets = Outlets::where('id', $outlet)->get();

        $grossSales = 0;
        $discount = 0;
        $netSales = 0;

        foreach ($dataTransaction as $transaction) {
            $discount += $transaction->total_diskon;

            $totalTax = 0;
            foreach (json_decode($transaction->total_pajak) as $itemPajak) {
                $totalTax += $itemPajak->total;
            }
            $grossSales += $transaction->total;

            $netSales += $transaction->total - $discount;
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

    // public function getDataOutletCompare(Request $request){
    //     $startDate = Carbon::now()->startOfDay();
    //     $endDate = Carbon::now()->endOfDay();

    //     $dates = explode(' - ', $request->input('date'));
    //     if (count($dates) == 2) {
    //         $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
    //         $endDate = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();
    //     } else {
    //         // Tetapkan tanggal default jika input 'date' hilang atau tidak valid
    //         $startDate = Carbon::now()->startOfDay();
    //         $endDate = Carbon::now()->endOfDay();
    //     }

    //     $outlet = $request->input('outlet');

    //     $listData = [];
    //     if(count($outlet)){
    //         foreach($outlet as $dataOutlet){
    //             $getOutlet = Outlets::find($dataOutlet);
    //             $dataTransaction = Transaction::with(['itemTransaction'])
    //                     ->whereBetween('created_at', [$startDate, $endDate])
    //                     ->where('outlet_id', $dataOutlet)->get(); // Ambil data sesuai kebutuhan

    //             $grossSales = 0;
    //             $discount = 0;
    //             $netSales = 0;

    //             foreach ($dataTransaction as $transaction) {
    //                 $discount += $transaction->total_diskon;

    //                 $totalTax = 0;
    //                 foreach (json_decode($transaction->total_pajak) as $itemPajak) {
    //                     $totalTax += $itemPajak->total;
    //                 }
    //                 $grossSales += $transaction->total + $transaction->total_diskon;

    //                 $netSales += $transaction->total;
    //             }

    //             // Query untuk mendapatkan variant_id dan jumlah kemunculan, urut dari yang terbanyak
    //             $topVariants = TransactionItem::select('product_id','variant_id', DB::raw('COUNT(*) as total'))
    //             ->whereNotNull('variant_id') // pastikan variant_id tidak null
    //             ->whereBetween('created_at', [$startDate, $endDate])
    //             ->groupBy('variant_id','product_id')
    //             ->orderByDesc('total')
    //             ->with(['variant', 'product', 'transaction' => function($trans) use($dataOutlet){
    //                 return $trans->where('outlet_id', $dataOutlet);
    //             }]) // eager load data variant agar bisa langsung akses detail variant
    //             ->limit(3)
    //             ->get();


    //             $averageSalesPerTransaction = count($dataTransaction) ? $grossSales / count($dataTransaction) : 0;
    //             // dd($averageSalesPerTransaction);
    //             $grossMargin = $grossSales ? ($netSales / $grossSales) * 100 : 0;
    //             $tmpData = [
    //                 'outlet' => $getOutlet->name,
    //                 'grossSales' => $grossSales,
    //                 'netSales' => $netSales,
    //                 'transactions' => count($dataTransaction),
    //                 'averageSales' => round($averageSalesPerTransaction),
    //                 'grossMargin' => round($grossMargin),
    //                 'topThreeItem' => $topVariants
    //             ];

    //             array_push($listData, $tmpData);
    //         }

    //         return response()->json([
    //             'data' => $listData
    //         ]);
    //     }
    // }

    public function getDataOutletCompare(Request $request){
        $startDate = Carbon::now()->startOfDay();
        $endDate   = Carbon::now()->endOfDay();

        $dates = explode(' - ', $request->input('date'));
        if (count($dates) == 2) {
            $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
            $endDate   = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();
        }

        $outletIds = (array) $request->input('outlet', []);

        $listData = [];
        if (count($outletIds)) {
            foreach ($outletIds as $outletId) {
                $getOutlet = Outlets::find($outletId);

                // Ambil transaksi per outlet & rentang tanggal
                $dataTransaction = Transaction::with(['itemTransaction'])
                    ->where('outlet_id', $outletId)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get();

                $grossSales = 0;
                $discount   = 0;
                $netSales   = 0;

                foreach ($dataTransaction as $transaction) {
                    $discount += $transaction->total_diskon;

                    $totalTax = 0;
                    foreach (json_decode($transaction->total_pajak) as $itemPajak) {
                        $totalTax += $itemPajak->total;
                    }

                    // asumsi total = net + diskon (tanpa pajak)
                    $grossSales += $transaction->total + $transaction->total_diskon;
                    $netSales   += $transaction->total;
                }

                // --- AGREGASI ITEM: TOP 3 & DOWN 3 ---
                // Catatan: filter outlet & tanggalnya lewat whereHas('transaction', ...)
                $baseVariantQuery = TransactionItem::query()
                    ->select([
                        'product_id',
                        'variant_id',
                        DB::raw('COUNT(*) as qty')
                    ])
                    ->whereNotNull('variant_id') // kalau mau gabungkan yang tanpa variant, lihat catatan di bawah
                    ->whereHas('transaction', function($q) use ($outletId, $startDate, $endDate) {
                        $q->where('outlet_id', $outletId)
                        ->whereBetween('created_at', [$startDate, $endDate]);
                    })
                    ->groupBy('product_id', 'variant_id');

                // 3 terbanyak
                $topThreeItem = (clone $baseVariantQuery)
                    ->orderByDesc('qty')
                    ->with(['variant:id,name', 'product:id,name'])
                    ->limit(3)
                    ->get()
                    ->map(function($row){
                        return [
                            'product_id' => $row->product_id,
                            'product'    => $row->product->name ?? null,
                            'variant_id' => $row->variant_id,
                            'variant'    => $row->variant->name ?? null,
                            'qty'        => (int) $row->qty,
                        ];
                    });

                // 3 tersedikit (di antara yang terjual pada periode tsb)
                $downThreeItem = (clone $baseVariantQuery)
                    ->orderBy('qty', 'asc')
                    ->with(['variant:id,name', 'product:id,name'])
                    ->limit(3)
                    ->get()
                    ->map(function($row){
                        return [
                            'product_id' => $row->product_id,
                            'product'    => $row->product->name ?? null,
                            'variant_id' => $row->variant_id,
                            'variant'    => $row->variant->name ?? null,
                            'qty'        => (int) $row->qty,
                        ];
                    });

                // Ringkasan outlet
                $averageSalesPerTransaction = $dataTransaction->count()
                    ? $grossSales / $dataTransaction->count()
                    : 0;

                $grossMargin = $grossSales
                    ? ($netSales / $grossSales) * 100
                    : 0;

                $listData[] = [
                    'outlet'        => $getOutlet->name ?? ('Outlet #'.$outletId),
                    'grossSales'    => $grossSales,
                    'netSales'      => $netSales,
                    'transactions'  => $dataTransaction->count(),
                    'averageSales'  => round($averageSalesPerTransaction),
                    'grossMargin'   => round($grossMargin),
                    'topThreeItem'  => $topThreeItem,
                    'downThreeItem' => $downThreeItem,
                ];
            }

            return response()->json(['data' => $listData]);
        }

        return response()->json(['data' => []]);
    }
}

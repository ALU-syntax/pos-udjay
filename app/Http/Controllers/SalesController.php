<?php

namespace App\Http\Controllers;

use App\Models\CategoryPayment;
use App\Models\Outlets;
use App\Models\Transaction;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;

class SalesController extends Controller
{

    public function getSalesSummary()
    {
        $data = Transaction::all(); // Ambil data sesuai kebutuhan  
        return DataTables::of($data)->make(true);
    }

    public function getPaymentMethodSales(Request $request)
    {
        $dates = explode(' - ', $request->input('date'));
        if (count($dates) == 2) {
            $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();
        }

        $outlet = $request->input('outlet');

        $data = CategoryPayment::with(['payment' => function ($payment) use ($startDate, $endDate, $outlet) {
            $payment->with(['transactions' => function ($transaction) use ($startDate, $endDate, $outlet) {
                $transaction->whereBetween('created_at', [$startDate, $endDate])->where('outlet_id', $outlet);
                // $transaction->whereDate('created_at', Carbon::yesterday())->where('outlet_id', $outlet);
            }]);
        }, 'transactions' => function ($transaction) use ($startDate, $endDate, $outlet) {
            $transaction->whereBetween('created_at', [$startDate, $endDate])->where('outlet_id', $outlet);
            // $transaction->whereDate('created_at', Carbon::yesterday())->where('outlet_id', $outlet);
        }])->get();

        // Format data untuk dikembalikan  
        $result = [];
        foreach ($data as $category) {
            $tmpData = [];
            if ($category->name == "Cash" || $category->id == 1) {
                $tmpData['payment_method'] = $category->name;
                $tmpData['number_of_transactions'] = count($category->transactions);
                $tmpData['parent'] = true;
                $totalCollected = 0;

                foreach ($category->transactions as $transaction) {
                    $totalCollected += $transaction->total;
                }

                $tmpData['total_collected'] = $totalCollected;

                array_push($result, $tmpData);
            } else {
                $tmpData['payment_method'] = $category->name;
                $tmpData['number_of_transactions'] = "";
                $tmpData['total_collected'] = "";
                $tmpData['parent'] = true;

                array_push($result, $tmpData);

                foreach ($category->payment as $payment) {
                    $tmpData['payment_method'] = $payment->name;
                    $tmpData['number_of_transactions'] = count($payment->transactions);
                    $tmpData['parent'] = false;
                    $paymentTotalCollected = 0;

                    foreach ($payment->transactions as $paymentTransaction) {
                        $paymentTotalCollected += $paymentTransaction->total;
                    }
                    $tmpData['total_collected'] = $paymentTotalCollected;

                    array_push($result, $tmpData);
                }
            }
        }

        return response()->json($result);
    }
    public function index()
    {
        return view('layouts.sales.index', [
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
        ]);
    }
}

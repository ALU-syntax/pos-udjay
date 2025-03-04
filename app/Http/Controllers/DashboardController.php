<?php

namespace App\Http\Controllers;

use App\Models\VariantProduct;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(){
        $startDate = Carbon::now()->startOfDay();
        $endDate = Carbon::now()->endOfDay();



        $data = VariantProduct::with(['itemTransaction' => function ($transaction) use ($startDate, $endDate) {
            $transaction->with(['transaction' => function ($query){
                $query->where('patty_cash_id', 1);
            }]);
        }, 'product.category'])->whereHas('product', function ($query)  {
            $query->where('outlet_id', 1);
        })->get();
        // dd($data);
        return view('dashboard');
    }
}

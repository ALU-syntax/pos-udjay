<?php

namespace App\Http\Controllers;

use App\DataTables\PromoDatatables;
use App\Models\Category;
use App\Models\Outlets;
use App\Models\Product;
use App\Models\Promo;
use App\Models\SalesType;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    public function index(PromoDatatables $datatables){
        return $datatables->render('layouts.promo.index');
    }

    public function create(){
        return view('layouts.promo.promo-modal',[
            'action' => route("library/promo/store"),
            'data' =>  new Promo(),
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
            'salesTypes' => SalesType::all(),
            // 'products' => Product::whereIn('outlet_id', json_decode(auth()->user()->outlet_id))->get(),
            'categorys' => Category::all()
        ]);
    }
}

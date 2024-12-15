<?php

namespace App\Http\Controllers;

use App\DataTables\PromoDatatables;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    public function index(PromoDatatables $datatables){
        return $datatables->render('layouts.promo.index');
    }
}

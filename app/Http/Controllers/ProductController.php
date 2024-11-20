<?php

namespace App\Http\Controllers;

use App\DataTables\ProductDataTable;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(ProductDataTable $productDataTable){
        return $productDataTable->render("layouts.product.index");
    }

    public function create(){
        
    }
}

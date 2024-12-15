<?php

namespace App\Http\Controllers;

use App\DataTables\ProductDataTable;
use App\Http\Requests\ProductStoreRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\File;

class ProductController extends Controller
{
    public function index(ProductDataTable $productDataTable){
        return $productDataTable->render("layouts.product.index");
    }

    public function create(){
        return view("layouts.product.product-modal",[
            "data" => new Product(),
            "action" => route("library/product/store"),
            "categorys" => Category::all()
        ]);
    }

        public function store(ProductStoreRequest $request){
            $product = new Product($request->validated());
            $product->harga_jual = getAmount($request->harga_jual);
            $product->harga_modal = getAmount($request->harga_modal);
            if ($request->hasFile('photo')) {
                $product->photo = $request->file('photo')->store('product');
            }
            $product->save();

            return responseSuccess(false);
        }

    public function edit(Product $product){
        return view('layouts.product.product-modal',[
            'data' => $product,
            'action' => route('library/product/update', $product->id),
            "categorys" => Category::all()
        ]);
    }

    public function update(ProductStoreRequest $request, Product $product){
        $product->fill($request->validated());
        $product->harga_jual = getAmount($request->harga_jual);
        $product->harga_modal = getAmount($request->harga_modal);
        if(!empty($request->file('photo'))){
            $filePath = public_path('uploads/product/' . $product->photo);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }

            $product->photo = $request->file('photo')->store('product');
        }

        $product->save();

        return responseSuccess(true);
    }

    public function destroy(Product $product){
        $product->delete();

        return responseSuccessDelete();
    }
}

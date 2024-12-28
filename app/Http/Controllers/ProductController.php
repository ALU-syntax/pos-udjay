<?php

namespace App\Http\Controllers;

use App\DataTables\ProductDataTable;
use App\Http\Requests\ProductStoreRequest;
use App\Models\Category;
use App\Models\Outlets;
use App\Models\Product;
use Illuminate\Support\Facades\File;

class ProductController extends Controller
{
    public function index(ProductDataTable $productDataTable)
    {
        return $productDataTable->render("layouts.product.index",[
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
        ]);
    }

    public function create()
    {
        return view("layouts.product.product-modal", [
            "data" => new Product(),
            "action" => route("library/product/store"),
            "categorys" => Category::all(),
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
        ]);
    }

    public function store(ProductStoreRequest $request)
    {
        $validatedData = $request->validated();

        foreach ($validatedData['outlet_id'] as $outlet) {
            $dataProduct = [
                "name" => $validatedData['name'],
                'category_id' => $validatedData['category_id'],
                'harga_jual' => getAmount($validatedData['harga_jual']),
                'harga_modal' => getAmount($validatedData['harga_modal']),
                'stock' => $validatedData['stock'],
                'outlet_id' => $outlet,
                'status' => $validatedData['status']
            ];

            if ($request->hasFile('photo')) {
                $dataProduct['photo'] = $request->file('photo')->store('product');
            }

            Product::create($dataProduct);
        }

        return responseSuccess(false);
    }

    public function edit(Product $product)
    {
        $products = $product->where('id', $product->id)->with(['outlet'])->get()[0];
        $outlet = Outlets::find($products->outlet_id);
        $dataOutlet = ['id' => $outlet->id, 'name' => $outlet->name];
        return view('layouts.product.product-modal', [
            'data' => $product,
            'action' => route('library/product/update', $product->id),
            "categorys" => Category::all(),
            'outlets' => json_encode([$dataOutlet])
        ]);
    }

    public function update(ProductStoreRequest $request, Product $product)
    {
        $product->fill($request->validated());
        $product->harga_jual = getAmount($request->harga_jual);
        $product->harga_modal = getAmount($request->harga_modal);
        if (!empty($request->file('photo'))) {
            $filePath = public_path('uploads/product/' . $product->photo);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }

            $product->photo = $request->file('photo')->store('product');
        }

        $product->save();

        return responseSuccess(true);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return responseSuccessDelete();
    }
}

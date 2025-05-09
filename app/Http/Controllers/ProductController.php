<?php

namespace App\Http\Controllers;

use App\DataTables\ProductDataTable;
use App\Http\Requests\ProductStoreRequest;
use App\Models\Category;
use App\Models\Outlets;
use App\Models\Product;
use App\Models\VariantProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ProductController extends Controller
{
    public function index(ProductDataTable $productDataTable)
    {
        return $productDataTable->render("layouts.product.index", [
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
        ]);
    }

    public function create()
    {
        return view("layouts.product.product-modal", [
            "data" => new Product(),
            "update" => false,
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
                // 'harga_jual' => getAmount($validatedData['harga_jual']),
                'harga_modal' => getAmount($validatedData['harga_modal']),
                // 'stock' => $validatedData['stock'],
                'outlet_id' => $outlet,
                'status' => $validatedData['status'],
                'description' => $validatedData['description']
            ];

            if ($request->hasFile('photo')) {
                $dataProduct['photo'] = $request->file('photo')->store('product');
            }

            $product = Product::create($dataProduct);

            // Buat data Modifiers
            $dataVariant = [];
            for ($x = 0; $x < count($validatedData['harga_jual']); $x++) {
                $dataVariant[] = [
                    'name' => count($validatedData['harga_jual']) > 1 ? $validatedData['nama_varian'][$x] : $validatedData['name'],
                    'harga' => getAmount($validatedData['harga_jual'][$x]),
                    'stok' => $validatedData['stock'][$x],
                    'product_id' => $product->id, // Gunakan ID langsung dari instance ModifierGroup
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            // if(count($validatedData['harga_jual']) > 1){
            //     for ($x = 0; $x < count($validatedData['harga_jual']); $x++) {
            //         $dataVariant[] = [
            //             'name' => $validatedData['nama_varian'][$x],
            //             'harga' => getAmount($validatedData['harga_jual'][$x]),
            //             'stok' => $validatedData['stock'][$x],
            //             'product_id' => $product->id, // Gunakan ID langsung dari instance ModifierGroup
            //             'created_at' => now(),
            //             'updated_at' => now()
            //         ];
            //     }
            // }else{
            //     $dataVariant[] = [
            //         'name' => $validatedData['name'],
            //         'harga' => getAmount($validatedData['harga_jual'][0]),
            //         'stok' => $validatedData['stock'][0],
            //         'product_id' => $product->id, // Gunakan ID langsung dari instance ModifierGroup
            //         'created_at' => now(),
            //         'updated_at' => now()
            //     ];
            // }

            // Simpan semua Modifiers secara bulk
            VariantProduct::insert($dataVariant); // Bulk insert lebih efisien
        }

        return responseSuccess(false);
    }

    public function edit(Product $product)
    {
        $products = $product->where('id', $product->id)->with(['outlet', 'variants'])->get()[0];
        $outlet = Outlets::find($products->outlet_id);
        $dataOutlet = ['id' => $outlet->id, 'name' => $outlet->name];
        return view('layouts.product.product-modal', [
            'data' => $product,
            'update' => true,
            'action' => route('library/product/update', $product->id),
            "categorys" => Category::all(),
            'outlets' => json_encode([$dataOutlet])
        ]);
    }

    public function update(ProductStoreRequest $request, Product $product)
    {
        // Muat relasi `variants` hanya untuk produk ini
        $product->load('variants');

        $product->fill($request->validated());
        // $product->harga_jual = getAmount($request->harga_jual);
        $product->harga_modal = getAmount($request->harga_modal);
        if (!empty($request->file('photo'))) {
            $filePath = public_path('product/' . $product->photo);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }

            $product->photo = $request->file('photo')->store('product');
        }

        $product->save();

        $listNameVariant = $request['nama_varian'];
        $listHargaJualVariant = $request['harga_jual'];
        $listStockVariant = $request['stock'];
        $listIdVariant = $request['id_variant'];

        $idVariantExist = array_column($product->variants->toArray(), 'id');

        $variantToDelete = array_diff($idVariantExist, $listIdVariant);

        foreach ($variantToDelete as $deleteItem) {
            VariantProduct::find($deleteItem)->delete();
        }

        foreach ($listHargaJualVariant as $key => $value) {
            if (isset($listIdVariant[$key])) {
                $varianItem = VariantProduct::find($listIdVariant[$key]);
                $dataVariant = [
                    'name' => $listNameVariant[$key] ?? $request['name'],
                    'harga' => getAmount($value),
                    'stok' => $listStockVariant[$key],
                ];

                $varianItem->update($dataVariant);
            } else {
                $dataVariant = [
                    'name' => $listNameVariant[$key],
                    'harga' => getAmount($value),
                    'stok' => $listStockVariant[$key],
                    'product_id' => $product->id, // Gunakan ID langsung dari instance ModifierGroup
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                VariantProduct::create($dataVariant);
            }
        }

        return responseSuccess(true);
    }

    public function destroy(Product $product)
    {
        $product->variants()->delete();
        $product->delete();

        return responseSuccessDelete();
    }

    public function findVariantByProductId($id)
    {
        // Ambil variant berdasarkan product_id
        $variants = VariantProduct::where('product_id', $id)->get();

        // Kembalikan data dalam format JSON
        return response()->json($variants);
    }

    public function getProductByOutlet(Request $request)
    {
        $idOutlet = $request->input('idOutlet'); // Ambil parameter 'idOutlet' dari request
        if (count($idOutlet) > 1) {
            $products = Product::whereIn('outlet_id', $idOutlet)
                ->where('status', true)
                ->select('name', \DB::raw('COUNT(name) as name_count'))
                ->groupBy('name')
                ->having('name_count', '>', 1) // Hanya ambil nama yang muncul lebih dari 1 kali
                ->get();
        } else {
            $products = Product::where('outlet_id', $idOutlet[0])->where('status', true)->get();
        }

        return response()->json($products);
    }

    public function findVariantByProductName($name, Request $request)
    {
        // Ambil variant berdasarkan product_id
        $idOutlet = $request->input('idOutlet'); // Ambil parameter 'idOutlet' dari request
        $products = Product::with(['variants'])->whereIn('outlet_id', $idOutlet)->where('name', $name)->get();
        // Looping untuk mendapatkan varian dari masing-masing product
        $variants = $products->flatMap(function ($product) {
            return $product->variants;
        });

        // Kembalikan data dalam format JSON
        return response()->json($variants);
    }

    public function getCategoryByOutlet(Request $request)
    {
        $idOutlet = $request->input('idOutlet');
        if (count($idOutlet) > 1) {
            $category = Product::with(['category'])
                ->whereIn('outlet_id', $idOutlet)
                ->select('category_id', \DB::raw('COUNT(*) as category_count')) // Hanya ambil category_id dan hitung
                ->groupBy('category_id') // Kelompokkan berdasarkan category_id
                ->having('category_count', '>', 1) // Hanya ambil kategori dengan lebih dari 1 produk
                ->get();
        } else {
            $categoryIds = Product::where('outlet_id', $idOutlet[0])
                ->pluck('category_id') // Ambil semua category_id
                ->unique(); // Ambil yang unik

            // Ambil data kategori berdasarkan category_id yang unik
            $category = Category::whereIn('id', $categoryIds)->get();
        }

        return response()->json($category);
    }
}

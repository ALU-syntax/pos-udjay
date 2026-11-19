<?php

namespace App\Http\Controllers\B2b;

use App\Http\Controllers\Controller;
use App\Models\B2b\Customer;
use App\Models\VariantProduct;
use Illuminate\Http\Request;

/** Sumber data pemilih: tabel pilih produk di form kontrak, filter customer di daftar Pesanan/Invoice. */
class CatalogSelectorController extends Controller
{
    public function __construct()
    {
        $this->abilities = [];
    }

    /** Semua varian satu outlet untuk tabel pilih harga kontrak; pencarian & filter dilakukan di browser. */
    public function variants(Request $request)
    {
        abort_unless(
            $request->user()->can('create b2b/contracts') || $request->user()->can('update b2b/contracts'),
            403
        );
        $validated = $request->validate(['outlet_id' => ['required', 'integer', 'exists:outlets,id']]);

        $data = VariantProduct::query()
            ->select('variant_products.*')
            ->join('products', 'products.id', '=', 'variant_products.product_id')
            ->whereNull('products.deleted_at')
            ->where('products.outlet_id', $validated['outlet_id'])
            ->with('product.category:id,name')
            ->orderBy('products.name')
            ->orderBy('variant_products.name')
            ->limit(3000)
            ->get()
            ->map(fn (VariantProduct $variant) => [
                'id' => $variant->id,
                'product' => $variant->product->name,
                'variant' => $variant->name === $variant->product->name ? null : $variant->name,
                'category' => $variant->product->category?->name,
                'price' => (int) $variant->harga,
                'active' => (bool) $variant->product->status,
            ]);

        return responseSuccess(false, false, $data->values()->all());
    }

    /** Pencarian customer untuk filter daftar (select2). Termasuk customer nonaktif karena pesanan lamanya tetap perlu dicari. */
    public function customers(Request $request)
    {
        abort_unless(
            $request->user()->can('read b2b/orders') || $request->user()->can('read b2b/invoices'),
            403
        );
        $q = addcslashes(trim((string) $request->query('q')), '%_\\');

        $data = Customer::query()
            ->when($q !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$q}%")
                ->orWhere('code', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'code', 'name', 'is_active'])
            ->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'text' => $customer->name.' · '.$customer->code.($customer->is_active ? '' : ' (nonaktif)'),
                'name' => $customer->name,
            ]);

        return responseSuccess(false, false, $data->all());
    }

    public static function variantLabel(VariantProduct $variant): string
    {
        $productName = $variant->product?->name ?? '';

        return $variant->name === $productName || $productName === ''
            ? $variant->name
            : $productName.' - '.$variant->name;
    }
}

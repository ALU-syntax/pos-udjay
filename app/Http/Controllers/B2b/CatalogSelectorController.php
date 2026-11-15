<?php

namespace App\Http\Controllers\B2b;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\VariantProduct;
use Illuminate\Http\Request;

/** Sumber pilihan (select2) untuk aturan diskon kontrak. Format: {data: [{id, text}]} */
class CatalogSelectorController extends Controller
{
    public function __construct()
    {
        $this->abilities = [];
    }

    public function categories(Request $request)
    {
        $q = $this->authorizeAndQuery($request);

        $data = Category::query()
            ->when($q, fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name'])
            ->map(fn ($category) => ['id' => $category->id, 'text' => $category->name]);

        return responseSuccess(false, false, $data->all());
    }

    public function products(Request $request)
    {
        $q = $this->authorizeAndQuery($request);
        $outletId = $request->integer('outlet_id');

        $data = Product::query()
            ->with('outlet:id,name')
            ->when($outletId, fn ($query) => $query->where('outlet_id', $outletId))
            ->when($q, fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'outlet_id'])
            ->map(fn ($product) => [
                'id' => $product->id,
                'text' => $product->name.($outletId ? '' : ' ('.($product->outlet?->name ?? 'tanpa outlet').')'),
            ]);

        return responseSuccess(false, false, $data->all());
    }

    public function variants(Request $request)
    {
        $q = $this->authorizeAndQuery($request);
        $outletId = $request->integer('outlet_id');

        $data = VariantProduct::query()
            ->select('variant_products.*')
            ->join('products', 'products.id', '=', 'variant_products.product_id')
            ->whereNull('products.deleted_at')
            ->with('product.outlet:id,name')
            ->when($outletId, fn ($query) => $query->where('products.outlet_id', $outletId))
            ->when($q, fn ($query) => $query->where(fn ($inner) => $inner
                ->where('products.name', 'like', "%{$q}%")
                ->orWhere('variant_products.name', 'like', "%{$q}%")))
            ->orderBy('products.name')
            ->orderBy('variant_products.name')
            ->limit(50)
            ->get()
            ->map(fn ($variant) => [
                'id' => $variant->id,
                'text' => self::variantLabel($variant).($outletId ? '' : ' ('.($variant->product->outlet?->name ?? 'tanpa outlet').')')
                    .' · Rp '.number_format($variant->harga, 0, ',', '.'),
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

    private function authorizeAndQuery(Request $request): ?string
    {
        abort_unless(
            $request->user()->can('create b2b/contracts') || $request->user()->can('update b2b/contracts'),
            403
        );

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'outlet_id' => ['nullable', 'integer', 'exists:outlets,id'],
        ]);

        return filled($validated['q'] ?? null) ? addcslashes($validated['q'], '%_\\') : null;
    }
}

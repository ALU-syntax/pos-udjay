<?php

namespace App\Http\Controllers\OrderTable;

use App\Http\Controllers\Controller;
use App\Services\OrderTable\CatalogSelectorService;
use Illuminate\Http\Request;

class CatalogSelectorController extends Controller
{
    public function __construct(private readonly CatalogSelectorService $catalogSelector)
    {
        $this->abilities = [];
    }

    public function categories(Request $request)
    {
        return $this->respond($request, 'categories');
    }

    public function products(Request $request)
    {
        return $this->respond($request, 'products');
    }

    public function vouchers(Request $request)
    {
        return $this->respond($request, 'vouchers');
    }

    public function promos(Request $request)
    {
        return $this->respond($request, 'promos');
    }

    public function internal(Request $request)
    {
        return $this->respond($request, 'internal');
    }

    private function respond(Request $request, string $type)
    {
        $this->authorizeSelector($request, $type);
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'outlet_id' => ['nullable', 'integer', 'exists:outlets,id'],
        ]);
        $outletId = array_key_exists('outlet_id', $validated) && $validated['outlet_id'] !== null
            ? (int) $validated['outlet_id']
            : null;

        abort_unless($this->catalogSelector->isAuthorizedOutlet($request->user(), $outletId), 403);

        $data = $type === 'internal'
            ? $this->catalogSelector->internal($validated['q'] ?? null)
            : $this->catalogSelector->{$type}($request->user(), $outletId, $validated['q'] ?? null);

        return responseSuccess(false, false, $data->values()->all());
    }

    private function authorizeSelector(Request $request, string $type): void
    {
        $voucherAbilities = [
            'create order-table/vouchers',
            'update order-table/vouchers',
        ];
        $bannerAbilities = [
            'create order-table/banners',
            'update order-table/banners',
        ];
        $abilities = match ($type) {
            'categories', 'products', 'vouchers' => [...$voucherAbilities, ...$bannerAbilities],
            'promos', 'internal' => $bannerAbilities,
        };

        abort_unless(collect($abilities)->contains(fn (string $ability) => $request->user()->can($ability)), 403);
    }
}

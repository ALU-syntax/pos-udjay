<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\Discount;
use App\Models\SalesType;
use App\Models\PilihanGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /**
     * Ambil semua kategori beserta produk aktif dan variannya untuk outlet user.
     *
     * - Outlet diambil otomatis dari token user yang login
     * - Kategori tanpa produk aktif tidak disertakan
     * - Produk diurutkan berdasarkan nama ascending
     * - Setiap produk menyertakan daftar varian dengan harga dan stok
     *
     * GET /api/v1/catalog/categories
     */
    public function categories(Request $request): JsonResponse
    {
        $outletIds = $request->user()->outletIds();

        if (empty($outletIds)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak memiliki outlet yang terdaftar.',
            ], 422);
        }

        $outletId = $outletIds[0];

        $categories = Category::with(['products' => function ($query) use ($outletId) {
            $query->where('outlet_id', $outletId)
                ->where('status', 1)
                ->orderBy('name', 'asc')
                ->with(['variants' => function ($q) {
                    $q->select('id', 'product_id', 'name', 'harga', 'stok')
                        ->orderBy('name', 'asc');
                }])
                ->select('id', 'name', 'category_id', 'photo', 'description', 'exclude_tax', 'outlet_id');
        }])
        ->orderBy('name', 'asc')
        ->get(['id', 'name'])
        // Saring kategori yang tidak punya produk aktif
        ->filter(fn($category) => $category->products->isNotEmpty())
        ->values();

        return response()->json([
            'status' => 'success',
            'data'   => $categories,
        ]);
    }

    /**
     * Ambil semua modifier group beserta item modifier untuk outlet user.
     *
     * - Outlet diambil otomatis dari token user yang login
     * - Setiap modifier group menyertakan daftar modifier dengan harga dan stok
     * - product_id adalah JSON array berisi ID produk yang terkait dengan group ini
     * - is_required menentukan apakah kasir wajib memilih modifier dari group ini
     *
     * GET /api/v1/catalog/modifiers
     */
    public function modifiers(Request $request): JsonResponse
    {
        $outletIds = $request->user()->outletIds();

        if (empty($outletIds)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak memiliki outlet yang terdaftar.',
            ], 422);
        }

        $outletId = $outletIds[0];

        $modifierGroups = ModifierGroup::with(['modifier' => function ($query) {
            $query->select('id', 'modifiers_group_id', 'name', 'harga', 'stok')
                ->orderBy('name', 'asc');
        }])
        ->where('outlet_id', (string) $outletId)
        ->orderBy('name', 'asc')
        ->get(['id', 'name', 'product_id', 'outlet_id', 'is_required']);

        return response()->json([
            'status' => 'success',
            'data'   => $modifierGroups,
        ]);
    }

    /**
     * Ambil semua diskon untuk outlet user.
     *
     * - Outlet diambil otomatis dari token user yang login
     * - Diurutkan berdasarkan nama ascending
     *
     * GET /api/v1/catalog/discounts
     */
    public function discounts(Request $request): JsonResponse
    {
        $outletIds = $request->user()->outletIds();

        if (empty($outletIds)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak memiliki outlet yang terdaftar.',
            ], 422);
        }

        $outletId = $outletIds[0];

        $discounts = Discount::where('outlet_id', $outletId)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'type_input', 'satuan_discount_custom', 'amount', 'satuan', 'outlet_id']);

        return response()->json([
            'status' => 'success',
            'data'   => $discounts,
        ]);
    }

    /**
     * Ambil semua tipe penjualan aktif untuk outlet user.
     *
     * - Outlet diambil otomatis dari token user yang login
     * - Hanya mengembalikan sales type dengan status = true
     *
     * GET /api/v1/catalog/sales-types
     */
    public function salesTypes(Request $request): JsonResponse
    {
        $outletIds = $request->user()->outletIds();

        if (empty($outletIds)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak memiliki outlet yang terdaftar.',
            ], 422);
        }

        $outletId = $outletIds[0];

        $salesTypes = SalesType::where('outlet_id', $outletId)
            ->where('status', true)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'outlet_id', 'status']);

        return response()->json([
            'status' => 'success',
            'data'   => $salesTypes,
        ]);
    }

    /**
     * Ambil semua pilihan group beserta item pilihan untuk outlet user.
     *
     * - Outlet diambil otomatis dari token user yang login
     * - Setiap pilihan group menyertakan daftar pilihan dengan harga dan stok
     * - product_id adalah JSON array berisi ID produk yang terkait dengan group ini
     *
     * GET /api/v1/catalog/pilihans
     */
    public function pilihans(Request $request): JsonResponse
    {
        $outletIds = $request->user()->outletIds();

        if (empty($outletIds)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak memiliki outlet yang terdaftar.',
            ], 422);
        }

        $outletId = $outletIds[0];

        $pilihanGroups = PilihanGroup::with(['pilihans' => function ($query) {
            $query->select('id', 'pilihan_group_id', 'name', 'harga', 'stok')
                ->orderBy('name', 'asc');
        }])
        ->where('outlet_id', (string) $outletId)
        ->orderBy('name', 'asc')
        ->get(['id', 'name', 'product_id', 'outlet_id']);

        return response()->json([
            'status' => 'success',
            'data'   => $pilihanGroups,
        ]);
    }
}

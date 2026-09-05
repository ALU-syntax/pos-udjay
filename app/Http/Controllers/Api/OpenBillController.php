<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OpenBill;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpenBillController extends Controller
{
    /**
     * Ambil daftar bill yang masih terbuka untuk outlet user.
     *
     * - Outlet diambil otomatis dari token user yang login
     * - Hanya bill yang belum dibayar (deleted_at null) dan tidak dihapus permanen
     * - Mendukung pencarian berdasarkan nama bill via query param ?q=
     * - Setiap bill menyertakan jumlah item dan total (sum result_total)
     * - Diurutkan dari yang terbaru
     *
     * GET /api/v1/open-bills?q=
     */
    public function index(Request $request): JsonResponse
    {
        $outletIds = $request->user()->outletIds();

        if (empty($outletIds)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak memiliki outlet yang terdaftar.',
            ], 422);
        }

        $outletId = $outletIds[0];
        $search   = trim((string) $request->query('q', ''));

        $openBills = OpenBill::with(['customer', 'user'])
            ->where('outlet_id', $outletId)
            ->whereNull('deleted_at')
            ->whereNull('delete_permanen')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->withCount('item')
            ->withSum('item', 'result_total')
            ->latest('created_at')
            ->get()
            ->map(fn ($bill) => [
                'id'               => $bill->id,
                'name'             => $bill->name,
                'queue_order'      => $bill->queue_order,
                'customer_id'      => $bill->customer?->id,
                'customer_name'    => $bill->customer?->name,
                'user'             => [
                    'id'   => $bill->user?->id,
                    'name' => $bill->user?->name,
                ],
                'item_count'       => $bill->item_count,
                'total'            => (int) $bill->item_sum_result_total,
                'created_at'       => $bill->created_at,
                'created_at_human' => Carbon::parse($bill->created_at)->diffForHumans(),
            ]);

        return response()->json([
            'status' => 'success',
            'data'   => $openBills,
        ]);
    }

    /**
     * Ambil detail satu open bill beserta item-nya untuk outlet user.
     *
     * - Bill harus still open (belum dibayar) dan milik outlet user
     * - Field JSON (diskon, modifier, pilihan, promo) dikembalikan sebagai array
     * - Format item menyesuaikan struktur yang dikirim Android saat bayar
     *
     * GET /api/v1/open-bills/{id}
     */
    public function show(Request $request, $id): JsonResponse
    {
        $outletIds = $request->user()->outletIds();

        if (empty($outletIds)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak memiliki outlet yang terdaftar.',
            ], 422);
        }

        $outletId = $outletIds[0];

        $openBill = OpenBill::with(['customer', 'user', 'item'])
            ->where('id', $id)
            ->where('outlet_id', $outletId)
            ->whereNull('deleted_at')
            ->whereNull('delete_permanen')
            ->first();

        if (!$openBill) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Open bill tidak ditemukan.',
            ], 404);
        }

        $items = $openBill->item->map(fn ($item) => [
            'id'           => $item->id,
            'open_bill_id' => $item->open_bill_id,
            'tmp_id'       => $item->tmp_id,
            'product_id'  => $item->product_id,
            'variant_id'  => $item->variant_id,
            'nama_product' => $item->nama_product,
            'nama_variant' => $item->nama_variant,
            'harga'       => (int) $item->harga,
            'quantity'    => (int) $item->quantity,
            'qty_terbayar' => (int) $item->qty_terbayar,
            'result_total' => (int) $item->result_total,
            'catatan'     => $item->catatan,
            'exclude_tax' => (bool) $item->exclude_tax,
            'sales_type'  => $item->sales_type === 'null' || $item->sales_type === null ? null : $item->sales_type,
            'diskon'      => json_decode($item->diskon, true),
            'modifier'    => json_decode($item->modifier, true),
            'pilihan'     => json_decode($item->pilihan, true),
            'promo'       => json_decode($item->promo, true),
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'               => $openBill->id,
                'name'             => $openBill->name,
                'queue_order'      => $openBill->queue_order,
                'customer'         => $openBill->customer
                    ? [
                        'id'   => $openBill->customer->id,
                        'name' => $openBill->customer->name,
                    ]
                    : null,
                'user'             => [
                    'id'   => $openBill->user?->id,
                    'name' => $openBill->user?->name,
                ],
                'total'            => (int) $openBill->item->sum('result_total'),
                'created_at'       => $openBill->created_at,
                'created_at_human' => Carbon::parse($openBill->created_at)->diffForHumans(),
                'items'            => $items,
            ],
        ]);
    }
}
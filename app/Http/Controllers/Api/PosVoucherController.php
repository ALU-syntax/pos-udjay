<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderTable\PosVoucherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Voucher Order Table untuk transaksi POS reguler di aplikasi kasir. */
class PosVoucherController extends Controller
{
    public function __construct(private PosVoucherService $vouchers)
    {
        $this->abilities = [];
    }

    /**
     * POST /api/v1/vouchers/quote
     *
     * Hitung potongan voucher untuk isi keranjang kasir. Tidak menyimpan apa pun: pemakaian
     * voucher dicatat saat transaksinya masuk lewat POST /transactions/pay.
     */
    public function quote(Request $request): JsonResponse
    {
        $outletIds = $request->user()->outletIds();
        if (empty($outletIds)) {
            return response()->json([
                'status' => 'error',
                'message' => 'User tidak memiliki outlet yang terdaftar.',
            ], 422);
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'lines' => ['required', 'array', 'min:1', 'max:500'],
            'lines.*.product_id' => ['nullable', 'integer'],
            // Nilai baris (harga x qty) setelah diskon per item, sebelum pajak.
            'lines.*.amount' => ['required', 'integer', 'min:0', 'max:1000000000'],
        ]);

        // Item custom / sentinel `-1` dari aplikasi tidak punya produk katalog.
        $lines = array_map(fn (array $line) => [
            'productId' => isset($line['product_id']) && (int) $line['product_id'] > 0 ? (int) $line['product_id'] : null,
            'amount' => (int) $line['amount'],
        ], array_values($data['lines']));

        return response()->json([
            'status' => 'success',
            'data' => $this->vouchers->quote((int) $outletIds[0], strtoupper(trim($data['code'])), $lines),
        ]);
    }
}

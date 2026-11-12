<?php

namespace App\Services\OrderTable;

use App\Models\OrderTable\Voucher;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Voucher Order Table yang dipakai di transaksi POS reguler (kasir).
 *
 * Aturan voucher tetap dihitung Node (`quote`); pemakaiannya dicatat di sini saat transaksi POS
 * tersimpan, karena transaksi kasir offline-first dan bisa baru tersinkron belakangan.
 */
class PosVoucherService
{
    /** Awalan id diskon voucher di `diskon_all_item`; sama dengan pembayaran order meja di kasir. */
    public const DISCOUNT_PREFIX = 'ot-voucher:';

    public function __construct(private OrderTableNodeClient $node) {}

    /**
     * @param  array<int, array{product_id: ?int, amount: int}>  $lines  baris keranjang setelah diskon per item
     */
    public function quote(int $outletId, string $code, array $lines): array
    {
        $quote = $this->node->quotePosVoucher($outletId, $code, $lines);

        return [
            'voucher' => $quote['voucher'],
            'subtotal' => $quote['subtotal'],
            'eligible_subtotal' => $quote['eligibleSubtotal'],
            'discount' => $quote['discount'],
            // Dikirim balik apa adanya oleh kasir sebagai entri `diskon_all_item` transaksi.
            'discount_id' => self::DISCOUNT_PREFIX.$quote['voucher']['id'],
            'discount_name' => 'Voucher '.$quote['voucher']['code'],
        ];
    }

    /**
     * Catat pemakaian voucher dari entri `diskon_all_item` transaksi POS yang baru dibuat.
     *
     * Transaksinya sudah dibayar pelanggan, jadi di titik ini voucher tidak divalidasi ulang dan
     * kegagalan mencatat tidak boleh menggagalkan sinkron transaksi kasir.
     *
     * @param  iterable<mixed>  $diskonAllItems
     */
    public function recordRedemption(Transaction $transaction, iterable $diskonAllItems, ?User $cashier): void
    {
        try {
            foreach ($diskonAllItems as $entry) {
                $entry = (array) $entry;
                $voucherId = $this->voucherId($entry['id'] ?? null);
                if ($voucherId === null) {
                    continue;
                }
                $this->record($transaction, $voucherId, (int) round((float) ($entry['value'] ?? 0)), $cashier);

                return; // satu voucher per transaksi
            }
        } catch (\Throwable $e) {
            Log::error('Pemakaian voucher POS gagal dicatat', [
                'transaction_id' => $transaction->id, 'reference_id' => $transaction->reference_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function voucherId(mixed $discountId): ?int
    {
        return is_string($discountId) && preg_match('/^'.preg_quote(self::DISCOUNT_PREFIX, '/').'(\d+)$/', $discountId, $match) === 1
            ? (int) $match[1]
            : null;
    }

    private function record(Transaction $transaction, int $voucherId, int $discount, ?User $cashier): void
    {
        $outletId = (int) $transaction->outlet_id;
        $voucher = Voucher::query()->whereKey($voucherId)
            ->where(fn ($query) => $query->whereNull('outlet_id')->orWhere('outlet_id', $outletId))
            ->first();
        if (! $voucher) {
            Log::warning('Voucher POS tidak dikenal, pemakaian tidak dicatat', [
                'transaction_id' => $transaction->id, 'voucher_id' => $voucherId, 'outlet_id' => $outletId,
            ]);

            return;
        }

        // UNIQUE (outlet_id, pos_reference): transaksi yang sama tidak menambah kuota dua kali.
        $inserted = DB::table('ot_voucher_redemptions')->insertOrIgnore([
            'voucher_id' => $voucher->id,
            'source' => 'pos',
            'outlet_id' => $outletId,
            'pos_reference' => mb_substr((string) ($transaction->reference_id ?: 'trx-'.$transaction->id), 0, 100),
            'discount_amount' => max(0, $discount),
            'user_agent' => mb_substr('kasir:'.($cashier?->name ?? $transaction->user_id), 0, 255),
            'created_at' => $transaction->created_at ?? now(),
        ]);
        if ($inserted) {
            Voucher::query()->whereKey($voucher->id)->increment('quota_used');
        }
    }
}

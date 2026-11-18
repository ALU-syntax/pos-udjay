<?php

namespace App\Services\B2b;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Penomoran dokumen B2B. Baris sequence dikunci (lockForUpdate) sampai transaksi pemanggil selesai,
 * sehingga dua pesanan yang disimpan bersamaan tidak pernah mendapat nomor yang sama.
 */
class SequenceService
{
    public function customerCode(): string
    {
        return 'CUS-B2B-'.str_pad((string) $this->next('customer', 'all'), 4, '0', STR_PAD_LEFT);
    }

    public function contractNumber(?Carbon $date = null): string
    {
        $date ??= now();

        return 'KTR/B2B/'.$date->format('Y').'/'.str_pad((string) $this->next('contract', $date->format('Y')), 4, '0', STR_PAD_LEFT);
    }

    public function orderNumber(?Carbon $date = null): string
    {
        $date ??= now();

        return 'SO/B2B/'.$date->format('Y/m').'/'.str_pad((string) $this->next('order', $date->format('Ym')), 4, '0', STR_PAD_LEFT);
    }

    public function invoiceNumber(?Carbon $date = null): string
    {
        $date ??= now();

        return 'INV/B2B/'.$date->format('Y/m').'/'.str_pad((string) $this->next('invoice', $date->format('Ym')), 4, '0', STR_PAD_LEFT);
    }

    public function paymentNumber(?Carbon $date = null): string
    {
        $date ??= now();

        return 'PAY/B2B/'.$date->format('Y/m').'/'.str_pad((string) $this->next('payment', $date->format('Ym')), 4, '0', STR_PAD_LEFT);
    }

    public function next(string $type, string $period): int
    {
        return DB::transaction(function () use ($type, $period) {
            DB::table('b2b_sequences')->insertOrIgnore([
                'type' => $type,
                'period' => $period,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DB::table('b2b_sequences')
                ->where('type', $type)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();

            $number = (int) $sequence->last_number + 1;

            DB::table('b2b_sequences')->where('id', $sequence->id)->update([
                'last_number' => $number,
                'updated_at' => now(),
            ]);

            return $number;
        });
    }
}

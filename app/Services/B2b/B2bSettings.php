<?php

namespace App\Services\B2b;

use App\Models\Config;
use Illuminate\Support\Carbon;

/**
 * Pengaturan B2B dari tabel `configs`, dengan nilai cadangan di kode
 * supaya aplikasi tetap jalan walau baris config belum di-insert.
 */
class B2bSettings
{
    public const DEFAULT_PAYMENT_TERM_KEY = 'b2b.default_payment_term_days';

    /** Dipakai kalau config belum ada atau isinya tidak valid. */
    public const FALLBACK_PAYMENT_TERM_DAYS = 7;

    /** Batas jatuh tempo terjauh dari hari ini, untuk mencegah salah ketik tahun. */
    public const MAX_DUE_DAYS = 180;

    /** Saran termin (hari) untuk mengisi jatuh tempo di kasir. Kasir tetap bisa mengganti tanggalnya. */
    public function defaultPaymentTermDays(): int
    {
        $value = Config::getValue(self::DEFAULT_PAYMENT_TERM_KEY);

        return is_int($value) && $value >= 0 && $value <= self::MAX_DUE_DAYS
            ? $value
            : self::FALLBACK_PAYMENT_TERM_DAYS;
    }

    public function suggestedDueDate(?Carbon $from = null): Carbon
    {
        return ($from ?? today())->copy()->startOfDay()->addDays($this->defaultPaymentTermDays());
    }

    public function maxDueDate(?Carbon $from = null): Carbon
    {
        return ($from ?? today())->copy()->startOfDay()->addDays(self::MAX_DUE_DAYS);
    }
}

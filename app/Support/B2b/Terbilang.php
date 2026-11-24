<?php

namespace App\Support\B2b;

/** Angka ke kata bahasa Indonesia untuk baris "Terbilang" di kwitansi, mis. 2.500.000 → "Dua Juta Lima Ratus Ribu Rupiah". */
final class Terbilang
{
    private const UNITS = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

    public static function rupiah(int $amount): string
    {
        return ucwords(self::words(abs($amount)) ?: 'nol').' Rupiah';
    }

    private static function words(int $n): string
    {
        $text = match (true) {
            $n < 12 => self::UNITS[$n],
            $n < 20 => self::words($n - 10).' belas',
            $n < 100 => self::words(intdiv($n, 10)).' puluh '.self::words($n % 10),
            $n < 200 => 'seratus '.self::words($n - 100),
            $n < 1_000 => self::words(intdiv($n, 100)).' ratus '.self::words($n % 100),
            $n < 2_000 => 'seribu '.self::words($n - 1_000),
            $n < 1_000_000 => self::words(intdiv($n, 1_000)).' ribu '.self::words($n % 1_000),
            $n < 1_000_000_000 => self::words(intdiv($n, 1_000_000)).' juta '.self::words($n % 1_000_000),
            $n < 1_000_000_000_000 => self::words(intdiv($n, 1_000_000_000)).' miliar '.self::words($n % 1_000_000_000),
            default => self::words(intdiv($n, 1_000_000_000_000)).' triliun '.self::words($n % 1_000_000_000_000),
        };

        return trim(preg_replace('/\s+/', ' ', $text));
    }
}

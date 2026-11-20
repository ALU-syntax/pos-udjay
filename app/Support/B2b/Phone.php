<?php

namespace App\Support\B2b;

final class Phone
{
    /**
     * Nomor WhatsApp format internasional tanpa "+": 0812… / +62 812… / 812… → 62812… (sama dengan aturan gateway).
     * Null kalau tidak valid.
     */
    public static function toWhatsApp(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return preg_match('/^[1-9]\d{9,14}$/', $digits) ? $digits : null;
    }
}

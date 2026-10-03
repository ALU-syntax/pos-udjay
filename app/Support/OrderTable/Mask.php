<?php

namespace App\Support\OrderTable;

class Mask
{
    public static function value(?string $value, int $start = 8, int $end = 4): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (strlen($value) <= $start + $end) {
            return str_repeat('*', strlen($value));
        }

        return substr($value, 0, $start).'...'.substr($value, -$end);
    }

    public static function email(?string $value): string
    {
        if ($value === null || ! str_contains($value, '@')) {
            return '-';
        }

        [$name, $domain] = explode('@', $value, 2);

        return substr($name, 0, 2).str_repeat('*', max(strlen($name) - 2, 1)).'@'.$domain;
    }

    public static function gatewayRef(?string $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (strlen($value) <= 6) {
            return str_repeat('*', strlen($value));
        }

        return substr($value, 0, 3).'...'.substr($value, -3);
    }
}

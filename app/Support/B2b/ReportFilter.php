<?php

namespace App\Support\B2b;

use Carbon\CarbonImmutable;

/**
 * Filter Laporan B2B: periode, outlet (dibatasi akses user), dan customer.
 * Dipakai halaman laporan dan semua endpoint data bagiannya, supaya angka di setiap bagian memakai filter yang sama.
 */
final class ReportFilter
{
    /**
     * @param  array<int, int>  $outletIds  outlet yang dihitung: outlet pilihan, atau semua outlet yang boleh diakses user
     */
    private function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly array $outletIds,
        public readonly ?int $outletId,
        public readonly ?int $customerId,
    ) {}

    /**
     * Nilai yang tidak valid diganti nilai bawaan (periode = awal bulan s.d. hari ini, semua outlet, semua customer).
     * Endpoint data memvalidasi input lebih dulu; halaman cukup memakai nilai bawaan supaya URL yang rusak tetap terbuka.
     *
     * @param  array<string, mixed>  $input
     * @param  array<int, int|string>  $allowedOutletIds
     */
    public static function resolve(array $input, array $allowedOutletIds): self
    {
        $today = CarbonImmutable::today();
        $from = self::date($input['from'] ?? null) ?? $today->startOfMonth();
        $to = self::date($input['to'] ?? null) ?? $today;
        if ($to->lt($from)) {
            [$from, $to] = [$today->startOfMonth(), $today];
        }

        $allowed = array_values(array_map('intval', $allowedOutletIds));
        $outletId = is_numeric($input['outlet_id'] ?? null) ? (int) $input['outlet_id'] : null;
        if ($outletId !== null && ! in_array($outletId, $allowed, true)) {
            $outletId = null;
        }

        $customerId = is_numeric($input['customer_id'] ?? null) ? (int) $input['customer_id'] : null;

        return new self($from, $to, $outletId !== null ? [$outletId] : $allowed, $outletId, $customerId);
    }

    /** Batas waktu periode untuk kolom datetime (awal hari pertama s.d. akhir hari terakhir). */
    public function between(): array
    {
        return [$this->from->startOfDay(), $this->to->endOfDay()];
    }

    /** Filter dalam bentuk query string, untuk URL halaman dan endpoint data. */
    public function toQuery(): array
    {
        return array_filter([
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
            'outlet_id' => $this->outletId,
            'customer_id' => $this->customerId,
        ], fn ($value) => $value !== null);
    }

    private static function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);

        return $date && $date->toDateString() === $value ? $date : null;
    }
}

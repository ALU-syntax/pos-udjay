<?php

namespace App\Support\OrderTable;

/**
 * Label Indonesia + varian badge (gaya report) untuk enum Order Table.
 * Istilah status order mengikuti aplikasi pelanggan: Diterima, Disiapkan, Disajikan.
 */
class OrderLabels
{
    public const ORDER = [
        'draft' => ['Draft', 'muted'],
        'placed' => ['Dipesan', 'warning'],
        'received' => ['Diterima', 'primary ob-badge-dot'],
        'preparing' => ['Disiapkan', 'primary ob-badge-dot'],
        'served' => ['Disajikan', 'success'],
        'cancelled' => ['Dibatalkan', 'danger'],
        'expired' => ['Kedaluwarsa', 'muted'],
    ];

    public const PAYMENT = [
        'unpaid' => ['Belum dibayar', 'muted'],
        'pending' => ['Menunggu', 'warning'],
        'paid' => ['Lunas', 'success'],
        'failed' => ['Gagal', 'danger'],
        'expired' => ['Kedaluwarsa', 'muted'],
        'refunded' => ['Dikembalikan', 'primary ob-badge-dot'],
    ];

    public const PAYMENT_MODE = [
        'qris' => ['QRIS', 'primary'],
        'pay_at_cashier' => ['Bayar di Kasir', 'primary'],
    ];

    public const BRIDGE = [
        'pending' => ['Menunggu', 'warning'],
        'linked' => ['Tersambung', 'success'],
        'failed' => ['Gagal', 'danger'],
    ];

    public const ITEM = [
        'pending' => ['Menunggu', 'warning'],
        'preparing' => ['Disiapkan', 'primary ob-badge-dot'],
        'served' => ['Disajikan', 'success'],
        'cancelled' => ['Dibatalkan', 'danger'],
    ];

    public const GEOFENCE = [
        'inside' => ['Di dalam area', 'success'],
        'outside' => ['Di luar area', 'danger'],
        'unknown' => ['Tidak diketahui', 'muted'],
    ];

    public const SESSION = [
        'open' => ['Aktif', 'success'],
        'closed' => ['Ditutup', 'muted'],
    ];

    public const SESSION_CLOSE_REASON = [
        'eod' => ['Tutup hari', 'muted'],
        'kasir' => ['Ditutup kasir', 'muted'],
        'manual' => ['Manual', 'muted'],
    ];

    /** Dipakai untuk aktor log status order dan closed_by sesi. */
    public const ACTOR = [
        'system' => ['Sistem', 'muted'],
        'kasir' => ['Kasir', 'muted'],
        'customer' => ['Pelanggan', 'muted'],
    ];

    /** Nilai di luar peta tetap ditampilkan apa adanya agar status baru tidak hilang. */
    public static function label(array $map, ?string $value): string
    {
        return $map[$value][0] ?? ($value ?? '-');
    }

    public static function badge(array $map, ?string $value): string
    {
        [$label, $variant] = $map[$value] ?? [$value ?? '-', 'muted'];

        return '<span class="badge badge-'.$variant.'">'.e($label).'</span>';
    }

    /** value => label, untuk opsi filter. */
    public static function options(array $map): array
    {
        return array_map(fn (array $entry) => $entry[0], $map);
    }
}

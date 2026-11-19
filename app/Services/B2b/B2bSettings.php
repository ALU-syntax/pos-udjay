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

    public const INVOICE_NUMBER_CODE_KEY = 'b2b.invoice.number_code';

    /** Kode di nomor invoice (07/INV/BGR/11/2026) kalau config belum ada. */
    public const FALLBACK_INVOICE_NUMBER_CODE = 'BGR';

    /** Kode kota/cabang di nomor invoice. Hanya huruf/angka supaya format nomor tidak rusak. */
    public function invoiceNumberCode(): string
    {
        $value = Config::getValue(self::INVOICE_NUMBER_CODE_KEY);
        $code = is_string($value) ? strtoupper(trim($value)) : '';

        return preg_match('/^[A-Z0-9]{1,10}$/', $code) ? $code : self::FALLBACK_INVOICE_NUMBER_CODE;
    }

    /**
     * Identitas penjual di cetak invoice (satu untuk semua outlet). Key = nama config, value = [isi default, keterangan].
     * Default ini juga ditulis ke tabel configs oleh B2bMenuSeeder (firstOrCreate, jadi isi yang sudah diubah tidak tertimpa).
     */
    public const INVOICE_PROFILE_DEFAULTS = [
        'b2b.invoice.brand_name' => ['Budiman Roastery', 'Nama brand di kepala invoice'],
        'b2b.invoice.address' => ['Jl. Singosari, Jl. Dahlia Mesjid Blk. A1 Perumahan Cimanggu Permai No.1, RT.04/RW.06, Kedung Jaya, Tanah Sareal, Kota Bogor, Jawa Barat 16164', 'Alamat di kepala invoice'],
        'b2b.invoice.bank_name' => ['BCA', 'Bank tujuan transfer di invoice'],
        'b2b.invoice.bank_account_number' => ['5735570401', 'Nomor rekening tujuan transfer di invoice'],
        'b2b.invoice.bank_account_name' => ['PT Budhidharma Adil Manfaat', 'Atas nama rekening tujuan transfer di invoice'],
        'b2b.invoice.instagram' => ['@budiman.roastery', 'Instagram di kaki invoice'],
        'b2b.invoice.email' => ['budimanroastery@gmail.com', 'Email di kaki invoice'],
        // Kosong = tidak ditampilkan. Isi nomor telepon/WhatsApp bagian penagihan.
        'b2b.invoice.phone' => ['', 'Telepon/WhatsApp penagihan di kaki invoice (kosong = tidak ditampilkan)'],
    ];

    /** @return array{brand_name:string, address:string, bank_name:string, bank_account_number:string, bank_account_name:string, instagram:string, email:string, phone:string} */
    public function invoiceProfile(): array
    {
        $values = Config::query()->whereIn('name', array_keys(self::INVOICE_PROFILE_DEFAULTS))->pluck('value', 'name');
        $profile = [];
        foreach (self::INVOICE_PROFILE_DEFAULTS as $key => [$default]) {
            $value = trim((string) ($values[$key] ?? ''));
            $profile[substr($key, strlen('b2b.invoice.'))] = $value !== '' ? $value : $default;
        }

        return $profile;
    }

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

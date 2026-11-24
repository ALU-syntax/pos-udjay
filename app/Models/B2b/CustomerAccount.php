<?php

namespace App\Models\B2b;

use App\Models\AccountProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Rekening bank / e-wallet terdaftar milik customer (pengirim transfer). Satu rekening hanya milik satu customer. */
class CustomerAccount extends Model
{
    public const TYPE_BANK = 'bank';

    public const TYPE_EWALLET = 'ewallet';

    public const TYPES = [
        self::TYPE_BANK => 'Bank',
        self::TYPE_EWALLET => 'E-wallet',
    ];

    // Daftar bank / e-wallet ada di tabel master account_providers (App\Models\AccountProvider), bukan di kode.

    public const MAX_PER_CUSTOMER = 10;

    protected $table = 'b2b_customer_accounts';

    protected $guarded = ['id'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'b2b_customer_id');
    }

    /** Jenis (bank/ewallet) dari master account_providers; null kalau kode tidak dikenal. */
    public static function typeOf(?string $provider): ?string
    {
        return AccountProvider::lookup($provider)?->type;
    }

    /** Pilihan dropdown per jenis (hanya provider aktif, kecuali kode di $keep yang sudah dipakai rekening ini). */
    public static function providerOptions(array $keep = []): array
    {
        return AccountProvider::options($keep);
    }

    /**
     * Nomor disimpan angka saja. Nomor e-wallet (nomor HP) diseragamkan ke 08…, jadi 62812… dan +62 812… dianggap sama.
     */
    public static function normalizeNumber(?string $number, ?string $provider): string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);
        if (self::typeOf((string) $provider) === self::TYPE_EWALLET && str_starts_with($digits, '62')) {
            $digits = '0'.substr($digits, 2);
        }

        return $digits;
    }

    public function providerLabel(): string
    {
        return AccountProvider::lookup($this->provider)?->name ?? strtoupper($this->provider);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /** Nomor dikelompokkan per 4 digit supaya mudah dibaca/dicocokkan: "1234 5678 90". */
    public function formattedNumber(): string
    {
        return trim(chunk_split($this->account_number, 4, ' '));
    }

    /** Ringkasan untuk log & tampilan: "BCA 1234567890 a.n. PT Maju". */
    public function summary(): string
    {
        return $this->providerLabel().' '.$this->account_number.' a.n. '.$this->account_name;
    }
}

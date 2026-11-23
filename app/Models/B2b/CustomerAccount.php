<?php

namespace App\Models\B2b;

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

    /** Daftar baku bank / e-wallet: kode => [jenis, nama tampil]. Tambah di sini bila perlu, jangan ketik bebas. */
    public const PROVIDERS = [
        'bca' => [self::TYPE_BANK, 'BCA'],
        'mandiri' => [self::TYPE_BANK, 'Mandiri'],
        'bri' => [self::TYPE_BANK, 'BRI'],
        'bni' => [self::TYPE_BANK, 'BNI'],
        'bsi' => [self::TYPE_BANK, 'BSI'],
        'cimb' => [self::TYPE_BANK, 'CIMB Niaga'],
        'danamon' => [self::TYPE_BANK, 'Danamon'],
        'permata' => [self::TYPE_BANK, 'Permata'],
        'btn' => [self::TYPE_BANK, 'BTN'],
        'ocbc' => [self::TYPE_BANK, 'OCBC'],
        'panin' => [self::TYPE_BANK, 'Panin'],
        'maybank' => [self::TYPE_BANK, 'Maybank'],
        'mega' => [self::TYPE_BANK, 'Bank Mega'],
        'jenius' => [self::TYPE_BANK, 'Jenius / SMBC Indonesia'],
        'jago' => [self::TYPE_BANK, 'Bank Jago'],
        'seabank' => [self::TYPE_BANK, 'SeaBank'],
        'blu' => [self::TYPE_BANK, 'blu by BCA Digital'],
        'bjb' => [self::TYPE_BANK, 'Bank BJB'],
        'dki' => [self::TYPE_BANK, 'Bank DKI'],
        'jateng' => [self::TYPE_BANK, 'Bank Jateng'],
        'jatim' => [self::TYPE_BANK, 'Bank Jatim'],
        'gopay' => [self::TYPE_EWALLET, 'GoPay'],
        'ovo' => [self::TYPE_EWALLET, 'OVO'],
        'dana' => [self::TYPE_EWALLET, 'DANA'],
        'shopeepay' => [self::TYPE_EWALLET, 'ShopeePay'],
        'linkaja' => [self::TYPE_EWALLET, 'LinkAja'],
    ];

    public const MAX_PER_CUSTOMER = 10;

    protected $table = 'b2b_customer_accounts';

    protected $guarded = ['id'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'b2b_customer_id');
    }

    public static function typeOf(string $provider): ?string
    {
        return self::PROVIDERS[$provider][0] ?? null;
    }

    /** Pilihan dropdown dikelompokkan per jenis: ['Bank' => [kode => nama], 'E-wallet' => [...]]. */
    public static function providerOptions(): array
    {
        $groups = [];
        foreach (self::PROVIDERS as $code => [$type, $label]) {
            $groups[self::TYPES[$type]][$code] = $label;
        }

        return $groups;
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
        return self::PROVIDERS[$this->provider][1] ?? strtoupper($this->provider);
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

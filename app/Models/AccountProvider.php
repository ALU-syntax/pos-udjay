<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Master bank & e-wallet (tabel account_providers, diisi AccountProviderSeeder).
 * Dibaca juga oleh aplikasi membership (uddjaya-membership) untuk form pendaftaran customer B2B.
 */
class AccountProvider extends Model
{
    public const TYPE_BANK = 'bank';

    public const TYPE_EWALLET = 'ewallet';

    public const TYPES = [
        self::TYPE_BANK => 'Bank',
        self::TYPE_EWALLET => 'E-wallet',
    ];

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /** Semua provider (aktif & nonaktif) per kode, dimuat sekali per proses; tabelnya kecil dan jarang berubah. */
    private static ?Collection $cache = null;

    protected static function booted(): void
    {
        static::saved(fn () => self::flushCache());
        static::deleted(fn () => self::flushCache());
    }

    public static function flushCache(): void
    {
        self::$cache = null;
    }

    /** @return Collection<string, self> */
    public static function cached(): Collection
    {
        return self::$cache ??= static::query()->orderBy('sort_order')->orderBy('name')->get()->keyBy('code');
    }

    public static function lookup(?string $code): ?self
    {
        return $code === null || $code === '' ? null : self::cached()->get($code);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Pilihan dropdown dikelompokkan per jenis: ['Bank' => [kode => nama], 'E-wallet' => [...]]. Kode di $keep ikut walau nonaktif. */
    public static function options(array $keep = []): array
    {
        $groups = [];
        foreach (self::cached() as $code => $provider) {
            if ($provider->is_active || in_array($code, $keep, true)) {
                $groups[self::TYPES[$provider->type]][$code] = $provider->name.($provider->is_active ? '' : ' (nonaktif)');
            }
        }

        return $groups;
    }

    /** @return list<string> */
    public static function ewalletCodes(): array
    {
        return self::cached()->where('type', self::TYPE_EWALLET)->keys()->values()->all();
    }
}

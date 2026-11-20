<?php

namespace App\Services;

use App\Models\Wilayah\District;
use App\Models\Wilayah\Province;
use App\Models\Wilayah\Regency;
use App\Models\Wilayah\Village;
use Illuminate\Database\Eloquent\Collection;

/** Pilihan dropdown wilayah berjenjang: Provinsi → Kota/Kabupaten → Kecamatan → Kelurahan/Desa. */
class WilayahService
{
    /** Tingkat anak → [model, kolom kode induk]. */
    public const CHILD_LEVELS = [
        'regencies' => [Regency::class, 'province_code'],
        'districts' => [District::class, 'regency_code'],
        'villages' => [Village::class, 'district_code'],
    ];

    public function provinces(): Collection
    {
        return Province::query()->orderBy('name')->get(['code', 'name']);
    }

    /** Wilayah satu tingkat di bawah induknya; kelurahan/desa ikut membawa kode pos. Kosong kalau induk tidak diisi. */
    public function children(string $level, ?string $parentCode): Collection
    {
        [$model, $parentColumn] = self::CHILD_LEVELS[$level];
        if (blank($parentCode)) {
            return (new $model)->newCollection();
        }

        return $model::query()
            ->where($parentColumn, $parentCode)
            ->orderBy('name')
            ->get($level === 'villages' ? ['code', 'name', 'postal_code'] : ['code', 'name']);
    }

    /**
     * Pilihan awal keempat dropdown untuk form, mengikuti kode yang sudah tersimpan.
     *
     * @return array{provinces: Collection, regencies: Collection, districts: Collection, villages: Collection}
     */
    public function optionsFor(?string $provinceCode, ?string $regencyCode, ?string $districtCode): array
    {
        return [
            'provinces' => $this->provinces(),
            'regencies' => $this->children('regencies', $provinceCode),
            'districts' => $this->children('districts', $regencyCode),
            'villages' => $this->children('villages', $districtCode),
        ];
    }
}

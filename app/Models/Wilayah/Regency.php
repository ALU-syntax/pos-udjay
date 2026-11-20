<?php

namespace App\Models\Wilayah;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Kota/Kabupaten. */
class Regency extends Model
{
    protected $table = 'regencies';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code');
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class, 'regency_code');
    }
}

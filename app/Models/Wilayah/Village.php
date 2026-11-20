<?php

namespace App\Models\Wilayah;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Kelurahan/Desa. */
class Village extends Model
{
    protected $table = 'villages';

    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_code');
    }
}

<?php

namespace App\Models\OrderTable;

use App\Models\Outlets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutletSetting extends Model
{
    protected $table = 'ot_outlet_settings';

    protected $guarded = ['id'];

    protected $casts = [
        'outlet_id' => 'integer',
        'order_enabled' => 'boolean',
        'forced_close' => 'boolean',
        'service_fee_pct' => 'decimal:2',
        'auto_preparing_delay_seconds' => 'integer',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlets::class, 'outlet_id');
    }
}

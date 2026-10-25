<?php

namespace App\Models\OrderTable;

use App\Models\Outlets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Banner extends Model
{
    public const DISPLAY_MODES = ['image', 'text'];

    public const TONES = ['black', 'red', 'coffee', 'yellow'];

    public const ACTION_TYPES = ['none', 'url', 'product', 'category', 'voucher'];

    protected $table = 'ot_banners';

    protected $guarded = ['id'];

    protected $casts = [
        'outlet_id' => 'integer',
        'sort_order' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'status' => 'boolean',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlets::class, 'outlet_id');
    }
}

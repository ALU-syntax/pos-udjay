<?php

namespace App\Models\OrderTable;

use App\Models\Outlets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    protected $table = 'ot_vouchers';

    protected $guarded = ['id', 'quota_used'];

    protected $casts = [
        'outlet_id' => 'integer',
        'value' => 'integer',
        'min_spend' => 'integer',
        'max_discount' => 'integer',
        'quota_total' => 'integer',
        'quota_per_device' => 'integer',
        'quota_per_session' => 'integer',
        'quota_used' => 'integer',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'scope_ids' => 'array',
        'status' => 'boolean',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlets::class, 'outlet_id');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(VoucherRedemption::class, 'voucher_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'voucher_id');
    }
}

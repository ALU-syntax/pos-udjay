<?php

namespace App\Models\OrderTable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherRedemption extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ot_voucher_redemptions';

    protected $guarded = ['*'];

    protected $casts = [
        'voucher_id' => 'integer',
        'order_id' => 'integer',
        'session_id' => 'integer',
        'discount_amount' => 'integer',
        'created_at' => 'datetime',
    ];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TableSession::class, 'session_id');
    }
}

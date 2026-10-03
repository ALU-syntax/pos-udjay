<?php

namespace App\Models\OrderTable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayment extends Model
{
    protected $table = 'ot_order_payments';

    protected $guarded = ['*'];

    protected $casts = [
        'order_id' => 'integer',
        'amount' => 'integer',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'raw_callback' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}

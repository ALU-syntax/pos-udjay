<?php

namespace App\Models\OrderTable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ot_order_status_logs';

    protected $guarded = ['*'];

    protected $casts = [
        'order_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}

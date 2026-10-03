<?php

namespace App\Models\OrderTable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemModifier extends Model
{
    protected $table = 'ot_order_item_modifiers';

    protected $guarded = ['*'];

    public $timestamps = false;

    protected $casts = [
        'order_item_id' => 'integer',
        'modifier_id' => 'integer',
        'harga' => 'integer',
        'qty' => 'integer',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}

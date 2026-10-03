<?php

namespace App\Models\OrderTable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $table = 'ot_order_items';

    protected $guarded = ['*'];

    public $timestamps = false;

    protected $casts = [
        'order_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'unit_price' => 'integer',
        'qty' => 'integer',
        'modifier_total' => 'integer',
        'line_total' => 'integer',
        'exclude_tax' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(OrderItemModifier::class, 'order_item_id');
    }
}

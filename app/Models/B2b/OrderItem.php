<?php

namespace App\Models\B2b;

use App\Models\Product;
use App\Models\VariantProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $table = 'b2b_order_items';

    protected $guarded = ['id'];

    protected $casts = [
        'qty' => 'integer',
        'base_price' => 'integer',
        'discount_value' => 'float',
        'discount_per_unit' => 'integer',
        'final_price' => 'integer',
        'line_total' => 'integer',
        'stock_insufficient' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'b2b_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id')->withTrashed();
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(VariantProduct::class, 'variant_id')->withTrashed();
    }

    public function displayName(): string
    {
        return $this->variant_name === '' || $this->variant_name === $this->product_name
            ? $this->product_name
            : $this->product_name.' - '.$this->variant_name;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderCancellationItems extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'qty_cancelled' => 'decimal:5',
        'qty_base_cancelled' => 'decimal:5',
    ];

    public function cancellation()
    {
        return $this->belongsTo(PurchaseOrderCancellations::class, 'purchase_order_cancellation_id');
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrdersItems::class, 'purchase_order_item_id');
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterials::class, 'raw_material_id');
    }

    public function unit()
    {
        return $this->belongsTo(Satuan::class, 'unit_id');
    }
}

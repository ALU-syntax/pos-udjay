<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrdersItems extends Model
{
    use HasFactory;

    protected $table = 'purchase_order_items';
    protected $guarded = ['id'];

    protected $casts = [
        'qty_ordered' => 'decimal:5',
        'qty_base_ordered' => 'decimal:5',
        'qty_base_received' => 'decimal:5',
        'qty_base_rejected' => 'decimal:5',
        'qty_base_cancelled' => 'decimal:5',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrders::class, 'purchase_order_id');
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterials::class, 'raw_material_id');
    }

    public function unit()
    {
        return $this->belongsTo(Satuan::class, 'unit_id');
    }

    public function receiptItems()
    {
        return $this->hasMany(PurchaseOrderReceiptItems::class, 'purchase_order_item_id');
    }

    public function cancellationItems()
    {
        return $this->hasMany(PurchaseOrderCancellationItems::class, 'purchase_order_item_id');
    }
}

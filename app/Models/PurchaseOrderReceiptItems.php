<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderReceiptItems extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'qty_received' => 'decimal:5',
        'qty_base_received' => 'decimal:5',
        'qty_accepted' => 'decimal:5',
        'qty_base_accepted' => 'decimal:5',
        'qty_rejected' => 'decimal:5',
        'qty_base_rejected' => 'decimal:5',
        'unit_price' => 'decimal:2',
        'accepted_subtotal' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function receipt()
    {
        return $this->belongsTo(PurchaseOrderReceipts::class, 'purchase_order_receipt_id');
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

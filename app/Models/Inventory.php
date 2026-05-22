<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inventory';
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function outlet()
    {
        return $this->belongsTo(Outlets::class, 'outlet_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function type()
    {
        return $this->belongsTo(InventoryType::class, 'inventory_type_id');
    }

    public function stockBalances()
    {
        return $this->hasMany(InventoryRawMaterialStockBalance::class, 'inventory_id');
    }

    public function receivingPurchaseOrders()
    {
        return $this->hasMany(PurchaseOrders::class, 'receiving_inventory_id');
    }

    public function purchaseOrderReceipts()
    {
        return $this->hasMany(PurchaseOrderReceipts::class, 'received_inventory_id');
    }
}

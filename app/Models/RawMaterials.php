<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RawMaterials extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(RawMaterialCategories::class, 'raw_material_category_id');
    }

    public function baseUnit()
    {
        return $this->belongsTo(Satuan::class, 'base_unit_id');
    }

    public function storageType()
    {
        return $this->belongsTo(RawStorageType::class, 'storage_type_id');
    }

    public function stockBalances()
    {
        return $this->hasMany(InventoryRawMaterialStockBalance::class, 'raw_material_id');
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrdersItems::class, 'raw_material_id');
    }

    public function purchaseOrderReceiptItems()
    {
        return $this->hasMany(PurchaseOrderReceiptItems::class, 'raw_material_id');
    }

    public function purchaseOrderCancellationItems()
    {
        return $this->hasMany(PurchaseOrderCancellationItems::class, 'raw_material_id');
    }

    public function rawMaterialRequestItems()
    {
        return $this->hasMany(RawMaterialRequestItems::class, 'raw_material_id');
    }

    public function procurementPlanItems()
    {
        return $this->hasMany(ProcurementPlanItems::class, 'raw_material_id');
    }

    public function inventory()
    {
        return $this->belongsToMany(
            Inventory::class,
            'inventory_raw_material_stock_balances',
            'raw_material_id',
            'inventory_id'
        )->withPivot(['qty_available', 'qty_reserved'])->withTimestamps();
    }
}

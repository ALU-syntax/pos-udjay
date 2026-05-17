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

    public function inventoryLocations()
    {
        return $this->belongsToMany(
            InventoryLocation::class,
            'inventory_raw_material_stock_balances',
            'raw_material_id',
            'inventory_location_id'
        )->withPivot(['qty_available', 'qty_reserved'])->withTimestamps();
    }
}

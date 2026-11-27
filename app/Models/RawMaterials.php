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

    public function supplierMaterials()
    {
        return $this->hasMany(SupplierRawMaterials::class, 'raw_material_id');
    }

    public function unitConversions()
    {
        return $this->hasMany(RawMaterialUnitConversions::class, 'raw_material_id');
    }

    /**
     * Jumlah satuan dasar dalam 1 satuan $unitId (mis. 1 karung = 25.000 g).
     * Memakai konversi satuan → dasar, atau kebalikannya (dasar → satuan).
     * Null bila belum ada konversi yang valid.
     */
    public function conversionToBase(int $unitId): ?float
    {
        if ((int) $this->base_unit_id === $unitId) {
            return 1.0;
        }

        $direct = (float) $this->unitConversions()
            ->where('from_unit_id', $unitId)
            ->where('to_unit_id', $this->base_unit_id)
            ->value('multiplier');

        if ($direct > 0) {
            return $direct;
        }

        $reverse = (float) $this->unitConversions()
            ->where('from_unit_id', $this->base_unit_id)
            ->where('to_unit_id', $unitId)
            ->value('multiplier');

        return $reverse > 0 ? 1 / $reverse : null;
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

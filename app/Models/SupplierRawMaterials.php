<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierRawMaterials extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'is_preferred' => 'boolean',
        'is_active' => 'boolean',
        'minimum_order_qty' => 'decimal:4',
        'current_price' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterials::class, 'raw_material_id');
    }

    public function purchaseUnit()
    {
        return $this->belongsTo(Satuan::class, 'purchase_unit_id');
    }

    public function priceHistories()
    {
        return $this->hasMany(SupplierRawMaterialPriceHistories::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierRawMaterialPriceHistories extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function supplierRawMaterial()
    {
        return $this->belongsTo(SupplierRawMaterials::class, 'supplier_raw_material_id');
    }
}

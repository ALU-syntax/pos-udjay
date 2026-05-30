<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcurementPlanItems extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'qty_required_base' => 'decimal:5',
        'qty_available_base' => 'decimal:5',
        'qty_shortage_base' => 'decimal:5',
        'qty_to_purchase_base' => 'decimal:5',
        'estimated_unit_price' => 'decimal:2',
        'estimated_subtotal' => 'decimal:2',
    ];

    public function procurementPlan()
    {
        return $this->belongsTo(ProcurementPlans::class, 'procurement_plan_id');
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterials::class, 'raw_material_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function supplierRawMaterial()
    {
        return $this->belongsTo(SupplierRawMaterials::class, 'supplier_raw_material_id');
    }

    public function unit()
    {
        return $this->belongsTo(Satuan::class, 'unit_id');
    }

    public function sources()
    {
        return $this->hasMany(ProcurementPlanItemSources::class, 'procurement_plan_item_id');
    }
}

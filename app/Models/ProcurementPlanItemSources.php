<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcurementPlanItemSources extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'qty_base_allocated' => 'decimal:5',
    ];

    public function procurementPlanItem()
    {
        return $this->belongsTo(ProcurementPlanItems::class, 'procurement_plan_item_id');
    }

    public function rawMaterialRequestItem()
    {
        return $this->belongsTo(RawMaterialRequestItems::class, 'raw_material_request_item_id');
    }
}

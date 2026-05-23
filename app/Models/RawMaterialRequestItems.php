<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterialRequestItems extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'qty_requested' => 'decimal:5',
        'qty_base_requested' => 'decimal:5',
        'qty_base_approved' => 'decimal:5',
        'qty_base_fulfilled' => 'decimal:5',
    ];

    public function rawMaterialRequest()
    {
        return $this->belongsTo(RawMaterialRequests::class, 'raw_material_request_id');
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterials::class, 'raw_material_id');
    }

    public function unit()
    {
        return $this->belongsTo(Satuan::class, 'unit_id');
    }

    public function procurementPlanItemSources()
    {
        return $this->hasMany(ProcurementPlanItemSources::class, 'raw_material_request_item_id');
    }
}

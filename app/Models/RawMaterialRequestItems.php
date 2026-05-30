<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterialRequestItems extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'requested_qty' => 'decimal:5',
        'requested_conversion_to_base' => 'decimal:6',
        'requested_base_qty' => 'decimal:5',
        'approved_qty' => 'decimal:5',
        'approved_conversion_to_base' => 'decimal:6',
        'approved_base_qty' => 'decimal:5',
        'fulfilled_base_qty' => 'decimal:5',
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
        return $this->requestedSatuan();
    }

    public function requestedSatuan()
    {
        return $this->belongsTo(Satuan::class, 'requested_satuan_id');
    }

    public function requestedBaseSatuan()
    {
        return $this->belongsTo(Satuan::class, 'requested_base_satuan_id');
    }

    public function approvedSatuan()
    {
        return $this->belongsTo(Satuan::class, 'approved_satuan_id');
    }

    public function approvedBaseSatuan()
    {
        return $this->belongsTo(Satuan::class, 'approved_base_satuan_id');
    }

    public function fulfilledBaseSatuan()
    {
        return $this->belongsTo(Satuan::class, 'fulfilled_base_satuan_id');
    }

    public function procurementPlanItemSources()
    {
        return $this->hasMany(ProcurementPlanItemSources::class, 'raw_material_request_item_id');
    }
}

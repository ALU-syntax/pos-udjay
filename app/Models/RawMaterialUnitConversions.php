<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterialUnitConversions extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterials::class, 'raw_material_id');
    }

    public function fromUnit()
    {
        return $this->belongsTo(Satuan::class, 'from_unit_id');
    }

    public function toUnit()
    {
        return $this->belongsTo(Satuan::class, 'to_unit_id');
    }
}

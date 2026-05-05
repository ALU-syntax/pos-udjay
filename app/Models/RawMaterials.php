<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RawMaterials extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function category()
    {
        return $this->belongsTo(RawMaterialCategories::class, 'raw_material_category_id');
    }

    public function baseUnit()
    {
        return $this->belongsTo(Satuan::class, 'base_unit_id');
    }
}

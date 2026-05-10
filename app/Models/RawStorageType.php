<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RawStorageType extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function rawMaterials()
    {
        return $this->hasMany(RawMaterials::class, 'storage_type_id');
    }
}

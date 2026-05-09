<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public $procurementMode = [
        'ONLINE' => 'online',
        'OFFLINE'=> 'offline',
        'BOTH' => 'both'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function contacts(){
        return $this->hasMany(SupplierContacts::class);
    }


    public function orderChannels(){
        return $this->hasMany(SupplierOrderChannels::class);
    }

    public function operationalHours()
    {
        return $this->hasMany(SupplierOperationalHours::class);
    }

    public function primaryContact(){
        return $this->hasOne(SupplierContacts::class)->where('is_primary', true);
    }

    public function primaryOrderChannel(){
        return $this->hasOne(SupplierOrderChannels::class)->where('is_primary', true);
    }

    public function rawMaterials()
    {
        return $this->hasMany(SupplierRawMaterials::class);
    }

    public function preferredRawMaterials()
    {
        return $this->hasMany(SupplierRawMaterials::class)->where('is_preferred', true);
    }


}

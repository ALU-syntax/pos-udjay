<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inventory_types';
    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function inventory()
    {
        return $this->hasMany(Inventory::class, 'inventory_type_id');
    }
}

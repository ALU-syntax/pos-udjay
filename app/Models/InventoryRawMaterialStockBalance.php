<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryRawMaterialStockBalance extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'qty_available' => 'decimal:5',
        'qty_reserved' => 'decimal:5',
    ];

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterials::class, 'raw_material_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function modifierGroups()
    {
        return ModifierGroup::whereJsonContains('product_id', strval($this->id));
    }

    public function pilihanGroups(){
        return PilihanGroup::whereJsonContains('product_id', strval($this->id));
    }

    public function outlet(){
        return $this->belongsTo(Outlets::class, 'outlet_id', 'id');
    }

    public function variants(){
        return $this->hasMany(VariantProduct::class, 'product_id', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModifierGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function modifier(){
        return $this->hasMany(Modifiers::class, "modifiers_group_id", 'id');
    }
    
    public function outlet(){
        return $this->belongsTo(Outlets::class, 'outlet_id', 'id');
    }
}

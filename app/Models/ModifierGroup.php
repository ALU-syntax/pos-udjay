<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModifierGroup extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function modifier(){
        return $this->hasMany(Modifiers::class, "modifiers_group_id", 'id');
    }
    
    public function outlet(){
        return $this->belongsTo(Outlets::class, 'outlet_id', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Modifiers extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function modifierGroup(){
        return $this->belongsTo(ModifierGroup::class, "modifiers_group_id", 'id');
    }
}

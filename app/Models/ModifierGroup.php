<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModifierGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        // Tampil di menu Order Table (pelanggan); kasir tetap melihat semuanya.
        'show_in_order_table' => 'boolean',
    ];

    public function modifier(){
        return $this->hasMany(Modifiers::class, "modifiers_group_id", 'id');
    }
    
    public function outlet(){
        return $this->belongsTo(Outlets::class, 'outlet_id', 'id');
    }
}

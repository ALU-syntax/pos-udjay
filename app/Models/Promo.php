<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promo extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function outlet()
    {
        return $this->belongsTo(Outlets::class, 'outlet_id', 'id');
    }
}

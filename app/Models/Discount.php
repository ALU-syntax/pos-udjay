<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function outlet()
    {
        return $this->belongsTo(Outlets::class, 'outlet_id', 'id');
    }
}

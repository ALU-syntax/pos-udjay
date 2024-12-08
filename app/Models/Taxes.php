<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Taxes extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function outlets(){
        return $this->belongsTo(Outlets::class, 'outlet_id', 'id');
    }
}

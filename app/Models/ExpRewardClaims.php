<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpRewardClaims extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function customer(){
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function outlet(){
        return $this->belongsTo(Outlets::class, 'outlet_id', 'id');
    }

    public function product(){
        return $this->belongsTo(Product::class, 'product_id', 'id')->withTrashed();
    }
}

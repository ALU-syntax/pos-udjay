<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OpenBill extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function outlet(){
        return $this->belongsTo(Outlets::class, 'outlet_id', 'id');
    }

    public function item(){
        return $this->hasMany(ItemOpenBill::class, 'open_bill_id', 'id');
    }

    public function itemWithTrashed()
    {
        return $this->hasMany(ItemOpenBill::class, 'open_bill_id', 'id')->withTrashed();
    }


    public function customer(){
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }
}

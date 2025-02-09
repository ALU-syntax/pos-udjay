<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerReferral extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function customer(){
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function referral(){
        return $this->belongsTo(Customer::class, 'referral_id', 'id');
    }
}

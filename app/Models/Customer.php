<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function transactions(){
        return $this->hasMany(Transaction::class, 'customer_id', 'id');
    }

    public function community(){
        return $this->belongsTo(Community::class, 'community_id', 'id');
    }

    public function referral(){
        return $this->belongsTo(Customer::class, 'referral_id', 'id');
    }

    public function referee(){
        return $this->hasMany(Customer::class, 'referral_id', 'id');
    }

    public function levelMembership(){
        return $this->belongsTo(LevelMembership::class, 'level_memberships_id', 'id');
    }

}

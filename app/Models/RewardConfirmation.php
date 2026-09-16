<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RewardConfirmation extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function outlet(){
        return $this->belongsTo(Outlets::class, 'outlet_id', 'id');
    }

    public function customer(){
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function levelMembership(){
        return $this->belongsTo(LevelMembership::class, 'level_membership_id', 'id')->withTrashed();
    }

    public function rewardMembership(){
        return $this->belongsTo(RewardMembership::class, 'reward_memberships_id', 'id')->withTrashed();
    }
}

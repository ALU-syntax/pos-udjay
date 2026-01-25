<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RewardMembership extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function rewardProduct(){
        return $this->hasMany(RewardLevelMembershipProduct::class, 'reward_membership_id', 'id');
    }

    public function rewardConfirmation(){
        return $this->hasMany(RewardConfirmation::class, 'reward_memberships_id', 'id');
    }

    protected static function booted()
    {
        static::deleting(function ($reward) {
            $reward->rewardProduct()->delete();
        });
    }

}

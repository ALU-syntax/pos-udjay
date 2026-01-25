<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LevelMembership extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ["id"];

    public function rewards(){
        return $this->hasMany(RewardMembership::class, 'level_membership_id', 'id');
    }

    public function rewardConfirmations(){
        return $this->hasMany(RewardConfirmation::class, 'reward_membership_id', 'id');
    }
}

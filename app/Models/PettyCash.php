<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PettyCash extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function userStarted(){
        return $this->belongsTo(User::class, 'user_id_started', 'id');
    }

    public function userEnded(){
        return $this->belongsTo(User::class, 'user_id_ended', 'id');
    }

    public function outlet(){
        return $this->belongsTo(Outlets::class, 'outlet_id', 'id');
    }
}

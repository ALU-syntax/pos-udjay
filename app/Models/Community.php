<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Community extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function customers()
    {
        return $this->hasMany(Customer::class, 'community_id', 'id');
    }
}

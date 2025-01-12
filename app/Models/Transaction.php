<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function itemTransaction(){
        return $this->hasMany(TransactionItem::class, 'transaction_id', 'id');
    }

    public function payments(){
        return $this->belongsTo(Payment::class, 'tipe_pembayaran', 'id');
    }

}

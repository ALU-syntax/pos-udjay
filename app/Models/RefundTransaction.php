<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundTransaction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function itemTransaction(){
        return $this->hasMany(TransactionItem::class, 'refund_transaction_id', 'id');
    }
}

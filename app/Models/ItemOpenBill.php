<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemOpenBill extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function product(){
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function variant(){
        return $this->belongsTo(VariantProduct::class, 'variant_id', 'id');
    }

    public function itemTransactions(){
        return $this->hasMany(TransactionItem::class, 'item_open_bill_id', 'id');
    }
}

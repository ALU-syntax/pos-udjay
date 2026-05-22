<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Satuan extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrdersItems::class, 'unit_id');
    }

    public function purchaseOrderReceiptItems()
    {
        return $this->hasMany(PurchaseOrderReceiptItems::class, 'unit_id');
    }

    public function purchaseOrderCancellationItems()
    {
        return $this->hasMany(PurchaseOrderCancellationItems::class, 'unit_id');
    }
}

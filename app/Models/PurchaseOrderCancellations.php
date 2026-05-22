<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderCancellations extends Model
{
    use HasFactory;

    public const PARTY_BUYER = 'buyer';
    public const PARTY_SUPPLIER = 'supplier';

    protected $guarded = ['id'];

    protected $casts = [
        'cancelled_at' => 'datetime',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrders::class, 'purchase_order_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderCancellationItems::class, 'purchase_order_cancellation_id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrders extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'purchase_orders';
    protected $guarded = ['id'];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'ordered_at' => 'datetime',
    ];

    public function procurementPlan()
    {
        return $this->belongsTo(ProcurementPlans::class, 'procurement_plan_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function orderedByLocation()
    {
        return $this->belongsTo(Inventory::class, 'ordered_by_inventory_id');
    }

    public function receivingLocation()
    {
        return $this->belongsTo(Inventory::class, 'receiving_inventory_id');
    }

    public function receivingInventory()
    {
        return $this->receivingLocation();
    }

    public function status()
    {
        return $this->belongsTo(PurchaseOrderStatus::class, 'status_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrdersItems::class, 'purchase_order_id');
    }

    public function receipts()
    {
        return $this->hasMany(PurchaseOrderReceipts::class, 'purchase_order_id');
    }

    public function cancellations()
    {
        return $this->hasMany(PurchaseOrderCancellations::class, 'purchase_order_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function orderedBy()
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }
}

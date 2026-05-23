<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcurementPlans extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'planned_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function planningLocation()
    {
        return $this->belongsTo(Inventory::class, 'planning_location_id');
    }

    public function status()
    {
        return $this->belongsTo(ProcurementPlanStatus::class, 'status_id');
    }

    public function items()
    {
        return $this->hasMany(ProcurementPlanItems::class, 'procurement_plan_id');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrders::class, 'procurement_plan_id');
    }

    public function plannedBy()
    {
        return $this->belongsTo(User::class, 'planned_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

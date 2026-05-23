<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RawMaterialRequests extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'needed_at' => 'date',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function requesterInventory()
    {
        return $this->belongsTo(Inventory::class, 'requester_inventory_id');
    }

    public function fulfillmentLocation()
    {
        return $this->belongsTo(Inventory::class, 'fulfillment_location_id');
    }

    public function status()
    {
        return $this->belongsTo(RawMaterialRequestStatus::class, 'status_id');
    }

    public function items()
    {
        return $this->hasMany(RawMaterialRequestItems::class, 'raw_material_request_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}

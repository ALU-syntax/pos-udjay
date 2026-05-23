<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RawMaterialRequestStatus extends Model
{
    use HasFactory, SoftDeletes;

    public const DRAFT = 'draft';
    public const SUBMITTED = 'submitted';
    public const APPROVED = 'approved';
    public const WAITING_STOCK = 'waiting_stock';
    public const WAITING_PROCUREMENT = 'waiting_procurement';
    public const PARTIALLY_FULFILLED = 'partially_fulfilled';
    public const FULFILLED = 'fulfilled';
    public const REJECTED = 'rejected';
    public const CANCELLED = 'cancelled';

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function rawMaterialRequests()
    {
        return $this->hasMany(RawMaterialRequests::class, 'status_id');
    }
}

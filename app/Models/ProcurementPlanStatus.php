<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcurementPlanStatus extends Model
{
    use HasFactory, SoftDeletes;

    public const DRAFT = 'draft';
    public const REVIEWED = 'reviewed';
    public const APPROVED = 'approved';
    public const CONVERTED_TO_PO = 'converted_to_po';
    public const CANCELLED = 'cancelled';

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function procurementPlans()
    {
        return $this->hasMany(ProcurementPlans::class, 'status_id');
    }
}

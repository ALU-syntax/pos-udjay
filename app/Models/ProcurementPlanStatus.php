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

    /** Label tampilan berbahasa Indonesia; kolom name di DB tetap bahasa Inggris dari seed. */
    public const LABELS = [
        self::DRAFT => 'Draft',
        self::REVIEWED => 'Direview',
        self::APPROVED => 'Disetujui',
        self::CONVERTED_TO_PO => 'Sudah Jadi PO',
        self::CANCELLED => 'Dibatalkan',
    ];

    /** Kelas badge gaya report (ob-*). */
    public const BADGES = [
        self::DRAFT => 'badge-muted',
        self::REVIEWED => 'badge-primary',
        self::APPROVED => 'badge-success',
        self::CONVERTED_TO_PO => 'badge-success',
        self::CANCELLED => 'badge-muted',
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getLabelAttribute(): string
    {
        return self::LABELS[$this->code] ?? $this->name;
    }

    public function getBadgeClassAttribute(): string
    {
        return self::BADGES[$this->code] ?? 'badge-muted';
    }

    public function procurementPlans()
    {
        return $this->hasMany(ProcurementPlans::class, 'status_id');
    }
}

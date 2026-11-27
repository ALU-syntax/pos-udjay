<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderStatus extends Model
{
    use HasFactory, SoftDeletes;

    public const DRAFT = 'draft';
    public const SUBMITTED = 'submitted';
    public const APPROVED = 'approved';
    public const ORDERED = 'ordered';
    public const PARTIALLY_RECEIVED = 'partially_received';
    public const RECEIVED = 'received';
    public const CANCELLED = 'cancelled';
    public const REJECTED = 'rejected';

    /** Label tampilan berbahasa Indonesia; kolom name di DB tetap bahasa Inggris dari seed. */
    public const LABELS = [
        self::DRAFT => 'Draft',
        self::SUBMITTED => 'Diajukan',
        self::APPROVED => 'Disetujui',
        self::ORDERED => 'Dipesan',
        self::PARTIALLY_RECEIVED => 'Diterima Sebagian',
        self::RECEIVED => 'Diterima',
        self::CANCELLED => 'Dibatalkan',
        self::REJECTED => 'Ditolak',
    ];

    /** Kelas badge gaya report (ob-*). */
    public const BADGES = [
        self::DRAFT => 'badge-muted',
        self::SUBMITTED => 'badge-primary',
        self::APPROVED => 'badge-success',
        self::ORDERED => 'badge-primary',
        self::PARTIALLY_RECEIVED => 'badge-warning',
        self::RECEIVED => 'badge-success',
        self::CANCELLED => 'badge-muted',
        self::REJECTED => 'badge-danger',
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

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrders::class, 'status_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrderReceiptStatus extends Model
{
    use HasFactory, SoftDeletes;

    public const DRAFT = 'draft';
    public const POSTED = 'posted';
    public const VOIDED = 'voided';

    /** Label tampilan berbahasa Indonesia; kolom name di DB tetap bahasa Inggris dari seed. */
    public const LABELS = [
        self::DRAFT => 'Draft',
        self::POSTED => 'Diposting',
        self::VOIDED => 'Dibatalkan',
    ];

    /** Kelas badge gaya report (ob-*). */
    public const BADGES = [
        self::DRAFT => 'badge-muted',
        self::POSTED => 'badge-success',
        self::VOIDED => 'badge-muted',
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

    public function receipts()
    {
        return $this->hasMany(PurchaseOrderReceipts::class, 'receipt_status_id');
    }
}

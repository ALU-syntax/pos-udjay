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

    /** Label tampilan berbahasa Indonesia; kolom name di DB tetap bahasa Inggris dari seed. */
    public const LABELS = [
        self::DRAFT => 'Draft',
        self::SUBMITTED => 'Diajukan',
        self::APPROVED => 'Disetujui',
        self::WAITING_STOCK => 'Menunggu Stok',
        self::WAITING_PROCUREMENT => 'Menunggu Pembelian',
        self::PARTIALLY_FULFILLED => 'Terpenuhi Sebagian',
        self::FULFILLED => 'Terpenuhi',
        self::REJECTED => 'Ditolak',
        self::CANCELLED => 'Dibatalkan',
    ];

    /** Kelas badge gaya report (ob-*). */
    public const BADGES = [
        self::DRAFT => 'badge-muted',
        self::SUBMITTED => 'badge-primary',
        self::APPROVED => 'badge-success',
        self::WAITING_STOCK => 'badge-warning',
        self::WAITING_PROCUREMENT => 'badge-warning',
        self::PARTIALLY_FULFILLED => 'badge-warning',
        self::FULFILLED => 'badge-success',
        self::REJECTED => 'badge-danger',
        self::CANCELLED => 'badge-muted',
    ];

    /** Status yang dianggap selesai: tidak lagi dianggap terlambat walau melewati tanggal dibutuhkan. */
    public const CLOSED = [self::FULFILLED, self::REJECTED, self::CANCELLED];

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

    public function rawMaterialRequests()
    {
        return $this->hasMany(RawMaterialRequests::class, 'status_id');
    }
}

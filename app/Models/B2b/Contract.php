<?php

namespace App\Models\B2b;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Contract extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_TERMINATED = 'terminated';

    /** Status yang masih "memakan" periode, sehingga periodenya tidak boleh tumpang tindih. */
    public const BLOCKING_STATUSES = [self::STATUS_DRAFT, self::STATUS_ACTIVE];

    protected $table = 'b2b_contracts';

    protected $guarded = ['id', 'contract_number'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'activated_at' => 'datetime',
        'terminated_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'b2b_customer_id')->withTrashed();
    }

    public function rules(): HasMany
    {
        return $this->hasMany(ContractRule::class, 'b2b_contract_id')->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function terminator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'terminated_by');
    }

    public function scopeEffectiveOn(Builder $query, Carbon $date): Builder
    {
        $day = $date->toDateString();

        return $query->where('status', self::STATUS_ACTIVE)
            ->whereDate('start_date', '<=', $day)
            ->whereDate('end_date', '>=', $day);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /** Aktif dan tanggal hari ini masuk periode. Kontrak aktif dengan tanggal mulai di masa depan belum berlaku. */
    public function isEffective(?Carbon $date = null): bool
    {
        $date = ($date ?? now())->copy()->startOfDay();

        return $this->isActive()
            && $this->start_date->lte($date)
            && $this->end_date->gte($date);
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->status === self::STATUS_ACTIVE && $this->start_date->isFuture() => 'Terjadwal',
            default => match ($this->status) {
                self::STATUS_DRAFT => 'Draft',
                self::STATUS_ACTIVE => 'Aktif',
                self::STATUS_EXPIRED => 'Berakhir',
                self::STATUS_TERMINATED => 'Dihentikan',
                default => ucfirst((string) $this->status),
            },
        };
    }

    public function statusVariant(): string
    {
        return match (true) {
            $this->status === self::STATUS_ACTIVE && $this->start_date->isFuture() => 'primary',
            default => match ($this->status) {
                self::STATUS_ACTIVE => 'success',
                self::STATUS_TERMINATED => 'danger',
                self::STATUS_EXPIRED => 'warning',
                default => 'muted',
            },
        };
    }
}

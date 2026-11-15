<?php

namespace App\Models\B2b;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Customer extends Model
{
    use SoftDeletes;

    /** Jenis customer. Hanya klasifikasi: tidak ditempel otomatis ke nama. */
    public const TYPES = [
        'individual' => 'Perorangan',
        'pt' => 'PT',
        'cv' => 'CV',
    ];

    public const REMINDER_CHANNELS = [
        'email' => 'Email',
        'whatsapp' => 'WhatsApp',
    ];

    /** Pilihan jadwal reminder: hari relatif terhadap jatuh tempo. */
    public const REMINDER_DAYS = [
        -7 => 'H-7',
        -3 => 'H-3',
        -1 => 'H-1',
        0 => 'Hari H',
    ];

    protected $table = 'b2b_customers';

    /** Kolom audit hanya diisi oleh CustomerService, tidak lewat fill(). */
    protected $guarded = ['id', 'code', 'created_by', 'modified_by'];

    protected $casts = [
        'reminder_enabled' => 'boolean',
        'reminder_channels' => 'array',
        'reminder_days' => 'array',
        'reminder_overdue_daily' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class, 'b2b_customer_id')->orderByDesc('is_primary')->orderBy('id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class, 'b2b_customer_id')->orderBy('id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'b2b_customer_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'b2b_customer_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'b2b_customer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? (string) $this->type;
    }

    /** Ringkasan pengaturan reminder untuk ditampilkan, mis. "Email · H-3, H-1, Hari H, harian setelah jatuh tempo". */
    public function reminderSummary(): string
    {
        if (! $this->reminder_enabled) {
            return 'Tidak dikirim';
        }

        $channels = collect($this->reminder_channels ?? [])->map(fn ($channel) => self::REMINDER_CHANNELS[$channel] ?? $channel)->implode(' + ');
        $days = collect(self::REMINDER_DAYS)->only($this->reminder_days ?? [])->values();
        if ($this->reminder_overdue_daily) {
            $days->push('harian setelah jatuh tempo');
        }

        return trim($channels.' · '.$days->implode(', '), ' ·');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Kontrak yang berlaku pada tanggal tertentu (status aktif dan tanggal di dalam periode). */
    public function effectiveContract(?Carbon $date = null): ?Contract
    {
        return $this->contracts()->effectiveOn($date ?? now())->first();
    }

    public function defaultBillingAddress(): ?CustomerAddress
    {
        return $this->addresses->firstWhere('is_default_billing', true) ?? $this->addresses->first();
    }

    public function defaultShippingAddress(): ?CustomerAddress
    {
        return $this->addresses->firstWhere('is_default_shipping', true) ?? $this->addresses->first();
    }
}

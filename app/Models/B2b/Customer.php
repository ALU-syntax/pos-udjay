<?php

namespace App\Models\B2b;

use App\Models\User;
use App\Models\Wilayah\District;
use App\Models\Wilayah\Province;
use App\Models\Wilayah\Regency;
use App\Models\Wilayah\Village;
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

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_code');
    }

    /** Kota/Kabupaten. */
    public function regency(): BelongsTo
    {
        return $this->belongsTo(Regency::class, 'regency_code');
    }

    /** Kecamatan. */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_code');
    }

    /** Kelurahan/Desa. */
    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class, 'village_code');
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

    /** Inisial untuk avatar, tanpa awalan badan usaha: "PT Kantor Sejahtera" → "KS". */
    public function initials(): string
    {
        $name = preg_replace('/^(pt|cv|ud)\.?\s+/i', '', trim((string) $this->name));
        $words = preg_split('/\s+/', (string) $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = implode('', array_map(fn (string $word) => mb_substr($word, 0, 1), array_slice($words, 0, 2)));

        return mb_strtoupper($initials !== '' ? $initials : '?');
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

    /** Data kontak yang kurang untuk channel reminder yang dipilih (kosong = siap dikirim). */
    public function reminderIssues(): array
    {
        if (! $this->reminder_enabled) {
            return [];
        }

        $channels = (array) ($this->reminder_channels ?? []);
        $issues = [];
        if (in_array('email', $channels, true) && ! filter_var(trim((string) $this->email), FILTER_VALIDATE_EMAIL)) {
            $issues[] = 'Email kosong atau tidak valid';
        }
        if (in_array('whatsapp', $channels, true) && blank($this->phone)) {
            $issues[] = 'Nomor WhatsApp kosong';
        }

        return $issues;
    }

    public function reminderLogs(): HasMany
    {
        return $this->hasMany(ReminderLog::class, 'b2b_customer_id');
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

    public function hasAddress(): bool
    {
        return filled($this->address);
    }

    /** Bisa dipakai untuk pengiriman internal: alamat jalan terisi dan wilayah dipilih minimal sampai kota/kabupaten. */
    public function hasDeliveryAddress(): bool
    {
        return $this->hasAddress() && filled($this->regency_code);
    }

    /**
     * Alamat lengkap satu baris, dipakai di invoice dan pesanan,
     * mis. "Jl. Raya 1, Pondok Rajeg, Kec. Cibinong, Kabupaten Bogor, Jawa Barat 16914".
     */
    public function fullAddress(): string
    {
        $district = $this->district?->name;
        $province = trim(collect([$this->province?->name, $this->postal_code])->filter(fn ($part) => filled($part))->implode(' '));

        return collect([$this->address, $this->village?->name, $district ? 'Kec. '.$district : null, $this->regency?->name, $province])
            ->filter(fn ($part) => filled($part))
            ->implode(', ');
    }

    /** Salinan alamat untuk pesanan/invoice supaya tidak ikut berubah kalau data customer diedit. Null kalau alamat kosong. */
    public function addressSnapshot(): ?array
    {
        if (! $this->hasAddress()) {
            return null;
        }

        return [
            'recipient_name' => $this->contact_name,
            'phone' => $this->phone,
            'address' => $this->address,
            'village' => $this->village?->name,
            'district' => $this->district?->name,
            'regency' => $this->regency?->name,
            'province' => $this->province?->name,
            'postal_code' => $this->postal_code,
            'full_address' => $this->fullAddress(),
        ];
    }
}

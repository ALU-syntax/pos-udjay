<?php

namespace App\Models\B2b;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Contract extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_TERMINATED = 'terminated';

    protected $table = 'b2b_contracts';

    /** Kolom audit & penghentian hanya diisi oleh ContractService, tidak lewat fill(). */
    protected $guarded = ['id', 'contract_number', 'status', 'created_by', 'modified_by', 'terminated_by', 'terminated_at', 'termination_reason'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'terminated_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'b2b_customer_id')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class, 'b2b_contract_id')->orderBy('variant_id')->orderBy('min_qty');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    public function terminator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'terminated_by');
    }

    /** Kontrak yang harganya dipakai pada tanggal tertentu: belum dihentikan dan tanggal masuk periode. */
    public function scopeEffectiveOn(Builder $query, Carbon $date): Builder
    {
        $day = $date->toDateString();

        return $query->where('status', self::STATUS_ACTIVE)
            ->whereDate('start_date', '<=', $day)
            ->whereDate('end_date', '>=', $day);
    }

    /**
     * Kontrak lanjutan = kontrak aktif paling awal milik customer yang sama yang mulai pada/setelah kontrak ini mulai.
     * Memakai tanggal mulai (bukan berakhir) supaya kontrak yang dihentikan lalu diganti kontrak baru di tengah
     * periodenya tetap dianggap sudah punya lanjutan. Kontrak aktif tidak tumpang tindih, jadi untuk kontrak aktif
     * lanjutannya selalu mulai setelah kontrak ini berakhir.
     */
    public function successor(): ?self
    {
        return static::query()
            ->where('b2b_customer_id', $this->b2b_customer_id)
            ->where('status', self::STATUS_ACTIVE)
            ->whereKeyNot($this->id)
            ->whereDate('start_date', '>=', $this->start_date->toDateString())
            ->orderBy('start_date')
            ->orderBy('id')
            ->first();
    }

    /** Tambah kolom `successor_id` (lihat successor()) untuk daftar kontrak tanpa query per baris. */
    public function scopeWithSuccessorId(Builder $query): Builder
    {
        // DB::table, bukan Eloquent: scope soft delete Eloquent merujuk nama tabel asli, bukan alias `nx`.
        return $query->addSelect(['successor_id' => DB::table('b2b_contracts as nx')
            ->select('nx.id')
            ->whereColumn('nx.b2b_customer_id', 'b2b_contracts.b2b_customer_id')
            ->whereColumn('nx.id', '<>', 'b2b_contracts.id')
            ->whereColumn('nx.start_date', '>=', 'b2b_contracts.start_date')
            ->where('nx.status', self::STATUS_ACTIVE)
            ->whereNull('nx.deleted_at')
            ->orderBy('nx.start_date')
            ->orderBy('nx.id')
            ->limit(1),
        ]);
    }

    public function isTerminated(): bool
    {
        return $this->status === self::STATUS_TERMINATED;
    }

    /** Berjalan = belum dihentikan dan hari ini (atau $date) masuk periode. */
    public function isRunning(?Carbon $date = null): bool
    {
        $date = ($date ?? now())->copy()->startOfDay();

        return ! $this->isTerminated()
            && $this->start_date->lte($date)
            && $this->end_date->gte($date);
    }

    /** Label tampilan, dihitung dari status + tanggal (tidak ada status draft/expired di database). */
    public function statusLabel(): string
    {
        return match (true) {
            $this->isTerminated() => 'Dihentikan',
            $this->start_date->gt(today()) => 'Terjadwal',
            $this->end_date->lt(today()) => 'Berakhir',
            default => 'Berjalan',
        };
    }

    /** Lama periode dalam hari, termasuk tanggal mulai dan berakhir. */
    public function durationDays(): int
    {
        return (int) $this->start_date->diffInDays($this->end_date) + 1;
    }

    /** Persentase periode yang sudah lewat (0–100), untuk bilah progres. */
    public function periodProgress(): int
    {
        $today = today();
        if ($today->lt($this->start_date)) {
            return 0;
        }
        if ($today->gt($this->end_date)) {
            return 100;
        }

        return (int) round(($this->start_date->diffInDays($today) + 1) / $this->durationDays() * 100);
    }

    /** Keterangan singkat posisi hari ini terhadap periode, mis. "Berakhir dalam 12 hari". */
    public function periodHint(): string
    {
        $today = today();

        return match (true) {
            $this->isTerminated() => 'Dihentikan '.($this->terminated_at?->translatedFormat('d M Y') ?? ''),
            $today->lt($this->start_date) => 'Mulai dalam '.$today->diffInDays($this->start_date).' hari',
            $today->gt($this->end_date) => 'Berakhir '.$this->end_date->diffInDays($today).' hari lalu',
            $today->equalTo($this->end_date) => 'Berakhir hari ini',
            default => 'Berakhir dalam '.$today->diffInDays($this->end_date).' hari',
        };
    }

    /** Berjalan dan berakhir dalam $days hari ke depan. */
    public function isEndingSoon(int $days = 30): bool
    {
        return $this->isRunning() && today()->diffInDays($this->end_date, false) <= $days;
    }

    public function statusVariant(): string
    {
        return match ($this->statusLabel()) {
            'Dihentikan' => 'danger',
            'Terjadwal' => 'primary',
            'Berakhir' => 'muted',
            default => 'success',
        };
    }
}

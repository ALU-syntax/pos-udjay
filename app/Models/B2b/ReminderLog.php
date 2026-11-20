<?php

namespace App\Models\B2b;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Satu kiriman reminder tagihan (email gabungan per customer per hari, atau pesan WhatsApp). */
class ReminderLog extends Model
{
    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const TRIGGER_AUTO = 'auto';

    public const TRIGGER_MANUAL = 'manual';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUS_LABELS = [
        self::STATUS_QUEUED => 'Antre',
        self::STATUS_SENT => 'Terkirim',
        self::STATUS_FAILED => 'Gagal',
        self::STATUS_SKIPPED => 'Dilewati',
    ];

    protected $table = 'b2b_reminder_logs';

    protected $guarded = ['id'];

    protected $casts = [
        'reminder_date' => 'date',
        'attachments' => 'array',
        'attempts' => 'integer',
        'sent_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'b2b_customer_id')->withTrashed();
    }

    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class, 'b2b_reminder_log_invoices', 'b2b_reminder_log_id', 'b2b_invoice_id')
            ->withPivot(['b2b_invoice_schedule_id', 'offset_days']);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public const CHANNEL_LABELS = [
        self::CHANNEL_EMAIL => 'Email',
        self::CHANNEL_WHATSAPP => 'WhatsApp',
    ];

    public const TRIGGER_LABELS = [
        self::TRIGGER_AUTO => 'Otomatis',
        self::TRIGGER_MANUAL => 'Manual',
    ];

    public function statusVariant(): string
    {
        return match ($this->status) {
            self::STATUS_SENT => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_SKIPPED => 'muted',
            default => 'warning',
        };
    }

    public function channelLabel(): string
    {
        return self::CHANNEL_LABELS[$this->channel] ?? ucfirst((string) $this->channel);
    }

    public function channelIcon(): string
    {
        return $this->channel === self::CHANNEL_WHATSAPP ? 'fab fa-whatsapp' : 'fas fa-envelope';
    }

    /** Kiriman gagal/dilewati boleh dikirim ulang (sebagai kiriman manual baru). */
    public function canResend(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_SKIPPED], true);
    }

    /** Label hari relatif: H-3, Hari H, H+2. */
    public static function offsetLabel(int $offset): string
    {
        return match (true) {
            $offset === 0 => 'Hari H',
            $offset < 0 => 'H'.$offset,
            default => 'H+'.$offset,
        };
    }
}

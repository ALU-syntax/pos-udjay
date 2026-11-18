<?php

namespace App\Models\B2b;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_VOID = 'void';

    public const STATUS_LABELS = [
        self::STATUS_UNPAID => 'Belum dibayar',
        self::STATUS_PARTIAL => 'Dibayar sebagian',
        self::STATUS_PAID => 'Lunas',
        self::STATUS_OVERDUE => 'Lewat jatuh tempo',
        self::STATUS_VOID => 'Dibatalkan',
    ];

    protected $table = 'b2b_invoices';

    protected $guarded = ['id', 'invoice_number'];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'amount' => 'integer',
        'unique_code_amount' => 'integer',
        'total_to_pay' => 'integer',
        'paid_amount' => 'integer',
        'paid_at' => 'datetime',
        'voided_at' => 'datetime',
        'billing_address_snapshot' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'b2b_order_id')->withTrashed();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'b2b_customer_id')->withTrashed();
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(InvoiceSchedule::class, 'b2b_invoice_id')->orderBy('sequence');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class, 'b2b_invoice_id')->orderBy('id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'b2b_invoice_id')->orderBy('id');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /** Pelunasan manual hanya untuk invoice tempo yang masih punya sisa; pesanan bayar langsung sudah lunas sejak dibuat. */
    public function canRecordPayment(): bool
    {
        return in_array($this->status, [self::STATUS_UNPAID, self::STATUS_PARTIAL, self::STATUS_OVERDUE], true)
            && $this->outstandingAmount() > 0
            && $this->order?->payment_type !== Order::PAYMENT_IMMEDIATE
            && $this->order?->status !== Order::STATUS_CANCELLED;
    }

    public function outstandingAmount(): int
    {
        return $this->status === self::STATUS_VOID ? 0 : max(0, $this->total_to_pay - $this->paid_amount);
    }

    /** Status untuk ditampilkan: tagihan belum lunas yang sudah lewat jatuh tempo dianggap overdue walau cron belum jalan. */
    public function displayStatus(): string
    {
        if (in_array($this->status, [self::STATUS_UNPAID, self::STATUS_PARTIAL], true) && $this->due_date->lt(today())) {
            return self::STATUS_OVERDUE;
        }

        return $this->status;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->displayStatus()] ?? ucfirst((string) $this->status);
    }

    public function statusVariant(): string
    {
        return match ($this->displayStatus()) {
            self::STATUS_PAID => 'success',
            self::STATUS_PARTIAL => 'primary',
            self::STATUS_OVERDUE => 'danger',
            self::STATUS_VOID => 'muted',
            default => 'warning',
        };
    }
}

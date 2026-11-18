<?php

namespace App\Models\B2b;

use App\Models\CategoryPayment;
use App\Models\Outlets;
use App\Models\Payment as PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu penerimaan uang dari customer B2B. Metode merujuk master pembayaran POS. */
class Payment extends Model
{
    public const STATUS_POSTED = 'posted';

    public const STATUS_REVERSED = 'reversed';

    public const STATUS_LABELS = [
        self::STATUS_POSTED => 'Diterima',
        self::STATUS_REVERSED => 'Dibatalkan',
    ];

    protected $table = 'b2b_payments';

    protected $guarded = ['id', 'payment_number'];

    protected $casts = [
        'outlet_id' => 'integer',
        'category_payment_id' => 'integer',
        'payment_id' => 'integer',
        'amount' => 'integer',
        'paid_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'b2b_customer_id')->withTrashed();
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlets::class, 'outlet_id');
    }

    public function categoryPayment(): BelongsTo
    {
        return $this->belongsTo(CategoryPayment::class, 'category_payment_id')->withTrashed();
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_id')->withTrashed();
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class, 'b2b_payment_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'b2b_payment_id');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }
}

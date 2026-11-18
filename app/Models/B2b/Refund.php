<?php

namespace App\Models\B2b;

use App\Models\CategoryPayment;
use App\Models\Outlets;
use App\Models\Payment as PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pengembalian uang atas pembayaran yang dibatalkan. */
class Refund extends Model
{
    protected $table = 'b2b_refunds';

    protected $guarded = ['id'];

    protected $casts = [
        'outlet_id' => 'integer',
        'category_payment_id' => 'integer',
        'payment_id' => 'integer',
        'amount' => 'integer',
        'refunded_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'b2b_payment_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'b2b_invoice_id')->withTrashed();
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

    public function refunder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }
}

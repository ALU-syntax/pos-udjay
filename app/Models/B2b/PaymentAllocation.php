<?php

namespace App\Models\B2b;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Bagian pembayaran yang dipakai untuk satu invoice (dan jadwal tagihannya). */
class PaymentAllocation extends Model
{
    protected $table = 'b2b_payment_allocations';

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'b2b_payment_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'b2b_invoice_id')->withTrashed();
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(InvoiceSchedule::class, 'b2b_invoice_schedule_id');
    }
}

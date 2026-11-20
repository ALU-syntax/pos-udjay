<?php

namespace App\Models\B2b;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceSchedule extends Model
{
    protected $table = 'b2b_invoice_schedules';

    protected $guarded = ['id'];

    protected $casts = [
        'sequence' => 'integer',
        'due_date' => 'date',
        'amount' => 'integer',
        'unique_code' => 'integer',
        'amount_to_pay' => 'integer',
        'paid_amount' => 'integer',
        'paid_at' => 'datetime',
        'overdue_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'b2b_invoice_id');
    }
}

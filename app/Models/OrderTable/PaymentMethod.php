<?php

namespace App\Models\OrderTable;

use App\Models\CategoryPayment;
use App\Models\Outlets;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethod extends Model
{
    protected $table = 'ot_payment_methods';

    protected $guarded = ['id'];

    protected $casts = [
        'outlet_id' => 'integer',
        'payment_id' => 'integer',
        'category_payment_id' => 'integer',
        'qris_expiry_minutes' => 'integer',
        'payment_due_minutes' => 'integer',
        'enabled' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlets::class, 'outlet_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function categoryPayment(): BelongsTo
    {
        return $this->belongsTo(CategoryPayment::class, 'category_payment_id');
    }
}

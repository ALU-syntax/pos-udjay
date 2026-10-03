<?php

namespace App\Models\OrderTable;

use App\Models\Outlets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $table = 'ot_orders';

    protected $guarded = ['id'];

    protected $casts = [
        'outlet_id' => 'integer',
        'session_id' => 'integer',
        'table_id' => 'integer',
        'voucher_id' => 'integer',
        'tax_breakdown' => 'array',
        'placed_at' => 'datetime',
        'payment_due_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlets::class, 'outlet_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TableSession::class, 'session_id');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class, 'table_id');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function voucherRedemption(): HasOne
    {
        return $this->hasOne(VoucherRedemption::class, 'order_id');
    }
}

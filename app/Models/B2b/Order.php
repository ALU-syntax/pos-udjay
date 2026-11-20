<?php

namespace App\Models\B2b;

use App\Models\Outlets;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_CONFIRMED => 'Dikonfirmasi',
        self::STATUS_COMPLETED => 'Selesai',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    public const PAYMENT_CREDIT = 'credit';

    public const PAYMENT_IMMEDIATE = 'immediate';

    public const PAYMENT_TYPE_LABELS = [
        self::PAYMENT_CREDIT => 'Tempo',
        self::PAYMENT_IMMEDIATE => 'Bayar langsung',
    ];

    public const DELIVERY_LABELS = [
        'pickup' => 'Diambil sendiri',
        'internal' => 'Diantar kurir internal',
    ];

    protected $table = 'b2b_orders';

    protected $guarded = ['id', 'order_number'];

    protected $casts = [
        'outlet_id' => 'integer',
        'order_date' => 'datetime',
        'due_date' => 'date',
        'subtotal' => 'integer',
        'tax_total' => 'integer',
        'shipping_cost' => 'integer',
        'grand_total' => 'integer',
        'shipping_address_snapshot' => 'array',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'b2b_customer_id')->withTrashed();
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'b2b_contract_id')->withTrashed();
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlets::class, 'outlet_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'b2b_order_id')->orderBy('id');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'b2b_order_id');
    }

    /**
     * Permission untuk membatalkan pesanan ini: pesanan selesai butuh izin terpisah supaya tidak semua akun
     * bisa membatalkan barang yang sudah diserahkan. Null kalau pesanan sudah dibatalkan.
     */
    public function cancelPermission(): ?string
    {
        return match ($this->status) {
            self::STATUS_CONFIRMED => 'cancel b2b/orders',
            self::STATUS_COMPLETED => 'cancel-completed b2b/orders',
            default => null,
        };
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusVariant(): string
    {
        return match ($this->status) {
            self::STATUS_CONFIRMED => 'primary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'muted',
        };
    }

    public function paymentTypeLabel(): string
    {
        return self::PAYMENT_TYPE_LABELS[$this->payment_type] ?? (string) $this->payment_type;
    }

    public function deliveryLabel(): string
    {
        return self::DELIVERY_LABELS[$this->delivery_method] ?? (string) $this->delivery_method;
    }
}

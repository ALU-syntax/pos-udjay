<?php

namespace App\Models\B2b;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerAddress extends Model
{
    use SoftDeletes;

    protected $table = 'b2b_customer_addresses';

    protected $guarded = ['id'];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_default_billing' => 'boolean',
        'is_default_shipping' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'b2b_customer_id');
    }

    /** Alamat lengkap satu baris, dipakai di invoice dan pesanan. */
    public function fullAddress(): string
    {
        return collect([$this->address, $this->city, $this->province, $this->postal_code])
            ->filter(fn ($part) => filled($part))
            ->implode(', ');
    }

    /** Salinan alamat yang disimpan ke pesanan/invoice supaya tidak berubah kalau alamat customer diedit. */
    public function toSnapshot(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'recipient_name' => $this->recipient_name,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postal_code,
            'full_address' => $this->fullAddress(),
        ];
    }
}

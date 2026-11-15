<?php

namespace App\Models\B2b;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerContact extends Model
{
    use SoftDeletes;

    protected $table = 'b2b_customer_contacts';

    protected $guarded = ['id'];

    protected $casts = [
        'is_primary' => 'boolean',
        'receive_reminder' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'b2b_customer_id');
    }
}

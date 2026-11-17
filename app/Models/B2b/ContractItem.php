<?php

namespace App\Models\B2b;

use App\Models\VariantProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Satu harga kontrak untuk satu varian, berlaku mulai qty `min_qty`. */
class ContractItem extends Model
{
    use SoftDeletes;

    protected $table = 'b2b_contract_items';

    protected $guarded = ['id'];

    protected $casts = [
        'variant_id' => 'integer',
        'min_qty' => 'integer',
        'price' => 'integer',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'b2b_contract_id')->withTrashed();
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(VariantProduct::class, 'variant_id')->withTrashed();
    }
}

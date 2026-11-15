<?php

namespace App\Models\B2b;

use App\Models\Category;
use App\Models\Outlets;
use App\Models\Product;
use App\Models\VariantProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractRule extends Model
{
    public const SCOPES = ['variant', 'product', 'category', 'all'];

    public const DISCOUNT_TYPES = ['percent', 'nominal', 'fixed_price'];

    protected $table = 'b2b_contract_rules';

    protected $guarded = ['id'];

    protected $casts = [
        'outlet_id' => 'integer',
        'category_id' => 'integer',
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'discount_value' => 'float',
        'min_qty' => 'integer',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'b2b_contract_id');
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlets::class, 'outlet_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id')->withTrashed();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id')->withTrashed();
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(VariantProduct::class, 'variant_id')->withTrashed();
    }

    /** Peringkat kekhususan: makin kecil makin spesifik (varian → produk → kategori → semua). */
    public function specificity(): int
    {
        return array_search($this->scope, self::SCOPES, true);
    }

    public function targetLabel(): string
    {
        return match ($this->scope) {
            'variant' => $this->variant
                ? trim(($this->product?->name ?? $this->variant->product?->name).' - '.$this->variant->name, ' -')
                : 'Varian #'.$this->variant_id,
            'product' => $this->product?->name ?? 'Produk #'.$this->product_id,
            'category' => $this->category?->name ?? 'Kategori #'.$this->category_id,
            default => 'Semua produk',
        };
    }

    public function discountLabel(): string
    {
        return match ($this->discount_type) {
            'percent' => 'Diskon '.rtrim(rtrim(number_format($this->discount_value, 2, ',', '.'), '0'), ',').'%',
            'nominal' => 'Potongan Rp '.number_format($this->discount_value, 0, ',', '.').'/unit',
            'fixed_price' => 'Harga khusus Rp '.number_format($this->discount_value, 0, ',', '.'),
            default => '-',
        };
    }
}

<?php

namespace App\Services\B2b;

use App\Models\B2b\Contract;
use App\Models\B2b\ContractRule;
use App\Models\B2b\Customer;
use App\Models\VariantProduct;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Perhitungan harga B2B. Satu-satunya sumber kebenaran harga: layar kasir hanya menampilkan
 * hasil quote(), dan OrderService memanggil quote() lagi saat pesanan disimpan.
 *
 * Aturan kontrak:
 * - hanya aturan dengan min_qty <= qty yang dipertimbangkan;
 * - yang paling spesifik menang: varian → produk → kategori → semua produk;
 * - di level yang sama, aturan khusus outlet menang atas aturan semua outlet, lalu min_qty terbesar.
 */
class PricingService
{
    /**
     * @param  array<int, array{variant_id:int, qty:int, notes?:string|null}>  $lines
     * @param  Collection<int, VariantProduct>|null  $variants  varian yang sudah dimuat (mis. sudah di-lock)
     */
    public function quote(Customer $customer, int $outletId, array $lines, ?Carbon $date = null, ?Collection $variants = null): array
    {
        $date ??= now();
        $contract = $customer->contracts()->effectiveOn($date)->with('rules')->first();
        $variants ??= $this->loadVariants($outletId, array_column($lines, 'variant_id'));

        $items = [];
        foreach ($lines as $index => $line) {
            $variant = $variants->get((int) $line['variant_id']);
            if (! $variant) {
                throw ValidationException::withMessages([
                    "items.$index.variant_id" => 'Produk tidak ditemukan di outlet ini atau sudah dihapus.',
                ]);
            }

            $items[] = $this->priceLine($variant, max(1, (int) $line['qty']), $contract, $outletId, $line['notes'] ?? null);
        }

        $subtotal = array_sum(array_map(fn ($item) => $item['base_price'] * $item['qty'], $items));
        $grandTotal = array_sum(array_column($items, 'line_total'));

        return [
            'contract' => $contract,
            'items' => $items,
            'subtotal' => $subtotal,
            'discount_total' => $subtotal - $grandTotal,
            'grand_total' => $grandTotal,
            'has_insufficient_stock' => collect($items)->contains('stock_insufficient', true),
        ];
    }

    /** @return Collection<int, VariantProduct> keyed by id */
    public function loadVariants(int $outletId, array $variantIds, bool $lock = false): Collection
    {
        $query = VariantProduct::query()
            ->with('product')
            ->whereIn('id', array_unique(array_map('intval', $variantIds)))
            ->whereHas('product', fn ($product) => $product->where('outlet_id', $outletId));

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get()->keyBy('id');
    }

    public function priceLine(VariantProduct $variant, int $qty, ?Contract $contract, int $outletId, ?string $notes = null): array
    {
        $basePrice = (int) $variant->harga;
        $rule = $contract ? $this->resolveRule($contract->rules, $variant, $outletId, $qty) : null;
        $discountPerUnit = $rule ? $this->discountPerUnit($rule, $basePrice) : 0;
        $finalPrice = $basePrice - $discountPerUnit;

        return [
            'variant_id' => $variant->id,
            'product_id' => $variant->product_id,
            'product_name' => $variant->product->name,
            'variant_name' => $variant->name,
            'qty' => $qty,
            'base_price' => $basePrice,
            'rule' => $rule,
            'b2b_contract_rule_id' => $rule?->id,
            'discount_type' => $rule?->discount_type,
            'discount_value' => $rule?->discount_value,
            'discount_label' => $rule?->discountLabel(),
            'discount_per_unit' => $discountPerUnit,
            'final_price' => $finalPrice,
            'line_total' => $finalPrice * $qty,
            'stock' => (int) $variant->stok,
            'stock_insufficient' => (int) $variant->stok < $qty,
            'notes' => $notes,
        ];
    }

    /** @param  Collection<int, ContractRule>  $rules */
    public function resolveRule(Collection $rules, VariantProduct $variant, int $outletId, int $qty): ?ContractRule
    {
        $product = $variant->product;

        return $rules
            ->filter(fn (ContractRule $rule) => $rule->min_qty <= $qty)
            ->filter(fn (ContractRule $rule) => $rule->outlet_id === null || $rule->outlet_id === $outletId)
            ->filter(fn (ContractRule $rule) => match ($rule->scope) {
                'variant' => $rule->variant_id === (int) $variant->id,
                'product' => $rule->product_id === (int) $variant->product_id,
                'category' => $product && $product->category_id !== null && $rule->category_id === (int) $product->category_id,
                'all' => true,
                default => false,
            })
            ->sortBy([
                fn (ContractRule $a, ContractRule $b) => $a->specificity() <=> $b->specificity(),
                fn (ContractRule $a, ContractRule $b) => ($a->outlet_id === null) <=> ($b->outlet_id === null),
                fn (ContractRule $a, ContractRule $b) => $b->min_qty <=> $a->min_qty,
                fn (ContractRule $a, ContractRule $b) => $a->id <=> $b->id,
            ])
            ->first();
    }

    /** Potongan per unit dalam rupiah, tidak pernah negatif dan tidak melebihi harga dasar. */
    public function discountPerUnit(ContractRule $rule, int $basePrice): int
    {
        $discount = match ($rule->discount_type) {
            'percent' => (int) round($basePrice * min(100, max(0, $rule->discount_value)) / 100),
            'nominal' => (int) round($rule->discount_value),
            // Harga khusus lebih tinggi dari harga normal tidak menaikkan harga.
            'fixed_price' => $basePrice - (int) round($rule->discount_value),
            default => 0,
        };

        return max(0, min($basePrice, $discount));
    }
}

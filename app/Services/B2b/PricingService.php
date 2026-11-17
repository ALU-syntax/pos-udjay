<?php

namespace App\Services\B2b;

use App\Models\B2b\Contract;
use App\Models\B2b\ContractItem;
use App\Models\B2b\Customer;
use App\Models\VariantProduct;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Perhitungan harga B2B. Satu-satunya sumber kebenaran harga: layar kasir hanya menampilkan
 * hasil quote(), dan OrderService memanggil quote() lagi saat pesanan disimpan.
 *
 * Harga kontrak ditulis langsung per varian (bukan diskon):
 * - dipakai baris dengan min_qty terbesar yang masih <= qty (harga bertingkat);
 * - varian tanpa baris yang memenuhi (tidak ada di kontrak / qty belum mencapai min_qty) memakai harga normal;
 * - harga kontrak boleh lebih tinggi dari harga normal.
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
        $contract = $customer->contracts()->effectiveOn($date)->with('items')->first();
        $variants ??= $this->loadVariants($outletId, array_column($lines, 'variant_id'));

        $items = [];
        foreach ($lines as $index => $line) {
            $variant = $variants->get((int) $line['variant_id']);
            if (! $variant) {
                throw ValidationException::withMessages([
                    "items.$index.variant_id" => 'Produk tidak ditemukan di outlet ini atau sudah dihapus.',
                ]);
            }

            $items[] = $this->priceLine($variant, max(1, (int) $line['qty']), $contract, $line['notes'] ?? null);
        }

        $subtotal = array_sum(array_column($items, 'line_total'));

        return [
            'contract' => $contract,
            'items' => $items,
            'subtotal' => $subtotal,
            'grand_total' => $subtotal,
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

    public function priceLine(VariantProduct $variant, int $qty, ?Contract $contract, ?string $notes = null): array
    {
        $basePrice = (int) $variant->harga;
        $contractItem = $contract ? $this->resolveItem($contract->items, (int) $variant->id, $qty) : null;
        $finalPrice = $contractItem ? $contractItem->price : $basePrice;

        return [
            'variant_id' => $variant->id,
            'product_id' => $variant->product_id,
            'product_name' => $variant->product->name,
            'variant_name' => $variant->name,
            'qty' => $qty,
            'base_price' => $basePrice,
            'contract_item' => $contractItem,
            'b2b_contract_item_id' => $contractItem?->id,
            'min_qty' => $contractItem?->min_qty,
            'final_price' => $finalPrice,
            'line_total' => $finalPrice * $qty,
            'stock' => (int) $variant->stok,
            'stock_insufficient' => (int) $variant->stok < $qty,
            'notes' => $notes,
        ];
    }

    /** @param  Collection<int, ContractItem>  $items */
    public function resolveItem(Collection $items, int $variantId, int $qty): ?ContractItem
    {
        return $items
            ->filter(fn (ContractItem $item) => $item->variant_id === $variantId && $item->min_qty <= $qty)
            ->sortByDesc('min_qty')
            ->first();
    }
}

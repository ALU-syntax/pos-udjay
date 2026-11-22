<?php

namespace App\Http\Requests\B2b;

use App\Models\B2b\Contract;
use App\Models\VariantProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can($this->editing() ? 'update b2b/contracts' : 'create b2b/contracts');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['items' => array_values((array) $this->input('items', []))]);
    }

    public function rules(): array
    {
        return [
            // Customer tidak bisa diganti setelah kontrak dibuat (pesanan lama merujuk kontrak ini).
            'b2b_customer_id' => $this->editing()
                ? ['prohibited']
                : ['required', 'integer', Rule::exists('b2b_customers', 'id')->whereNull('deleted_at')],
            // Kontrak asal saat perpanjangan (tombol Perpanjang); hanya dicatat di log.
            'copied_from' => $this->editing() ? ['prohibited'] : ['nullable', 'integer'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
            // Hapus file kontrak yang sudah tersimpan (hanya saat edit, dan diabaikan kalau ada file baru).
            'remove_attachment' => ['nullable', 'boolean'],

            'items' => ['required', 'array', 'min:1', 'max:3000'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.variant_id' => ['required', 'integer', Rule::exists('variant_products', 'id')],
            'items.*.min_qty' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.price' => ['required', 'integer', 'min:0', 'max:999999999999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'b2b_customer_id' => 'customer',
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal berakhir',
            'attachment' => 'file kontrak',
            'items' => 'harga kontrak',
            'items.*.variant_id' => 'produk',
            'items.*.min_qty' => 'mulai qty',
            'items.*.price' => 'harga kontrak',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Isi minimal satu harga produk.',
            'items.min' => 'Isi minimal satu harga produk.',
            'b2b_customer_id.prohibited' => 'Customer kontrak tidak bisa diganti.',
        ];
    }

    /** Satu varian boleh punya beberapa harga, asal "mulai qty"-nya berbeda. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $items = (array) $this->input('items', []);
            $deletedIds = $this->deletedVariantIds(array_map(fn ($item) => (int) $item['variant_id'], $items));

            $seen = [];
            foreach ($items as $index => $item) {
                if (in_array((int) $item['variant_id'], $deletedIds, true)) {
                    $validator->errors()->add("items.$index.variant_id", 'Produk ini sudah dihapus. Buang dari daftar harga kontrak.');
                }

                $key = (int) $item['variant_id'].'|'.(int) $item['min_qty'];
                if (isset($seen[$key])) {
                    $validator->errors()->add("items.$index.min_qty", 'Produk ini sudah punya harga untuk qty yang sama (baris '.($seen[$key] + 1).').');
                }
                $seen[$key] = $index;
            }
        });
    }

    /** Data kontrak siap simpan. */
    public function contractData(): array
    {
        return array_merge(
            $this->safe()->only(['b2b_customer_id', 'start_date', 'end_date', 'notes']),
            ['copied_from' => $this->copiedFrom()],
            ['items' => array_map(fn (array $item) => [
                'id' => filled($item['id'] ?? null) ? (int) $item['id'] : null,
                'variant_id' => (int) $item['variant_id'],
                'min_qty' => (int) $item['min_qty'],
                'price' => (int) $item['price'],
            ], $this->validated('items'))],
        );
    }

    /**
     * Kontrak asal perpanjangan. Diabaikan kalau tidak ditemukan atau customer di form sudah diganti,
     * karena kontrak baru itu bukan lagi perpanjangan kontrak asal.
     */
    private function copiedFrom(): ?Contract
    {
        $id = $this->validated('copied_from');
        $source = $id ? Contract::find($id) : null;

        return $source && (int) $source->b2b_customer_id === (int) $this->validated('b2b_customer_id') ? $source : null;
    }

    /**
     * Varian yang varian/produknya sudah dihapus. Varian yang sudah ada di kontrak yang sedang diedit
     * dibiarkan, supaya kontrak lama tetap bisa diedit tanpa harus membuang barisnya.
     *
     * @param  array<int, int>  $variantIds
     * @return array<int, int>
     */
    private function deletedVariantIds(array $variantIds): array
    {
        $existing = $this->editing() ? $this->route('contract')->items()->pluck('variant_id')->all() : [];

        return VariantProduct::withTrashed()
            ->with('product')
            ->whereIn('id', array_diff(array_unique($variantIds), $existing))
            ->get()
            ->filter(fn (VariantProduct $variant) => $variant->trashed() || $variant->product === null)
            ->map(fn (VariantProduct $variant) => (int) $variant->id)
            ->values()
            ->all();
    }

    private function editing(): bool
    {
        return $this->route('contract') !== null;
    }
}

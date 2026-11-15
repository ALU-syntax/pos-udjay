<?php

namespace App\Http\Requests\B2b;

use App\Models\B2b\ContractRule;
use App\Models\Product;
use App\Models\VariantProduct;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ContractRequest extends FormRequest
{
    /** @var array<int, array>|null */
    private ?array $normalizedRules = null;

    public function authorize(): bool
    {
        return (bool) $this->user()?->can($this->route('contract') ? 'update b2b/contracts' : 'create b2b/contracts');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'activate' => $this->boolean('activate'),
            'rules' => array_values((array) $this->input('rules', [])),
        ]);
    }

    public function rules(): array
    {
        return [
            'b2b_customer_id' => ['required', 'integer', Rule::exists('b2b_customers', 'id')->whereNull('deleted_at')],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
            'activate' => ['boolean'],

            'rules' => ['array', 'max:300'],
            'rules.*.scope' => ['required', Rule::in(ContractRule::SCOPES)],
            'rules.*.outlet_id' => ['nullable', 'integer', Rule::exists('outlets', 'id')],
            'rules.*.category_id' => ['nullable', 'required_if:rules.*.scope,category', 'integer', Rule::exists('categories', 'id')->whereNull('deleted_at')],
            'rules.*.product_id' => ['nullable', 'required_if:rules.*.scope,product', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'rules.*.variant_id' => ['nullable', 'required_if:rules.*.scope,variant', 'integer', Rule::exists('variant_products', 'id')->whereNull('deleted_at')],
            'rules.*.discount_type' => ['required', Rule::in(ContractRule::DISCOUNT_TYPES)],
            'rules.*.discount_value' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'rules.*.min_qty' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'b2b_customer_id' => 'customer',
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal berakhir',
            'attachment' => 'file kontrak',
            'rules.*.scope' => 'cakupan',
            'rules.*.category_id' => 'kategori',
            'rules.*.product_id' => 'produk',
            'rules.*.variant_id' => 'varian',
            'rules.*.discount_type' => 'tipe diskon',
            'rules.*.discount_value' => 'nilai diskon',
            'rules.*.min_qty' => 'minimal qty',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $rules = $this->normalizedRules();
            if ($rules === []) {
                $validator->errors()->add('rules', 'Isi minimal satu aturan diskon.');
            }

            $seen = [];
            foreach ($rules as $index => $rule) {
                if ($rule['discount_type'] === 'percent' && ($rule['discount_value'] <= 0 || $rule['discount_value'] > 100)) {
                    $validator->errors()->add("rules.$index.discount_value", 'Diskon persen harus lebih dari 0 dan maksimal 100.');
                }
                if ($rule['discount_type'] === 'nominal' && $rule['discount_value'] <= 0) {
                    $validator->errors()->add("rules.$index.discount_value", 'Potongan nominal harus lebih dari 0.');
                }

                $key = implode('|', [$rule['scope'], $rule['outlet_id'], $rule['category_id'], $rule['product_id'], $rule['variant_id'], $rule['min_qty']]);
                if (isset($seen[$key])) {
                    $validator->errors()->add("rules.$index.min_qty", 'Aturan dengan cakupan, outlet, target, dan minimal qty yang sama sudah ada (baris '.($seen[$key] + 1).').');
                }
                $seen[$key] = $index;
            }
        });
    }

    /** Data kontrak siap simpan, dengan aturan yang sudah dinormalisasi. */
    public function contractData(): array
    {
        return array_merge(
            $this->safe()->only(['b2b_customer_id', 'start_date', 'end_date', 'notes']),
            ['rules' => $this->normalizedRules()],
        );
    }

    /**
     * Isi kolom FK sesuai cakupan. Aturan produk/varian otomatis mengikuti outlet produknya,
     * dan aturan varian ikut menyimpan product_id untuk label.
     */
    private function normalizedRules(): array
    {
        if ($this->normalizedRules !== null) {
            return $this->normalizedRules;
        }

        $input = (array) $this->input('rules', []);
        $products = Product::query()->whereIn('id', array_filter(array_column($input, 'product_id')))->get()->keyBy('id');
        $variants = VariantProduct::query()->with('product')->whereIn('id', array_filter(array_column($input, 'variant_id')))->get()->keyBy('id');

        return $this->normalizedRules = array_map(function (array $rule) use ($products, $variants) {
            $scope = $rule['scope'];
            $normalized = [
                'scope' => $scope,
                'outlet_id' => filled($rule['outlet_id'] ?? null) ? (int) $rule['outlet_id'] : null,
                'category_id' => null,
                'product_id' => null,
                'variant_id' => null,
                'discount_type' => $rule['discount_type'],
                'discount_value' => round((float) $rule['discount_value'], 2),
                'min_qty' => (int) $rule['min_qty'],
            ];

            if ($scope === 'category') {
                $normalized['category_id'] = (int) $rule['category_id'];
            } elseif ($scope === 'product') {
                $product = $products->get((int) $rule['product_id']);
                $normalized['product_id'] = (int) $rule['product_id'];
                $normalized['outlet_id'] = $product?->outlet_id !== null ? (int) $product->outlet_id : $normalized['outlet_id'];
            } elseif ($scope === 'variant') {
                $variant = $variants->get((int) $rule['variant_id']);
                $normalized['variant_id'] = (int) $rule['variant_id'];
                $normalized['product_id'] = $variant?->product_id;
                $normalized['outlet_id'] = $variant?->product?->outlet_id !== null ? (int) $variant->product->outlet_id : $normalized['outlet_id'];
            }

            return $normalized;
        }, array_values($input));
    }
}

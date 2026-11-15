<?php

namespace App\Http\Requests\B2b;

use App\Services\B2b\B2bSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Dipakai untuk preview harga (quote) dan simpan pesanan dari layar Kasir B2B. */
class CashierOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->routeIs('b2b/cashier/quote') ? 'read b2b/cashier' : 'create b2b/cashier';

        return (bool) $this->user()?->can($ability);
    }

    public function rules(): array
    {
        $placing = ! $this->routeIs('b2b/cashier/quote');

        return [
            'outlet_id' => ['required', 'integer', Rule::in($this->user()->outletIds())],
            'b2b_customer_id' => ['required', 'integer', Rule::exists('b2b_customers', 'id')->whereNull('deleted_at')],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.variant_id' => ['required', 'integer', 'distinct'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'delivery_method' => [Rule::requiredIf($placing), Rule::in(['pickup', 'internal'])],
            'due_date' => [Rule::requiredIf($placing), 'nullable', 'date_format:Y-m-d', 'after_or_equal:today',
                'before_or_equal:'.app(B2bSettings::class)->maxDueDate()->toDateString()],
            'shipping_address_id' => ['nullable', 'required_if:delivery_method,internal', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'outlet_id' => 'outlet',
            'b2b_customer_id' => 'customer',
            'items' => 'item pesanan',
            'items.*.qty' => 'qty',
            'shipping_address_id' => 'alamat pengiriman',
            'due_date' => 'jatuh tempo',
        ];
    }

    public function messages(): array
    {
        return [
            'outlet_id.in' => 'Anda tidak punya akses ke outlet ini.',
            'items.required' => 'Keranjang masih kosong.',
            'items.*.variant_id.distinct' => 'Produk yang sama muncul lebih dari sekali di keranjang.',
            'due_date.after_or_equal' => 'Jatuh tempo tidak boleh sebelum hari ini.',
            'due_date.before_or_equal' => 'Jatuh tempo maksimal '.B2bSettings::MAX_DUE_DAYS.' hari dari hari ini.',
        ];
    }
}

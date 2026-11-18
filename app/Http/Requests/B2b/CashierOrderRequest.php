<?php

namespace App\Http\Requests\B2b;

use App\Models\B2b\Order;
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

        $rules = [
            'outlet_id' => ['required', 'integer', Rule::in($this->user()->outletIds())],
            'b2b_customer_id' => ['required', 'integer', Rule::exists('b2b_customers', 'id')->whereNull('deleted_at')],
            'items' => ['required', 'array', 'min:1', 'max:200'],
            'items.*.variant_id' => ['required', 'integer', 'distinct'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'delivery_method' => [Rule::requiredIf($placing), Rule::in(['pickup', 'internal'])],
            // Bayar langsung tidak memakai jatuh tempo (diisi tanggal pesanan oleh OrderService).
            'due_date' => ['exclude_if:payment_type,'.Order::PAYMENT_IMMEDIATE, Rule::requiredIf($placing), 'nullable', 'date_format:Y-m-d',
                'after_or_equal:today', 'before_or_equal:'.app(B2bSettings::class)->maxDueDate()->toDateString()],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        if ($placing) {
            // Kecocokan kategori dengan sub metode dan status aktifnya dicek lagi di PaymentService.
            $rules += [
                'payment_type' => ['nullable', Rule::in(array_keys(Order::PAYMENT_TYPE_LABELS))],
                'payment' => ['exclude_unless:payment_type,'.Order::PAYMENT_IMMEDIATE, 'required', 'array'],
                'payment.category_payment_id' => ['exclude_unless:payment_type,'.Order::PAYMENT_IMMEDIATE, 'required', 'integer'],
                'payment.payment_id' => ['exclude_unless:payment_type,'.Order::PAYMENT_IMMEDIATE, 'nullable', 'integer'],
                'payment.reference' => ['exclude_unless:payment_type,'.Order::PAYMENT_IMMEDIATE, 'nullable', 'string', 'max:100'],
            ];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'outlet_id' => 'outlet',
            'b2b_customer_id' => 'customer',
            'items' => 'item pesanan',
            'items.*.qty' => 'qty',
            'due_date' => 'jatuh tempo',
            'payment_type' => 'jenis pembayaran',
            'payment.category_payment_id' => 'metode pembayaran',
            'payment.payment_id' => 'jenis metode pembayaran',
            'payment.reference' => 'no. referensi',
        ];
    }

    public function messages(): array
    {
        return [
            'outlet_id.in' => 'Anda tidak punya akses ke outlet ini.',
            'items.required' => 'Keranjang masih kosong.',
            'items.*.variant_id.distinct' => 'Produk yang sama muncul lebih dari sekali di keranjang.',
            'due_date.after_or_equal' => 'Jatuh tempo tidak boleh sebelum hari ini.',
            'payment.required' => 'Pilih metode pembayaran.',
            'payment.category_payment_id.required' => 'Pilih metode pembayaran.',
            'due_date.before_or_equal' => 'Jatuh tempo maksimal '.B2bSettings::MAX_DUE_DAYS.' hari dari hari ini.',
        ];
    }
}

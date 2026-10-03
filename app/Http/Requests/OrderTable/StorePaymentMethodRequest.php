<?php

namespace App\Http\Requests\OrderTable;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePaymentMethodRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'label' => trim((string) $this->input('label')),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create order-table/payment-methods') === true
            && in_array($this->integer('outlet_id'), $this->user()->outletIds(), true);
    }

    public function rules(): array
    {
        $code = $this->input('code');

        return [
            'outlet_id' => ['required', 'integer', 'exists:outlets,id'],
            'code' => [
                'required',
                Rule::in(['qris', 'pay_at_cashier']),
                Rule::unique('ot_payment_methods', 'code')->where('outlet_id', $this->integer('outlet_id')),
            ],
            'label' => ['required', 'string', 'max:100'],
            'payment_id' => ['prohibited'],
            'category_payment_id' => ['prohibited'],
            'nama_tipe_pembayaran' => ['prohibited'],
            'qris_expiry_minutes' => $code === 'qris' ? ['required', 'integer', 'min:1'] : ['nullable', Rule::in([null])],
            'payment_due_minutes' => $code === 'pay_at_cashier' ? ['required', 'integer', 'min:1'] : ['nullable', Rule::in([null])],
            'enabled' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('code') === 'pay_at_cashier'
                && $this->boolean('enabled')
                && ! config('order-table.pay_at_cashier_enabled')) {
                $validator->errors()->add('enabled', 'Bayar di kasir belum diizinkan untuk diaktifkan pada environment ini.');
            }
        });
    }
}

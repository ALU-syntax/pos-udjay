<?php

namespace App\Http\Requests\OrderTable;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDiningTableRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'name' => trim((string) $this->input('name')),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create order-table/dining-tables') === true
            && in_array($this->integer('outlet_id'), $this->user()->outletIds(), true);
    }

    public function rules(): array
    {
        return [
            'outlet_id' => ['required', 'integer', 'exists:outlets,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('ot_dining_tables', 'code')->where('outlet_id', $this->integer('outlet_id')),
            ],
            'name' => ['required', 'string', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'qr_active' => ['sometimes', 'boolean'],
            'qr_token' => ['prohibited'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->filled('code') ? trim((string) $this->input('code')) : null,
            'parent_id' => $this->filled('parent_id') ? $this->input('parent_id') : null,
            'outlet_id' => $this->filled('outlet_id') ? $this->input('outlet_id') : null,
            'brand_id' => $this->filled('brand_id') ? $this->input('brand_id') : null,
        ]);
    }

    public function rules(): array
    {
        $inventoryId = $this->route('inventory')?->id;

        return [
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'exists:inventory,id',
                Rule::notIn([$inventoryId]),
            ],
            'outlet_id' => ['nullable', 'exists:outlets,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'inventory_type_id' => ['required', 'exists:inventory_types,id'],
            'is_active' => ['required', Rule::in([0, 1])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lokasi inventory wajib diisi.',
            'inventory_type_id.required' => 'Tipe lokasi wajib dipilih.',
            'parent_id.not_in' => 'Parent lokasi tidak boleh menunjuk dirinya sendiri.',
            'is_active.required' => 'Status lokasi wajib dipilih.',
        ];
    }
}

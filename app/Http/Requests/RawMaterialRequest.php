<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RawMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->filled('code') ? trim($this->input('code')) : null,
            'raw_material_category_id' => $this->filled('raw_material_category_id') ? $this->input('raw_material_category_id') : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('raw_materials', 'code')->ignore($this->route('rawMaterial')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'raw_material_category_id' => ['nullable', 'exists:raw_material_categories,id'],
            'base_unit_id' => ['required', 'exists:satuans,id'],
            'storage_type_id' => ['required', 'exists:raw_storage_types,id'],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama bahan baku wajib diisi.',
            'code.unique' => 'Kode bahan baku sudah digunakan.',
            'base_unit_id.required' => 'Satuan dasar wajib dipilih.',
            'storage_type_id.required' => 'Tipe penyimpanan wajib dipilih.',
        ];
    }
}

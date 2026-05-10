<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RawMaterialCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('raw_material_categories', 'name')
                    ->ignore($this->route('categoryBahanBaku')),
            ],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori bahan baku wajib diisi.',
            'name.unique' => 'Nama kategori bahan baku sudah digunakan.',
            'is_active.required' => 'Status kategori wajib dipilih.',
        ];
    }
}

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
        $rawMaterial = $this->route('rawMaterial');

        return [
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('raw_materials', 'code')->ignore($this->route('rawMaterial')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'raw_material_category_id' => ['nullable', $this->activeOrCurrent('raw_material_categories', $rawMaterial?->raw_material_category_id)],
            'base_unit_id' => ['required', $this->activeOrCurrent('satuans', $rawMaterial?->base_unit_id)],
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
            'raw_material_category_id.exists' => 'Kategori tidak ditemukan atau sudah nonaktif.',
            'base_unit_id.required' => 'Satuan dasar wajib dipilih.',
            'base_unit_id.exists' => 'Satuan dasar tidak ditemukan atau sudah nonaktif.',
            'storage_type_id.required' => 'Tipe penyimpanan wajib dipilih.',
        ];
    }

    /** Hanya pilihan aktif, kecuali nilai yang sudah tersimpan agar edit tidak memaksa ganti. */
    private function activeOrCurrent(string $table, ?int $currentId)
    {
        return Rule::exists($table, 'id')->where(fn ($query) => $query->where('is_active', 1)
            ->when($currentId, fn ($query) => $query->orWhere('id', $currentId)));
    }
}

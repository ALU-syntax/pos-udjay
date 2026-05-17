<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryStockBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'raw_material_id' => $this->filled('raw_material_id') ? $this->input('raw_material_id') : null,
            'qty_available' => $this->filled('qty_available') ? $this->input('qty_available') : 0,
            'qty_reserved' => $this->filled('qty_reserved') ? $this->input('qty_reserved') : 0,
        ]);
    }

    public function rules(): array
    {
        $inventoryLocation = $this->route('inventoryLocation');
        $stockBalance = $this->route('stockBalance');

        return [
            'raw_material_id' => [
                'required',
                Rule::exists('raw_materials', 'id')->whereNull('deleted_at'),
                Rule::unique('inventory_raw_material_stock_balances', 'raw_material_id')
                    ->ignore($stockBalance?->id)
                    ->where(fn ($query) => $query->where('inventory_location_id', $inventoryLocation?->id)),
            ],
            'qty_available' => ['required', 'numeric', 'min:0'],
            'qty_reserved' => ['required', 'numeric', 'min:0', 'lte:qty_available'],
        ];
    }

    public function messages(): array
    {
        return [
            'raw_material_id.required' => 'Bahan baku wajib dipilih.',
            'raw_material_id.unique' => 'Bahan baku ini sudah ada di inventory tersebut.',
            'qty_reserved.lte' => 'Qty reserved tidak boleh lebih besar dari qty available.',
        ];
    }
}

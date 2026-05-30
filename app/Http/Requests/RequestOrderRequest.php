<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->map(function ($item) {
                if (!isset($item['requested_qty']) && isset($item['qty_requested'])) {
                    $item['requested_qty'] = $item['qty_requested'];
                }

                if (!isset($item['requested_satuan_id']) && isset($item['unit_id'])) {
                    $item['requested_satuan_id'] = $item['unit_id'];
                }

                return $item;
            })
            ->filter(function ($item) {
                return filled($item['raw_material_id'] ?? null)
                    || filled($item['requested_qty'] ?? null)
                    || filled($item['requested_satuan_id'] ?? null)
                    || filled($item['notes'] ?? null);
            })
            ->values()
            ->all();

        $this->merge([
            'request_number' => $this->filled('request_number') ? trim((string) $this->input('request_number')) : null,
            'fulfillment_inventory_id' => $this->filled('fulfillment_inventory_id') ? $this->input('fulfillment_inventory_id') : null,
            'needed_at' => $this->filled('needed_at') ? $this->input('needed_at') : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
            'items' => $items,
        ]);
    }

    public function rules(): array
    {
        $requestOrder = $this->route('requestOrder');

        return [
            'request_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('raw_material_requests', 'request_number')->ignore($requestOrder?->id),
            ],
            'requester_inventory_id' => ['required', 'exists:inventory,id'],
            'fulfillment_inventory_id' => ['nullable', 'exists:inventory,id'],
            'needed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.raw_material_id' => ['required', 'distinct', 'exists:raw_materials,id'],
            'items.*.requested_qty' => ['required', 'numeric', 'gt:0'],
            'items.*.requested_satuan_id' => ['required', 'exists:satuans,id'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'request_number.unique' => 'Nomor request order sudah digunakan.',
            'requester_inventory_id.required' => 'Lokasi pemohon wajib dipilih.',
            'requester_inventory_id.exists' => 'Lokasi pemohon tidak valid.',
            'fulfillment_inventory_id.exists' => 'Inventory pemenuhan tidak valid.',
            'items.required' => 'Minimal satu bahan baku wajib ditambahkan.',
            'items.min' => 'Minimal satu bahan baku wajib ditambahkan.',
            'items.*.raw_material_id.required' => 'Bahan baku pada item wajib dipilih.',
            'items.*.raw_material_id.distinct' => 'Bahan baku tidak boleh dipilih lebih dari satu kali.',
            'items.*.requested_qty.required' => 'Qty request pada item wajib diisi.',
            'items.*.requested_qty.gt' => 'Qty request harus lebih dari 0.',
            'items.*.requested_satuan_id.required' => 'Satuan pada item wajib dipilih.',
        ];
    }
}

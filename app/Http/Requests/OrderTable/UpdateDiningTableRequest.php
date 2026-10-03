<?php

namespace App\Http\Requests\OrderTable;

use App\Models\OrderTable\DiningTable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDiningTableRequest extends FormRequest
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
        $table = $this->routeTable();
        $outletId = $this->integer('outlet_id') ?: (int) $table?->outlet_id;

        return $this->user()?->can('update order-table/dining-tables') === true
            && in_array($outletId, $this->user()->outletIds(), true)
            && ($table === null || in_array((int) $table->outlet_id, $this->user()->outletIds(), true));
    }

    public function rules(): array
    {
        $table = $this->routeTable();
        $tableId = $table?->getKey() ?? (int) ($this->route('diningTable') ?? $this->route('dining_table') ?? $this->route('table'));

        return [
            'outlet_id' => ['required', 'integer', 'exists:outlets,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('ot_dining_tables', 'code')
                    ->where('outlet_id', $this->integer('outlet_id'))
                    ->ignore($tableId),
            ],
            'name' => ['required', 'string', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'qr_active' => ['prohibited'],
            'qr_token' => ['prohibited'],
        ];
    }

    private function routeTable(): ?DiningTable
    {
        $value = $this->route('diningTable') ?? $this->route('dining_table') ?? $this->route('table');

        return $value instanceof DiningTable ? $value : null;
    }
}

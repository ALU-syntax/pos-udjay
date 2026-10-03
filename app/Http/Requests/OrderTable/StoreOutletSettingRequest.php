<?php

namespace App\Http\Requests\OrderTable;

class StoreOutletSettingRequest extends UpdateOutletSettingRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create order-table/outlet-settings') === true
            && in_array($this->integer('outlet_id'), $this->user()->outletIds(), true);
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'outlet_id' => ['required', 'integer', 'exists:outlets,id', 'unique:ot_outlet_settings,outlet_id'],
            'forced_close' => ['required', 'boolean'],
        ]);
    }
}

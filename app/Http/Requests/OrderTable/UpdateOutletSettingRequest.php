<?php

namespace App\Http\Requests\OrderTable;

use App\Models\OrderTable\OutletSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOutletSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $outletId = $this->integer('outlet_id') ?: $this->routeOutletId();

        return $this->user()?->can('update order-table/outlet-settings') === true
            && in_array($outletId, $this->user()->outletIds(), true);
    }

    public function rules(): array
    {
        return [
            'outlet_id' => ['sometimes', 'integer', 'exists:outlets,id'],
            'order_enabled' => ['required', 'boolean'],
            'stock_mode' => ['required', Rule::in(['off', 'status_only', 'strict'])],
            'open_time' => ['nullable', 'date_format:H:i', 'required_with:close_time'],
            'close_time' => ['nullable', 'date_format:H:i', 'required_with:open_time'],
            'forced_close' => ['prohibited'],
            'service_fee_pct' => ['nullable', 'numeric', 'between:0,100'],
            'auto_preparing_delay_seconds' => ['required', 'integer', 'min:0'],
            'session_close_time' => ['required', 'date_format:H:i'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'geofence_radius_m' => ['required', 'integer', 'between:50,2000'],
        ];
    }

    private function routeOutletId(): int
    {
        $value = $this->route('outletSetting') ?? $this->route('outlet_setting') ?? $this->route('setting') ?? $this->route('outlet');

        if ($value instanceof OutletSetting) {
            return (int) $value->outlet_id;
        }

        return (int) OutletSetting::query()->whereKey($value)->value('outlet_id');
    }
}

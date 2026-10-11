<?php

namespace App\Http\Requests\OrderTable;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderTablePublicUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update order-table/outlet-settings') === true;
    }

    public function rules(): array
    {
        return [
            'public_url' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'public_url' => 'base URL',
        ];
    }
}

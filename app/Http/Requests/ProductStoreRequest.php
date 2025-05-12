<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 'name' => ['required', Rule::unique('products')->ignore($this->product)],
            'name' => 'required',
            'category_id' => 'required',
            'photo' => 'nullable|image',
            'harga_modal' => 'required',
            'nama_varian' => 'nullable|array',
            'harga_jual' => 'required|array',
            'stock' => 'required|array',
            'status' => 'required',
            'outlet_id' => 'required',
            'id_variant' => 'nullable|array',
            'description' => 'nullable',
            'exclude_tax' => 'nullable',
        ];
    }
}

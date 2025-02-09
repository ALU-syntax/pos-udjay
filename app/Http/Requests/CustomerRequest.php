<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
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
            'name' => 'required',
            'umur' => 'required',
            'telfon' => 'required|string|max:20|regex:/^\+?[0-9\-]+$/|unique:customers,telfon',
            'email' => 'required',
            'tanggal_lahir' => 'required',
            'domisili' => 'required',
            'gender' => 'required',
            'community_id' => 'nullable',
            'referral_id' => 'nullable',
        ];
    }
}

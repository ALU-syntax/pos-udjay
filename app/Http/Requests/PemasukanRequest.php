<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PemasukanRequest extends FormRequest
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
            'kategori_pemasukan_id' => 'required',
            'jumlah' => 'required',
            'photo' => [
                'required',
                'max:1024', // ukuran maksimal dalam kilobyte  
                'mimes:jpg,png,jpeg', // ekstensi file yang diperbolehkan  
            ],
            'outlet_id' => 'required',
            'customer_id' => 'nullable',
            'tanggal' => 'nullable',
            'catatan' => 'nullable',
        ];
    }

    public function messages()
    {
        return [
            'photo.max' => ':attribute Ukuran terlalu besar!',
            'photo.mimes' => ':attribute harus JPG, PNG, atau JPEG!',
        ];
    }
}

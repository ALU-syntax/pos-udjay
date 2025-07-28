<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NoteReceiptSchedulingRequest extends FormRequest
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
     public function rules(){

        $rules = [
            'name' => 'required|string|max:255',
            'message' => 'required|string',
            'start_hour' => 'required|date_format:H:i',
            'end_hour' => 'required|date_format:H:i|after_or_equal:start_hour',
            'status' => 'boolean',
        ];

        if ($this->isMethod('post')) {
            $rules['outlet_id'] = 'required';
        }

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['outlet_id'] = 'sometimes';
        }
        return $rules;
    }


    public function messages()
    {
        return [
            'name.required' => 'Nama nota wajib diisi.',
            'start_hour.required' => 'Waktu mulai wajib diisi.',
            'start_hour.date_format' => 'Format waktu mulai tidak valid (format HH:mm).',
            'end_hour.required' => 'Waktu selesai wajib diisi.',
            'end_hour.date_format' => 'Format waktu selesai tidak valid (format HH:mm).',
            'end_hour.after_or_equal' => 'Waktu selesai harus setelah atau sama dengan waktu mulai.',
            'outlet_id.required' => 'Outlet wajib dipilih.',
            'status.required' => 'Status harus dipilih.',
            'status.boolean' => 'Status harus bernilai aktif atau tidak aktif.',
        ];
    }
}

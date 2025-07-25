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
        return [
            'name' => 'required|string|max:255',
            'message' => 'required|string',
            'start_hour' => 'required|date_format:H:i',
            'end_hour' => 'required|date_format:H:i|after_or_equal:start_hour',
            'outlet_id' => 'required|array',
            'outlet_id.*' => 'integer|exists:outlets,id',
            'status' => 'boolean',
        ];
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
            'outlet_id.array' => 'Format data outlet tidak valid.',
            'outlet_id.*.exists' => 'Outlet yang dipilih tidak ditemukan.',
            'status.required' => 'Status harus dipilih.',
            'status.boolean' => 'Status harus bernilai aktif atau tidak aktif.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        $emailRules = ['required', 'string', 'email', 'max:255'];

        // Email dipakai untuk login back office, jadi tidak boleh sama dengan user aktif lain.
        // Hanya dicek saat email diganti agar data lama yang terlanjur kembar tidak mengunci form.
        if (strtolower((string) $this->input('email')) !== strtolower((string) $user->email)) {
            $emailRules[] = Rule::unique('users', 'email')->ignore($user->id)->where('deleted', 0);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => $emailRules,
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'max' => ':Attribute maksimal :max karakter.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah dipakai user lain.',
            'username.unique' => 'Username sudah terdaftar.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'username' => 'username',
            'email' => 'email',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        // Ambil ID dari route model binding: Route::resource('customers', ...)
        // Akan null saat store, terisi saat update
        $customerId = $this->route('customer')?->id;

        // Jika pakai soft delete di tabel customers, Anda bisa tambahkan ->whereNull('deleted_at')
        $uniqueTelfon = Rule::unique('customers', 'telfon')
            ->ignore($customerId); // abaikan baris yg sedang diedit


        $uniqueEmail = Rule::unique('customers', 'email')
            ->ignore($customerId);

        // Jika ingin field tidak wajib saat PATCH (update sebagian),
        // pakai 'sometimes' di method PATCH. Kalau mau tetap wajib, biarkan 'required'.
        $required = $this->isMethod('PATCH') ? 'sometimes' : 'required';

        return [
            'name'           => 'required',
            'umur'           => 'required',
            'telfon'         => [$required, 'string', 'max:20', 'regex:/^\+?[0-9\-]+$/', $uniqueTelfon],
            'email'          => [
                // kalau opsional:
                // 'nullable',
                'required',
                'string',
                'max:254',
                'email:rfc',      // atau 'email:rfc,dns' jika perlu cek DNS
                $uniqueEmail,
            ],
            'tanggal_lahir'  => 'required',
            'domisili'       => 'required',
            'gender'         => 'required',
            'community_id'   => 'nullable',
            'referral_id'    => 'nullable',
        ];
    }
}

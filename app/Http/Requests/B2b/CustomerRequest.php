<?php

namespace App\Http\Requests\B2b;

use App\Models\B2b\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can($this->route('customer') ? 'update b2b/customers' : 'create b2b/customers');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'npwp' => $this->filled('npwp') ? trim((string) $this->input('npwp')) : null,
            'email' => $this->filled('email') ? strtolower(trim((string) $this->input('email'))) : null,
            'is_active' => $this->boolean('is_active', true),
            'reminder_enabled' => $this->boolean('reminder_enabled'),
            'reminder_overdue_daily' => $this->boolean('reminder_overdue_daily'),
            'reminder_channels' => array_values(array_unique((array) $this->input('reminder_channels', []))),
            'reminder_days' => array_values(array_unique(array_map('intval', (array) $this->input('reminder_days', [])))),
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(Customer::TYPES))],
            'name' => ['required', 'string', 'max:255'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'is_active' => ['boolean'],
            'reminder_enabled' => ['boolean'],
            'reminder_channels' => ['array'],
            'reminder_channels.*' => [Rule::in(array_keys(Customer::REMINDER_CHANNELS))],
            'reminder_days' => ['array'],
            'reminder_days.*' => ['integer', Rule::in(array_keys(Customer::REMINDER_DAYS))],
            'reminder_overdue_daily' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** Reminder yang dinyalakan harus bisa benar-benar terkirim: ada channel, jadwal, dan tujuan pengiriman. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty() || ! $this->boolean('reminder_enabled')) {
                return;
            }

            $channels = (array) $this->input('reminder_channels', []);
            if ($channels === []) {
                $validator->errors()->add('reminder_channels', 'Pilih minimal satu channel reminder.');
            }
            if ((array) $this->input('reminder_days', []) === [] && ! $this->boolean('reminder_overdue_daily')) {
                $validator->errors()->add('reminder_days', 'Pilih minimal satu jadwal reminder.');
            }

            // Reminder dikirim ke email/telepon customer, jadi tujuan untuk channel yang dipilih harus terisi.
            if (in_array('email', $channels, true) && blank($this->input('email'))) {
                $validator->errors()->add('email', 'Channel reminder email dipilih, jadi email customer wajib diisi.');
            }
            if (in_array('whatsapp', $channels, true) && blank($this->input('phone'))) {
                $validator->errors()->add('phone', 'Channel reminder WhatsApp dipilih, jadi nomor telepon/WA customer wajib diisi.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'type' => 'jenis customer',
            'name' => 'nama customer',
            'contact_name' => 'nama PIC',
            'address' => 'alamat',
            'postal_code' => 'kode pos',
        ];
    }
}

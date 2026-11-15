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
            'contacts' => array_values((array) $this->input('contacts', [])),
            'addresses' => array_values((array) $this->input('addresses', [])),
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
            'is_active' => ['boolean'],
            'reminder_enabled' => ['boolean'],
            'reminder_channels' => ['array'],
            'reminder_channels.*' => [Rule::in(array_keys(Customer::REMINDER_CHANNELS))],
            'reminder_days' => ['array'],
            'reminder_days.*' => ['integer', Rule::in(array_keys(Customer::REMINDER_DAYS))],
            'reminder_overdue_daily' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'contacts' => ['array', 'max:20'],
            'contacts.*.id' => ['nullable', 'integer'],
            'contacts.*.name' => ['required', 'string', 'max:255'],
            'contacts.*.phone' => ['nullable', 'string', 'max:30'],
            'contacts.*.email' => ['nullable', 'email', 'max:255'],
            'contacts.*.position' => ['nullable', 'string', 'max:100'],
            'contacts.*.is_primary' => ['nullable', 'boolean'],
            'contacts.*.receive_reminder' => ['nullable', 'boolean'],

            'addresses' => ['array', 'max:20'],
            'addresses.*.id' => ['nullable', 'integer'],
            'addresses.*.label' => ['required', 'string', 'max:100'],
            'addresses.*.recipient_name' => ['nullable', 'string', 'max:255'],
            'addresses.*.phone' => ['nullable', 'string', 'max:30'],
            'addresses.*.address' => ['required', 'string', 'max:1000'],
            'addresses.*.city' => ['nullable', 'string', 'max:100'],
            'addresses.*.province' => ['nullable', 'string', 'max:100'],
            'addresses.*.postal_code' => ['nullable', 'string', 'max:10'],
            'addresses.*.is_default_billing' => ['nullable', 'boolean'],
            'addresses.*.is_default_shipping' => ['nullable', 'boolean'],
        ];
    }

    /** Reminder yang dinyalakan harus bisa benar-benar terkirim: ada channel, jadwal, dan penerima yang cocok. */
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

            $recipients = collect((array) $this->input('contacts', []))
                ->filter(fn ($contact) => filter_var($contact['receive_reminder'] ?? false, FILTER_VALIDATE_BOOLEAN));
            if ($recipients->isEmpty()) {
                $validator->errors()->add('reminder_recipients', 'Centang "Terima reminder" pada minimal satu kontak.');

                return;
            }
            if (in_array('email', $channels, true) && $recipients->every(fn ($contact) => blank($contact['email'] ?? null))) {
                $validator->errors()->add('reminder_recipients', 'Channel email dipilih, tapi tidak ada kontak penerima reminder yang punya email.');
            }
            if (in_array('whatsapp', $channels, true) && $recipients->every(fn ($contact) => blank($contact['phone'] ?? null))) {
                $validator->errors()->add('reminder_recipients', 'Channel WhatsApp dipilih, tapi tidak ada kontak penerima reminder yang punya nomor telepon/WA.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'type' => 'jenis customer',
            'name' => 'nama customer',
            'contacts.*.name' => 'nama kontak',
            'contacts.*.email' => 'email kontak',
            'addresses.*.label' => 'label alamat',
            'addresses.*.address' => 'alamat',
        ];
    }
}

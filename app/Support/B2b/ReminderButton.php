<?php

namespace App\Support\B2b;

use App\Models\B2b\Invoice;
use App\Models\B2b\ReminderLog;
use App\Services\B2b\B2bSettings;
use App\Services\B2b\ReminderService;

/**
 * Atribut data-* untuk tombol "Kirim reminder" (dialog pilih channel di layouts.b2b.reminders.partials.send-dialog).
 * Dipakai di detail pesanan dan daftar invoice supaya isinya sama.
 */
final class ReminderButton
{
    public static function attributes(Invoice $invoice, ?string $table = null): string
    {
        $customer = $invoice->customer;
        $reminders = app(ReminderService::class);
        $settings = app(B2bSettings::class);
        $email = trim((string) $customer?->email);
        $phone = Phone::toWhatsApp($customer?->phone);
        $channels = [];
        foreach (ReminderLog::CHANNEL_LABELS as $channel => $label) {
            $contact = $channel === ReminderLog::CHANNEL_EMAIL ? (filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null) : ($phone ? '+'.$phone : null);
            $channels[] = [
                'value' => $channel,
                'label' => $label,
                'contact' => $contact,
                'blocked' => match (true) {
                    ! $reminders->supports($channel) => 'Pengirim '.$label.' belum dipasang',
                    ! $settings->reminderChannelEnabled($channel) => 'Dihentikan lewat saklar darurat',
                    $contact === null => ($channel === ReminderLog::CHANNEL_EMAIL ? 'Email' : 'Nomor WhatsApp').' customer kosong / tidak valid',
                    default => null,
                },
                // Default mengikuti channel reminder customer; tanpa pengaturan, email.
                'checked' => in_array($channel, (array) ($customer?->reminder_channels ?: [ReminderLog::CHANNEL_EMAIL]), true),
            ];
        }

        $data = [
            'send-reminder' => '',
            'url' => route('b2b/reminders/send-invoice', $invoice->id),
            'number' => $invoice->invoice_number,
            'channels' => json_encode($channels),
            'table' => $table,
        ];

        return collect($data)->filter(fn ($value) => $value !== null)->map(fn ($value, $key) => 'data-'.$key.'="'.e((string) $value).'"')->implode(' ');
    }
}

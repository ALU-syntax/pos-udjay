<?php

namespace App\Support\B2b;

use App\Models\B2b\Invoice;
use App\Models\B2b\ReminderLog;
use App\Services\B2b\B2bSettings;
use App\Services\B2b\ReminderService;

/**
 * Tombol "Kirim ke customer" / "Kirim kwitansi" (dialog pilih jenis + channel di layouts.b2b.reminders.partials.send-dialog).
 * Dipakai di detail pesanan dan daftar invoice supaya isinya sama. Tampilkan hanya kalau Invoice::sendableKinds() tidak kosong.
 */
final class ReminderButton
{
    /** Keterangan per jenis kiriman di dialog. */
    private const KIND_HINTS = [
        ReminderLog::KIND_INVOICE => 'Invoice pesanan beserta PDF invoice',
        ReminderLog::KIND_REMINDER => 'Pengingat jatuh tempo beserta PDF invoice',
        ReminderLog::KIND_RECEIPT => 'Kwitansi pembayaran beserta PDF kwitansi',
    ];

    /** Label tombol: kwitansi untuk invoice lunas, selain itu "Kirim ke customer". */
    public static function label(Invoice $invoice): string
    {
        return $invoice->sendableKinds() === [ReminderLog::KIND_RECEIPT] ? 'Kirim kwitansi' : 'Kirim ke customer';
    }

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
                // Default reminder mengikuti channel reminder customer (tanpa pengaturan: email).
                // Invoice/kwitansi: semua channel yang bisa dipakai (diatur di dialog).
                'reminder' => in_array($channel, (array) ($customer?->reminder_channels ?: [ReminderLog::CHANNEL_EMAIL]), true),
            ];
        }
        $kinds = array_map(fn (string $kind) => [
            'value' => $kind,
            'label' => ReminderLog::KIND_LABELS[$kind],
            'hint' => self::KIND_HINTS[$kind],
        ], $invoice->sendableKinds());

        $data = [
            'send-reminder' => '',
            'url' => route('b2b/reminders/send-invoice', $invoice->id),
            'number' => $invoice->invoice_number,
            'kinds' => json_encode($kinds),
            'channels' => json_encode($channels),
            'table' => $table,
        ];

        return collect($data)->filter(fn ($value) => $value !== null)->map(fn ($value, $key) => 'data-'.$key.'="'.e((string) $value).'"')->implode(' ');
    }
}

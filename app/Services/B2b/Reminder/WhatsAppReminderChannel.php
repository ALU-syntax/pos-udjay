<?php

namespace App\Services\B2b\Reminder;

use App\Models\B2b\Customer;
use App\Models\B2b\ReminderLog;
use App\Services\B2b\B2bSettings;
use App\Services\B2b\InvoicePdfService;
use App\Support\B2b\InvoiceDocument;
use Illuminate\Support\Collection;

/**
 * Reminder lewat WhatsApp (gateway Baileys): satu pesan teks ringkasan, lalu satu pesan PDF per invoice.
 * Kalau gagal sebelum ada pesan terkirim, job boleh mencoba ulang. Kalau sebagian sudah terkirim, kiriman dihentikan
 * (ReminderSendException) supaya customer tidak menerima pesan dobel; staf bisa kirim ulang manual.
 */
class WhatsAppReminderChannel implements ReminderChannel
{
    public function __construct(
        private readonly WhatsAppGateway $gateway,
        private readonly B2bSettings $settings,
        private readonly InvoicePdfService $pdf,
    ) {}

    public function send(ReminderLog $log, Customer $customer, Collection $items, callable $beforeSend): ?string
    {
        $profile = $this->settings->invoiceProfile();
        $text = $this->message($customer, $items, $profile);
        $documents = $items->map(fn (array $item) => [
            'invoice' => $item['invoice'],
            'filename' => InvoiceDocument::pdfFilename($item['invoice']),
        ])->values();

        $beforeSend([
            'subject' => null,
            'body' => $text,
            'attachments' => $documents->pluck('filename')->all(),
        ]);

        $total = 1 + $documents->count();
        $sent = 0;
        $firstId = null;
        try {
            $firstId = $this->gateway->send($log->recipient, $text);
            $sent++;
            foreach ($documents as $document) {
                $this->gateway->send($log->recipient, 'Invoice '.$document['invoice']->invoice_number, [
                    'filename' => $document['filename'],
                    'content' => $this->pdf->output($document['invoice']),
                ]);
                $sent++;
            }
        } catch (WhatsAppGatewayException $e) {
            if ($sent > 0) {
                throw ReminderSendException::permanent("Terkirim sebagian ({$sent} dari {$total} pesan): {$e->getMessage()} Kirim ulang manual kalau perlu.");
            }
            if ($e->isPermanent()) {
                throw ReminderSendException::permanent($e->getMessage());
            }

            throw $e;
        }

        return $firstId;
    }

    /** Teks WhatsApp (format *tebal* WhatsApp), isi setara email reminder. */
    public function message(Customer $customer, Collection $items, array $profile): string
    {
        $rupiah = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
        $when = fn (int $offset) => match (true) {
            $offset === 0 => 'jatuh tempo hari ini',
            $offset < 0 => abs($offset).' hari lagi',
            default => 'lewat '.$offset.' hari',
        };
        $overdue = $items->contains(fn (array $item) => $item['offset'] > 0);
        $greeting = $customer->contact_name ? $customer->contact_name.' ('.$customer->name.')' : $customer->name;

        $lines = ["Yth. {$greeting},", ''];
        $lines[] = $overdue
            ? "Tagihan dari *{$profile['brand_name']}* berikut telah melewati jatuh tempo. Mohon segera melakukan pembayaran."
            : "Pengingat tagihan dari *{$profile['brand_name']}*:";
        $lines[] = '';
        foreach ($items->values() as $index => $item) {
            $invoice = $item['invoice'];
            $lines[] = ($index + 1).'. *'.$invoice->invoice_number.'*';
            $lines[] = '   Jatuh tempo '.$invoice->due_date->translatedFormat('d M Y').' ('.$when((int) $item['offset']).')';
            $lines[] = '   Sisa tagihan '.$rupiah($invoice->outstandingAmount());
        }
        $lines[] = '';
        $lines[] = '*Total sisa tagihan: '.$rupiah($items->sum(fn (array $item) => $item['invoice']->outstandingAmount())).'*';
        $lines[] = '';
        $lines[] = "Pembayaran ke {$profile['bank_name']} {$profile['bank_account_number']} a.n. {$profile['bank_account_name']}. Mohon cantumkan nomor invoice pada berita transfer.";
        $lines[] = '';
        $lines[] = 'Invoice terlampir dalam PDF. Abaikan pesan ini apabila pembayaran sudah dilakukan. Terima kasih.';

        return implode("\n", $lines);
    }
}

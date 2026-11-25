<?php

namespace App\Services\B2b\Reminder;

use App\Models\B2b\Customer;
use App\Models\B2b\Invoice;
use App\Models\B2b\ReminderLog;
use App\Services\B2b\B2bSettings;
use App\Services\B2b\InvoicePdfService;
use App\Support\B2b\InvoiceDocument;
use Illuminate\Support\Collection;

/**
 * Kiriman lewat WhatsApp (gateway Baileys): satu pesan teks, lalu satu pesan PDF per invoice
 * (PDF invoice untuk reminder/invoice, PDF kwitansi untuk kwitansi).
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
        $receipt = $log->kind === ReminderLog::KIND_RECEIPT;
        $text = match ($log->kind) {
            ReminderLog::KIND_INVOICE => $this->invoiceMessage($customer, $items->first()['invoice'], $profile),
            ReminderLog::KIND_RECEIPT => $this->receiptMessage($customer, $items->first()['invoice'], $profile),
            default => $this->message($customer, $items, $profile),
        };
        $documents = $items->map(fn (array $item) => [
            'invoice' => $item['invoice'],
            'filename' => $receipt ? InvoiceDocument::receiptPdfFilename($item['invoice']) : InvoiceDocument::pdfFilename($item['invoice']),
            'caption' => $receipt ? 'Kwitansi '.InvoiceDocument::receiptNumber($item['invoice']) : 'Invoice '.$item['invoice']->invoice_number,
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
                $this->gateway->send($log->recipient, $document['caption'], [
                    'filename' => $document['filename'],
                    'content' => $receipt ? $this->pdf->receipt($document['invoice']) : $this->pdf->output($document['invoice']),
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

    /** Teks WhatsApp untuk invoice pesanan tempo yang baru dibuat. */
    public function invoiceMessage(Customer $customer, Invoice $invoice, array $profile): string
    {
        $lines = ["Yth. {$this->greeting($customer)},", ''];
        $lines[] = "Terima kasih atas pesanan Anda di *{$profile['brand_name']}*. Berikut invoice pesanan Anda:";
        $lines[] = '';
        $lines[] = 'No. Invoice: *'.$invoice->invoice_number.'*';
        if ($invoice->order?->order_number) {
            $lines[] = 'No. Pesanan: '.$invoice->order->order_number;
        }
        $lines[] = 'Jatuh tempo: '.$invoice->due_date->translatedFormat('d M Y');
        $lines[] = '*'.($invoice->paid_amount > 0 ? 'Sisa tagihan' : 'Total tagihan').': '.$this->rupiah($invoice->outstandingAmount()).'*';
        $lines[] = '';
        $lines[] = "Pembayaran ke {$profile['bank_name']} {$profile['bank_account_number']} a.n. {$profile['bank_account_name']}. Mohon cantumkan nomor invoice pada berita transfer.";
        $lines[] = '';
        $lines[] = 'Invoice terlampir dalam PDF. Terima kasih.';

        return implode("\n", $lines);
    }

    /** Teks WhatsApp untuk kwitansi invoice yang sudah lunas. */
    public function receiptMessage(Customer $customer, Invoice $invoice, array $profile): string
    {
        $methods = $invoice->allocations->pluck('payment')->filter()->where('status', 'posted')->pluck('method_name')->unique()->implode(', ');

        $lines = ["Yth. {$this->greeting($customer)},", ''];
        $lines[] = "Terima kasih. Pembayaran Anda di *{$profile['brand_name']}* telah kami terima.";
        $lines[] = '';
        $lines[] = 'No. Kwitansi: *'.InvoiceDocument::receiptNumber($invoice).'*';
        $lines[] = 'No. Invoice: '.$invoice->invoice_number;
        if ($invoice->order?->order_number) {
            $lines[] = 'No. Pesanan: '.$invoice->order->order_number;
        }
        $lines[] = 'Tanggal bayar: '.($invoice->paid_at ?? $invoice->issue_date)->translatedFormat('d M Y');
        if ($methods !== '') {
            $lines[] = 'Metode: '.$methods;
        }
        $lines[] = '*Jumlah dibayar: '.$this->rupiah($invoice->paid_amount).'*';
        $lines[] = '';
        $lines[] = 'Kwitansi terlampir dalam PDF. Terima kasih.';

        return implode("\n", $lines);
    }

    private function greeting(Customer $customer): string
    {
        return $customer->contact_name ? $customer->contact_name.' ('.$customer->name.')' : $customer->name;
    }

    private function rupiah($value): string
    {
        return 'Rp '.number_format((int) $value, 0, ',', '.');
    }
}

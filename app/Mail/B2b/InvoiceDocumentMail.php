<?php

namespace App\Mail\B2b;

use App\Models\B2b\Customer;
use App\Models\B2b\Invoice;
use App\Models\B2b\ReminderLog;
use App\Services\B2b\InvoicePdfService;
use App\Support\B2b\InvoiceDocument;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Email dokumen satu invoice ke customer: invoice pesanan tempo (lampiran PDF invoice) atau kwitansi invoice lunas
 * (lampiran PDF kwitansi). Dikirim langsung dari SendReminderJob (sudah di queue), jadi tidak di-queue lagi.
 */
class InvoiceDocumentMail extends Mailable
{
    /** PDF dibuat sekali per email; lihat attachments(). */
    private ?string $pdfContent = null;

    /**
     * @param  string  $kind  ReminderLog::KIND_INVOICE atau ReminderLog::KIND_RECEIPT
     * @param  array<string, string>  $profile  B2bSettings::invoiceProfile()
     */
    public function __construct(
        public Customer $customer,
        public Invoice $invoice,
        public string $kind,
        public array $profile,
        private readonly InvoicePdfService $pdf,
    ) {}

    public function isReceipt(): bool
    {
        return $this->kind === ReminderLog::KIND_RECEIPT;
    }

    public function subjectLine(): string
    {
        $topic = $this->isReceipt()
            ? 'Kwitansi pembayaran '.InvoiceDocument::receiptNumber($this->invoice)
            : 'Invoice '.$this->invoice->invoice_number;

        return '['.$this->profile['brand_name'].'] '.$topic;
    }

    public function attachmentName(): string
    {
        return $this->isReceipt()
            ? InvoiceDocument::receiptPdfFilename($this->invoice)
            : InvoiceDocument::pdfFilename($this->invoice);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine(),
            replyTo: filter_var($this->profile['email'], FILTER_VALIDATE_EMAIL) ? [new Address($this->profile['email'], $this->profile['brand_name'])] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'email.b2b-invoice-document', with: [
            'customer' => $this->customer,
            'invoice' => $this->invoice,
            'receipt' => $this->isReceipt(),
            'receiptNumber' => InvoiceDocument::receiptNumber($this->invoice),
            'payments' => $this->invoice->allocations->pluck('payment')->filter()->where('status', 'posted')->unique('id')->values(),
            'profile' => $this->profile,
        ]);
    }

    /**
     * Laravel memanggil attachments() setiap render() dan send(); kanal email me-render dulu untuk log, lalu mengirim.
     * Isi PDF disimpan supaya dibuat sekali dan lampiran yang sama disatukan Laravel (dompdf menghasilkan byte berbeda
     * di tiap pembuatan, jadi tanpa ini PDF terlampir dua kali).
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => $this->pdfContent ??= $this->isReceipt() ? $this->pdf->receipt($this->invoice) : $this->pdf->output($this->invoice),
                $this->attachmentName(),
            )->withMime('application/pdf'),
        ];
    }
}

<?php

namespace App\Mail\B2b;

use App\Models\B2b\Customer;
use App\Services\B2b\InvoicePdfService;
use App\Support\B2b\InvoiceDocument;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;

/**
 * Email reminder gabungan: semua invoice customer yang kena jadwal hari itu, lampiran PDF per invoice.
 * Dikirim langsung dari SendReminderJob (sudah di queue), jadi Mailable ini tidak di-queue lagi.
 */
class InvoiceReminderMail extends Mailable
{
    /** @var array<int, string> PDF per id invoice, dibuat sekali per email; lihat attachments(). */
    private array $pdfContents = [];

    /**
     * @param  Collection<int, array{invoice: \App\Models\B2b\Invoice, offset: int}>  $items
     * @param  array<string, string>  $profile  B2bSettings::invoiceProfile()
     */
    public function __construct(
        public Customer $customer,
        public Collection $items,
        public array $profile,
        private readonly InvoicePdfService $pdf,
    ) {}

    public function subjectLine(): string
    {
        $offsets = $this->items->pluck('offset');
        $topic = match (true) {
            $offsets->contains(fn ($offset) => $offset > 0) => 'Tagihan telah lewat jatuh tempo',
            $offsets->contains(0) => 'Tagihan jatuh tempo hari ini',
            default => 'Pengingat tagihan akan jatuh tempo',
        };

        return '['.$this->profile['brand_name'].'] '.$topic;
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
        return new Content(view: 'email.b2b-invoice-reminder', with: [
            'customer' => $this->customer,
            'items' => $this->items,
            'profile' => $this->profile,
            'outstanding' => $this->items->sum(fn (array $item) => $item['invoice']->outstandingAmount()),
        ]);
    }

    /**
     * Laravel memanggil attachments() setiap render() dan send(); isi PDF disimpan supaya dibuat sekali dan lampiran
     * yang sama disatukan Laravel (lihat InvoiceDocumentMail::attachments()).
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return $this->items->map(fn (array $item) => Attachment::fromData(
            fn () => $this->pdfContents[$item['invoice']->id] ??= $this->pdf->output($item['invoice']),
            InvoiceDocument::pdfFilename($item['invoice']),
        )->withMime('application/pdf'))->values()->all();
    }
}

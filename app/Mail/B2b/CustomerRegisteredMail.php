<?php

namespace App\Mail\B2b;

use App\Models\B2b\Customer;
use App\Support\B2b\Phone;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Email konfirmasi pendaftaran customer B2B lewat form umum (aplikasi membership): data pendaftaran dan nomor admin
 * untuk pemesanan. Dikirim langsung dari SendReminderJob (sudah di queue), jadi tidak di-queue lagi.
 */
class CustomerRegisteredMail extends Mailable
{
    /**
     * @param  array<string, string>  $profile  B2bSettings::invoiceProfile()
     * @param  ?string  $adminPhone  B2bSettings::orderContactPhone()
     */
    public function __construct(
        public Customer $customer,
        public array $profile,
        public ?string $adminPhone,
    ) {}

    public function subjectLine(): string
    {
        return '['.$this->profile['brand_name'].'] Pendaftaran customer berhasil';
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
        $whatsapp = Phone::toWhatsApp($this->adminPhone);

        return new Content(view: 'email.b2b-customer-registered', with: [
            'customer' => $this->customer,
            'profile' => $this->profile,
            'adminPhone' => $this->adminPhone,
            'adminWhatsAppUrl' => $whatsapp ? 'https://wa.me/'.$whatsapp : null,
        ]);
    }
}

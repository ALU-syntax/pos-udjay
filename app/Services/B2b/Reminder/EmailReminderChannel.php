<?php

namespace App\Services\B2b\Reminder;

use App\Mail\B2b\InvoiceDocumentMail;
use App\Mail\B2b\InvoiceReminderMail;
use App\Models\B2b\Customer;
use App\Models\B2b\ReminderLog;
use App\Services\B2b\B2bSettings;
use App\Services\B2b\InvoicePdfService;
use App\Support\B2b\InvoiceDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class EmailReminderChannel implements ReminderChannel
{
    public function __construct(
        private readonly B2bSettings $settings,
        private readonly InvoicePdfService $pdf,
    ) {}

    public function send(ReminderLog $log, Customer $customer, Collection $items, callable $beforeSend): ?string
    {
        $profile = $this->settings->invoiceProfile();

        if ($log->kind === ReminderLog::KIND_REMINDER) {
            $mail = new InvoiceReminderMail($customer, $items, $profile, $this->pdf);
            $attachments = $items->map(fn (array $item) => InvoiceDocument::pdfFilename($item['invoice']))->values()->all();
        } else {
            // Kiriman invoice/kwitansi selalu satu invoice.
            $mail = new InvoiceDocumentMail($customer, $items->first()['invoice'], $log->kind, $profile, $this->pdf);
            $attachments = [$mail->attachmentName()];
        }

        $beforeSend([
            'subject' => $mail->subjectLine(),
            'body' => $mail->render(),
            'attachments' => $attachments,
        ]);

        $sent = Mail::to($log->recipient)->send($mail);

        return $sent?->getMessageId();
    }
}

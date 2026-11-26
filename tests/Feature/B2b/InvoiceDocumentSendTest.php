<?php

namespace Tests\Feature\B2b;

use App\Jobs\B2b\SendReminderJob;
use App\Mail\B2b\InvoiceDocumentMail;
use App\Models\B2b\Customer;
use App\Models\B2b\Invoice;
use App\Models\B2b\Order;
use App\Models\B2b\ReminderLog;
use App\Models\Outlets;
use App\Models\User;
use App\Models\VariantProduct;
use App\Services\B2b\B2bSettings;
use App\Services\B2b\OrderService;
use App\Services\B2b\PaymentService;
use App\Services\B2b\ReminderService;
use App\Support\B2b\InvoiceDocument;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/** Kiriman dokumen invoice (pesanan tempo) dan kwitansi (invoice lunas) lewat jalur kiriman reminder. */
class InvoiceDocumentSendTest extends B2bTestCase
{
    private const GATEWAY = 'http://wa-gateway.test';

    private Outlets $outlet;

    private User $user;

    private VariantProduct $variant;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.b2b_wa_gateway.url' => self::GATEWAY, 'services.b2b_wa_gateway.key' => 'kunci-rahasia-gateway-minimal-32-karakter']);
        $this->outlet = $this->outlet();
        $this->user = $this->userFor($this->outlet);
        $this->variant = $this->variant($this->outlet, 10000, 100);
        // Pengingat customer dimatikan: kiriman dokumen tidak bergantung pada pengaturan reminder.
        $this->customer = $this->customer([
            'contact_name' => 'Bu Rina',
            'email' => 'finance@pelanggan.test',
            'phone' => '0812-3456-7890',
            'reminder_enabled' => false,
        ]);
        $this->travelTo(today()->setTime(10, 0));
    }

    /** Pesanan 30.000: tempo 7 hari, atau bayar langsung tunai. */
    private function invoice(bool $immediate = false): Invoice
    {
        [$cash] = $this->paymentCategory('Cash');

        return app(OrderService::class)->place($this->user, array_merge([
            'outlet_id' => $this->outlet->id,
            'b2b_customer_id' => $this->customer->id,
            'items' => [['variant_id' => $this->variant->id, 'qty' => 3]],
            'delivery_method' => 'pickup',
        ], $immediate
            ? ['payment_type' => Order::PAYMENT_IMMEDIATE, 'payment' => ['category_payment_id' => $cash->id]]
            : ['due_date' => today()->addDays(7)->toDateString()]
        ))->invoice;
    }

    private function queue(Invoice $invoice, string $kind, string $channel = ReminderLog::CHANNEL_EMAIL, string $trigger = ReminderLog::TRIGGER_MANUAL): ReminderLog
    {
        Bus::fake([SendReminderJob::class]);
        $service = app(ReminderService::class);

        return $service->createLog($this->customer, $channel, collect([['invoice' => $invoice, 'offset' => $service->offset($invoice, today())]]),
            today(), $trigger, $trigger === ReminderLog::TRIGGER_MANUAL ? $this->user : null, $kind);
    }

    private function runJob(ReminderLog $log): void
    {
        (new SendReminderJob($log->id))->handle(app(B2bSettings::class));
    }

    public function test_invoice_email_for_credit_order_attaches_invoice_pdf(): void
    {
        Mail::fake();
        $invoice = $this->invoice();
        $log = $this->queue($invoice, ReminderLog::KIND_INVOICE);
        $this->assertSame(ReminderLog::KIND_INVOICE, $log->kind);
        $this->assertNull($log->dedupe_key);

        $this->runJob($log);

        Mail::assertSent(InvoiceDocumentMail::class, fn (InvoiceDocumentMail $mail) => $mail->hasTo('finance@pelanggan.test')
            && ! $mail->isReceipt()
            && $mail->invoice->is($invoice)
            && count($mail->attachments()) === 1
            && $mail->attachmentName() === InvoiceDocument::pdfFilename($invoice));
        $log->refresh();
        $this->assertSame(ReminderLog::STATUS_SENT, $log->status);
        $this->assertStringContainsString('Invoice '.$invoice->invoice_number, $log->subject);
        $this->assertStringContainsString('Bu Rina', $log->body);
        $this->assertStringContainsString('Rp 30.000', $log->body);
        $this->assertStringContainsString($invoice->due_date->translatedFormat('d M Y'), $log->body);
        $this->assertSame([InvoiceDocument::pdfFilename($invoice)], $log->attachments);
    }

    public function test_receipt_email_for_immediate_order_attaches_receipt_pdf(): void
    {
        Mail::fake();
        $invoice = $this->invoice(immediate: true);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);

        $this->runJob($this->queue($invoice, ReminderLog::KIND_RECEIPT));

        $receiptNumber = InvoiceDocument::receiptNumber($invoice);
        Mail::assertSent(InvoiceDocumentMail::class, fn (InvoiceDocumentMail $mail) => $mail->isReceipt()
            && $mail->attachmentName() === InvoiceDocument::receiptPdfFilename($invoice));
        $log = ReminderLog::query()->latest('id')->first();
        $this->assertSame(ReminderLog::STATUS_SENT, $log->status);
        $this->assertStringContainsString('Kwitansi pembayaran '.$receiptNumber, $log->subject);
        $this->assertStringContainsString('telah kami terima', $log->body);
        $this->assertStringContainsString('Cash', $log->body);
        $this->assertStringNotContainsString('Pembayaran dapat ditransfer', $log->body);
    }

    /** Lampiran email yang benar-benar terkirim (mailer array, bukan Mail::fake): nama file PDF per pesan. */
    private function sentAttachmentNames(): array
    {
        return collect(app('mailer')->getSymfonyTransport()->messages())
            ->map(fn ($sent) => collect($sent->getOriginalMessage()->getAttachments())->map(fn ($part) => $part->getFilename())->all())
            ->all();
    }

    public function test_each_pdf_is_attached_once(): void
    {
        config(['mail.default' => 'array']);
        app('mailer')->getSymfonyTransport()->flush();
        $receipt = $this->invoice(immediate: true);
        $credit = $this->invoice();

        $this->runJob($this->queue($receipt, ReminderLog::KIND_RECEIPT));
        $this->runJob($this->queue($credit, ReminderLog::KIND_INVOICE));
        $this->runJob($this->queue($credit, ReminderLog::KIND_REMINDER));

        $this->assertSame([
            [InvoiceDocument::receiptPdfFilename($receipt)],
            [InvoiceDocument::pdfFilename($credit)],
            [InvoiceDocument::pdfFilename($credit)],
        ], $this->sentAttachmentNames());
    }

    public function test_documents_are_rechecked_when_the_job_runs(): void
    {
        Mail::fake();
        [$cash] = $this->paymentCategory('Cash');
        $credit = $this->invoice();
        $invoiceLog = $this->queue($credit, ReminderLog::KIND_INVOICE);
        // Kwitansi untuk invoice yang belum lunas tidak dikirim.
        $receiptLog = $this->queue($this->invoice(), ReminderLog::KIND_RECEIPT);
        app(PaymentService::class)->record($this->user, $credit, ['category_payment_id' => $cash->id, 'amount' => 30000]);

        $this->runJob($invoiceLog);
        $this->runJob($receiptLog);

        Mail::assertNothingSent();
        $this->assertSame(ReminderLog::STATUS_SKIPPED, $invoiceLog->fresh()->status);
        $this->assertSame('Invoice belum lunas atau pesanannya dibatalkan.', $receiptLog->fresh()->error);
    }

    public function test_automatic_document_is_not_bound_to_reminder_send_hours(): void
    {
        Mail::fake();
        $this->travelTo(today()->setTime(20, 0));
        $log = $this->queue($this->invoice(immediate: true), ReminderLog::KIND_RECEIPT, trigger: ReminderLog::TRIGGER_AUTO);
        $this->assertNull($log->dedupe_key);

        $this->runJob($log);

        $this->assertSame(ReminderLog::STATUS_SENT, $log->fresh()->status);
    }

    public function test_receipt_whatsapp_sends_text_then_receipt_pdf(): void
    {
        $invoice = $this->invoice(immediate: true);
        $log = $this->queue($invoice, ReminderLog::KIND_RECEIPT, ReminderLog::CHANNEL_WHATSAPP);
        Http::fake([self::GATEWAY.'/send' => Http::sequence()->push(['id' => 'TEXT1'])->push(['id' => 'DOC1'])]);

        $this->runJob($log);

        $requests = Http::recorded()->map(fn ($pair) => $pair[0])->values();
        $this->assertCount(2, $requests);
        $text = $requests[0]->data()['text'];
        $this->assertStringContainsString('Yth. Bu Rina', $text);
        $this->assertStringContainsString('*'.InvoiceDocument::receiptNumber($invoice).'*', $text);
        $this->assertStringContainsString('*Jumlah dibayar: Rp 30.000*', $text);
        /** @var Request $document */
        $document = $requests[1];
        $this->assertSame('Kwitansi '.InvoiceDocument::receiptNumber($invoice), $document->data()['text']);
        $this->assertSame(InvoiceDocument::receiptPdfFilename($invoice), $document->data()['document']['filename']);
        $this->assertStringStartsWith('%PDF', base64_decode($document->data()['document']['base64']));
        $this->assertSame(ReminderLog::STATUS_SENT, $log->fresh()->status);
    }

    public function test_invoice_whatsapp_text_contains_due_date_and_bank(): void
    {
        $invoice = $this->invoice();
        $log = $this->queue($invoice, ReminderLog::KIND_INVOICE, ReminderLog::CHANNEL_WHATSAPP);
        Http::fake([self::GATEWAY.'/send' => Http::sequence()->push(['id' => 'TEXT1'])->push(['id' => 'DOC1'])]);

        $this->runJob($log);

        $requests = Http::recorded()->map(fn ($pair) => $pair[0])->values();
        $text = $requests[0]->data()['text'];
        $this->assertStringContainsString('*'.$invoice->invoice_number.'*', $text);
        $this->assertStringContainsString('Jatuh tempo: '.$invoice->due_date->translatedFormat('d M Y'), $text);
        $this->assertStringContainsString('*Total tagihan: Rp 30.000*', $text);
        $this->assertSame(InvoiceDocument::pdfFilename($invoice), $requests[1]->data()['document']['filename']);
    }

    public function test_failed_receipt_is_resent_as_receipt(): void
    {
        $invoice = $this->invoice(immediate: true);
        $log = $this->queue($invoice, ReminderLog::KIND_RECEIPT);
        $log->forceFill(['status' => ReminderLog::STATUS_FAILED, 'error' => 'SMTP error'])->save();

        $new = app(ReminderService::class)->resend($log, $this->user);

        $this->assertSame(ReminderLog::KIND_RECEIPT, $new->kind);
        $this->assertSame(ReminderLog::STATUS_QUEUED, $new->status);
        $this->assertSame([$invoice->id], $new->invoices()->pluck('b2b_invoices.id')->all());
        $this->assertSame('Kwitansi', $new->invoiceLabel(0));
    }
}

<?php

namespace App\Http\Controllers\B2b;

use App\DataTables\B2b\ReminderLogDataTable;
use App\Http\Controllers\Controller;
use App\Models\B2b\Customer;
use App\Models\B2b\Invoice;
use App\Models\B2b\ReminderLog;
use App\Services\B2b\ActivityLogger;
use App\Services\B2b\B2bSettings;
use App\Services\B2b\Reminder\WhatsAppGateway;
use App\Services\B2b\Reminder\WhatsAppGatewayException;
use App\Services\B2b\ReminderService;
use App\Support\B2b\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Menu Reminder: aktivitas kiriman (termasuk log email), kirim ulang, dan saklar darurat per channel. */
class ReminderController extends Controller
{
    public function __construct(
        private readonly ReminderService $reminders,
        private readonly B2bSettings $settings,
        private readonly WhatsAppGateway $gateway,
    ) {
        $this->abilities = [];
    }

    public function index(ReminderLogDataTable $dataTable, Request $request)
    {
        $this->authorize('read b2b/reminders');
        $tab = in_array($request->query('tab'), ['customers', 'whatsapp'], true) ? $request->query('tab') : 'activity';

        $today = ReminderLog::query()
            ->whereDate('reminder_date', today())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return $dataTable->render('layouts.b2b.reminders.index', [
            'stats' => collect(ReminderLog::STATUS_LABELS)->map(fn ($label, $status) => (int) ($today[$status] ?? 0)),
            'switches' => collect(B2bSettings::REMINDER_SWITCH_KEYS)->map(fn ($key, $channel) => [
                'label' => ReminderLog::CHANNEL_LABELS[$channel],
                'icon' => $channel === ReminderLog::CHANNEL_WHATSAPP ? 'fab fa-whatsapp' : 'fas fa-envelope',
                'enabled' => $this->settings->reminderChannelEnabled($channel),
                'supported' => $this->reminders->supports($channel),
            ]),
            'sendAt' => B2bSettings::REMINDER_SEND_AT,
            'retryUntil' => B2bSettings::REMINDER_RETRY_UNTIL,
            'tab' => $tab,
            'customers' => $tab === 'customers' ? $this->reminderCustomers() : collect(),
            'whatsappConfigured' => $this->gateway->isConfigured(),
        ]);
    }

    /**
     * Tab Customer: customer dengan reminder aktif, ditambah customer yang reminder-nya mati tapi masih punya tagihan tempo terbuka.
     * Urutan: yang datanya perlu dilengkapi dulu, lalu yang belum diatur, lalu sisanya menurut nama.
     */
    private function reminderCustomers()
    {
        $open = fn ($invoice) => $invoice->whereIn('id', $this->reminders->openCreditInvoices()->select('b2b_invoices.id'));

        return Customer::query()
            ->where(fn ($query) => $query->where('reminder_enabled', true)->orWhereHas('invoices', $open))
            ->withCount(['invoices as open_invoices_count' => $open])
            ->withSum(['invoices as open_outstanding' => $open], DB::raw('total_to_pay - paid_amount'))
            ->addSelect(['last_reminder_id' => ReminderLog::query()->select('id')->whereColumn('b2b_customer_id', 'b2b_customers.id')->latest('id')->limit(1)])
            ->orderBy('name')
            ->get()
            ->each(function (Customer $customer) {
                $customer->setAttribute('issues', $customer->reminderIssues());
                $customer->setAttribute('not_configured', ! $customer->reminder_enabled);
            })
            ->tap(function ($customers) {
                $lastLogs = ReminderLog::query()->whereIn('id', $customers->pluck('last_reminder_id')->filter())->get()->keyBy('id');
                $customers->each(fn (Customer $customer) => $customer->setRelation('lastReminder', $lastLogs[$customer->last_reminder_id] ?? null));
            })
            ->sortBy(fn (Customer $customer) => match (true) {
                $customer->issues !== [] => 0,
                $customer->not_configured => 1,
                default => 2,
            })
            ->values();
    }

    /** Kirim reminder satu invoice sekarang lewat channel yang dipilih (tombol di detail pesanan / daftar invoice). */
    public function sendInvoice(Request $request, int $invoice)
    {
        $this->authorize('send b2b/reminders');
        $validated = $request->validate([
            'channels' => ['nullable', 'array'],
            'channels.*' => [Rule::in(array_keys(ReminderLog::CHANNEL_LABELS))],
        ], [], ['channels' => 'channel']);
        $invoice = Invoice::query()
            ->whereHas('order', fn ($order) => $order->withTrashed()->whereIn('outlet_id', $request->user()->outletIds()))
            ->findOrFail($invoice);

        $logs = $this->reminders->sendNow($invoice, $request->user(), $validated['channels'] ?? [ReminderLog::CHANNEL_EMAIL]);
        $parts = $logs->map(fn (ReminderLog $log) => $log->status === ReminderLog::STATUS_QUEUED
            ? $log->channelLabel().' ke '.$log->recipient.' masuk antrean'
            : $log->channelLabel().' dilewati: '.$log->error);

        return responseSuccess(true, 'Reminder '.$invoice->invoice_number.': '.$parts->implode('; ').'.');
    }

    /** Detail satu kiriman untuk dialog log (isi email ditampilkan di iframe sandbox). */
    public function show(int $log)
    {
        $this->authorize('read b2b/reminders');
        $log = ReminderLog::query()->with(['customer', 'invoices', 'sender'])->findOrFail($log);

        return responseSuccess(false, 'OK', [
            'id' => $log->id,
            'customer' => $log->customer?->name,
            'channel' => $log->channelLabel(),
            'channel_key' => $log->channel,
            'channel_icon' => $log->channelIcon(),
            'recipient' => $log->recipient,
            'trigger' => ReminderLog::TRIGGER_LABELS[$log->trigger] ?? $log->trigger,
            'sender' => $log->sender?->name,
            'status' => $log->statusLabel(),
            'status_variant' => $log->statusVariant(),
            'reminder_date' => $log->reminder_date->translatedFormat('d M Y'),
            'created_at' => $log->created_at->translatedFormat('d M Y H:i'),
            'sent_at' => $log->sent_at?->translatedFormat('d M Y H:i'),
            'attempts' => $log->attempts,
            'subject' => $log->subject,
            'body' => $log->body,
            'attachments' => $log->attachments ?? [],
            'error' => $log->error,
            'invoices' => $log->invoices->sortBy('due_date')->values()->map(fn ($invoice) => [
                'number' => $invoice->invoice_number,
                'due_date' => $invoice->due_date->translatedFormat('d M Y'),
                'offset' => ReminderLog::offsetLabel((int) $invoice->pivot->offset_days),
                'url' => route('b2b/invoices/print', $invoice->id),
            ]),
        ]);
    }

    public function resend(Request $request, int $log)
    {
        $this->authorize('send b2b/reminders');
        $new = $this->reminders->resend(ReminderLog::query()->findOrFail($log), $request->user());

        return responseSuccess(true, $new->status === ReminderLog::STATUS_QUEUED
            ? 'Reminder dikirim ulang dan masuk antrean.'
            : 'Reminder dicatat tapi dilewati: '.$new->error);
    }

    /** Saklar darurat: hentikan / aktifkan lagi semua kiriman satu channel. Menghentikan wajib menyertakan alasan. */
    public function toggle(Request $request, string $channel, string $state, ActivityLogger $logger)
    {
        $this->authorize('toggle b2b/reminders');
        abort_unless(array_key_exists($channel, B2bSettings::REMINDER_SWITCH_KEYS), 404);
        $enabled = $state === 'on';
        $validated = $request->validate(['reason' => [$enabled ? 'nullable' : 'required', 'string', 'max:255']], [], ['reason' => 'alasan']);

        $config = $this->settings->setReminderChannelEnabled($channel, $enabled);
        $logger->log($enabled ? 'reminder.channel_enabled' : 'reminder.channel_disabled', $config, [
            'channel' => $channel,
            'reason' => $validated['reason'] ?? null,
        ], $request->user()->id);

        $label = ReminderLog::CHANNEL_LABELS[$channel];

        return responseSuccess(true, $enabled ? "Kiriman {$label} diaktifkan lagi." : "Semua kiriman {$label} dihentikan sementara.");
    }

    /** Tab WhatsApp: status gateway (dipanggil berkala dari halaman). */
    public function whatsappStatus()
    {
        $this->authorize('read b2b/reminders');

        return $this->gatewayCall(fn () => $this->gateway->status());
    }

    /** Mulai menautkan nomor: gateway membuat QR (atau menyambung lagi memakai sesi tersimpan). */
    public function whatsappConnect(Request $request, ActivityLogger $logger)
    {
        $this->authorize('manage-whatsapp b2b/reminders');

        return $this->gatewayCall(function () use ($request, $logger) {
            $status = $this->gateway->connect();
            $logger->log('whatsapp.connect', $request->user(), ['state' => $status['state'] ?? null], $request->user()->id);

            return $status;
        });
    }

    public function whatsappQr()
    {
        $this->authorize('manage-whatsapp b2b/reminders');

        return $this->gatewayCall(fn () => ['qr' => $this->gateway->qr()]);
    }

    /** Alternatif QR: kode 8 karakter yang dimasukkan di HP nomor penagihan. */
    public function whatsappPairingCode(Request $request)
    {
        $this->authorize('manage-whatsapp b2b/reminders');
        $validated = $request->validate(['phone' => ['required', 'string', 'max:30']], [], ['phone' => 'nomor WhatsApp penagihan']);
        $phone = Phone::toWhatsApp($validated['phone']);
        if ($phone === null) {
            throw ValidationException::withMessages(['phone' => 'Nomor WhatsApp penagihan tidak valid.']);
        }

        return $this->gatewayCall(fn () => ['code' => $this->gateway->pairingCode($phone), 'phone' => $phone]);
    }

    /** Putuskan tautan nomor penagihan dan hapus sesinya di gateway. Wajib alasan, dicatat di log aktivitas. */
    public function whatsappLogout(Request $request, ActivityLogger $logger)
    {
        $this->authorize('manage-whatsapp b2b/reminders');
        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']], [], ['reason' => 'alasan']);

        return $this->gatewayCall(function () use ($request, $logger, $validated) {
            $before = $this->gateway->status();
            $status = $this->gateway->logout();
            $logger->log('whatsapp.logout', $request->user(), ['number' => $before['me']['id'] ?? null, 'reason' => $validated['reason']], $request->user()->id);

            return $status;
        }, 'Nomor WhatsApp diputuskan.');
    }

    /** Kirim pesan uji (teks saja) untuk memastikan nomor penagihan bisa mengirim. */
    public function whatsappTest(Request $request, ActivityLogger $logger)
    {
        $this->authorize('manage-whatsapp b2b/reminders');
        $validated = $request->validate(['phone' => ['required', 'string', 'max:30']], [], ['phone' => 'nomor tujuan']);
        $phone = Phone::toWhatsApp($validated['phone']);
        if ($phone === null) {
            throw ValidationException::withMessages(['phone' => 'Nomor tujuan tidak valid.']);
        }

        return $this->gatewayCall(function () use ($request, $logger, $phone) {
            $brand = $this->settings->invoiceProfile()['brand_name'];
            $id = $this->gateway->send($phone, "Pesan uji dari backoffice {$brand}. Nomor ini dipakai untuk mengirim reminder tagihan. Abaikan pesan ini.");
            $logger->log('whatsapp.test', $request->user(), ['to' => $phone, 'message_id' => $id], $request->user()->id);

            return ['to' => $phone, 'id' => $id];
        }, 'Pesan uji terkirim ke '.$phone.'.');
    }

    /** Jalankan panggilan gateway; error gateway dibalas JSON 422/503 dengan pesan yang bisa ditampilkan. */
    private function gatewayCall(callable $call, string $message = 'OK')
    {
        try {
            return responseSuccess(false, $message, $call());
        } catch (WhatsAppGatewayException $e) {
            $status = in_array($e->errorCode, ['not_configured', 'unreachable', 'unauthorized'], true) || $e->httpStatus >= 500 ? 503 : 422;

            return response()->json(['status' => 'error', 'message' => $e->getMessage(), 'code' => $e->errorCode], $status);
        }
    }
}

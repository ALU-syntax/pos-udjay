<?php

namespace App\Http\Controllers\B2b;

use App\DataTables\B2b\ReminderLogDataTable;
use App\Http\Controllers\Controller;
use App\Models\B2b\ReminderLog;
use App\Services\B2b\ActivityLogger;
use App\Services\B2b\B2bSettings;
use App\Services\B2b\ReminderService;
use Illuminate\Http\Request;

/** Menu Reminder: aktivitas kiriman (termasuk log email), kirim ulang, dan saklar darurat per channel. */
class ReminderController extends Controller
{
    public function __construct(
        private readonly ReminderService $reminders,
        private readonly B2bSettings $settings,
    ) {
        $this->abilities = [];
    }

    public function index(ReminderLogDataTable $dataTable)
    {
        $this->authorize('read b2b/reminders');

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
                'supported' => in_array($channel, ReminderService::SUPPORTED_CHANNELS, true),
            ]),
            'sendAt' => B2bSettings::REMINDER_SEND_AT,
            'retryUntil' => B2bSettings::REMINDER_RETRY_UNTIL,
        ]);
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
}

<?php

namespace App\Jobs\B2b;

use App\Models\B2b\Invoice;
use App\Models\B2b\Order;
use App\Models\B2b\ReminderLog;
use App\Services\B2b\B2bSettings;
use App\Services\B2b\Reminder\EmailReminderChannel;
use App\Services\B2b\Reminder\ReminderChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/**
 * Kirim satu reminder (satu baris b2b_reminder_logs). Dicoba ulang kalau gagal; kiriman otomatis
 * tidak dikirim lewat batas jam (17:00 WIB) supaya tidak mengganggu waktu istirahat customer.
 */
class SendReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 4;

    public function __construct(public int $logId) {}

    /** Jeda percobaan ulang: 10 menit, 30 menit, 1 jam. */
    public function backoff(): array
    {
        return [600, 1800, 3600];
    }

    public function handle(B2bSettings $settings): void
    {
        $log = ReminderLog::query()->with('customer')->find($this->logId);
        if (! $log || $log->status !== ReminderLog::STATUS_QUEUED) {
            return;
        }

        if (! $settings->reminderChannelEnabled($log->channel)) {
            $this->finish($log, ReminderLog::STATUS_SKIPPED, 'Channel dimatikan lewat saklar darurat.');

            return;
        }
        if ($log->trigger === ReminderLog::TRIGGER_AUTO && now()->gt($settings->reminderRetryDeadline($log->reminder_date))) {
            $this->finish($log, ReminderLog::STATUS_FAILED, $log->error ?: 'Lewat batas jam kirim '.B2bSettings::REMINDER_RETRY_UNTIL.' WIB.');

            return;
        }

        // Dicek ulang saat kirim: invoice yang sudah lunas atau dibatalkan sejak diantrekan tidak ikut ditagih.
        $offsets = $log->invoices()->pluck('b2b_reminder_log_invoices.offset_days', 'b2b_invoices.id');
        $items = Invoice::query()
            ->whereIn('id', $offsets->keys())
            ->with(['order.items', 'customer', 'allocations.payment', 'schedules'])
            ->orderBy('due_date')
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->outstandingAmount() > 0 && $invoice->order?->status !== Order::STATUS_CANCELLED)
            ->map(fn (Invoice $invoice) => ['invoice' => $invoice, 'offset' => (int) $offsets[$invoice->id]])
            ->values();
        if ($items->isEmpty()) {
            $this->finish($log, ReminderLog::STATUS_SKIPPED, 'Semua invoice sudah lunas atau dibatalkan.');

            return;
        }

        $log->increment('attempts');

        try {
            $messageId = $this->channel($log->channel)->send($log, $log->customer, $items, function (array $content) use ($log) {
                $log->forceFill($content)->save();
            });
        } catch (Throwable $e) {
            $log->forceFill(['error' => mb_substr($e->getMessage(), 0, 2000)])->save();

            throw $e;
        }

        $log->forceFill([
            'status' => ReminderLog::STATUS_SENT,
            'provider_message_id' => $messageId,
            'error' => null,
            'sent_at' => now(),
        ])->save();
    }

    /** Semua percobaan habis. */
    public function failed(?Throwable $exception): void
    {
        $log = ReminderLog::query()->find($this->logId);
        if ($log && $log->status === ReminderLog::STATUS_QUEUED) {
            $this->finish($log, ReminderLog::STATUS_FAILED, $exception ? mb_substr($exception->getMessage(), 0, 2000) : $log->error);
        }
    }

    private function channel(string $channel): ReminderChannel
    {
        return match ($channel) {
            ReminderLog::CHANNEL_EMAIL => app(EmailReminderChannel::class),
            default => throw new \RuntimeException("Channel reminder {$channel} belum didukung."),
        };
    }

    private function finish(ReminderLog $log, string $status, ?string $error): void
    {
        $log->forceFill(['status' => $status, 'error' => $error])->save();
    }
}

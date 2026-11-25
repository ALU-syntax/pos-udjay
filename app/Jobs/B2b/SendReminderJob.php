<?php

namespace App\Jobs\B2b;

use App\Models\B2b\Invoice;
use App\Models\B2b\ReminderLog;
use App\Services\B2b\B2bSettings;
use App\Services\B2b\Reminder\EmailReminderChannel;
use App\Services\B2b\Reminder\ReminderChannel;
use App\Services\B2b\Reminder\ReminderSendException;
use App\Services\B2b\Reminder\WhatsAppReminderChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/**
 * Kirim satu kiriman ke customer (satu baris b2b_reminder_logs): reminder, invoice, atau kwitansi. Dicoba ulang kalau gagal;
 * reminder otomatis tidak dikirim lewat batas jam (17:00 WIB) supaya tidak mengganggu waktu istirahat customer.
 * Invoice/kwitansi dikirim saat transaksi terjadi, jadi tidak terikat batas jam.
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
        if ($log->kind === ReminderLog::KIND_REMINDER && $log->trigger === ReminderLog::TRIGGER_AUTO
            && now()->gt($settings->reminderRetryDeadline($log->reminder_date))) {
            $this->finish($log, ReminderLog::STATUS_FAILED, $log->error ?: 'Lewat batas jam kirim '.B2bSettings::REMINDER_RETRY_UNTIL.' WIB.');

            return;
        }

        // Dicek ulang saat kirim: tagihan yang sudah lunas/dibatalkan sejak diantrekan tidak ikut ditagih,
        // dan kwitansi hanya untuk invoice yang masih lunas.
        $offsets = $log->invoices()->pluck('b2b_reminder_log_invoices.offset_days', 'b2b_invoices.id');
        $items = Invoice::query()
            ->whereIn('id', $offsets->keys())
            ->with(['order.items', 'customer', 'allocations.payment', 'schedules'])
            ->orderBy('due_date')
            ->get()
            ->filter(fn (Invoice $invoice) => $log->stillDeliverable($invoice))
            ->map(fn (Invoice $invoice) => ['invoice' => $invoice, 'offset' => (int) $offsets[$invoice->id]])
            ->values();
        if ($items->isEmpty()) {
            $this->finish($log, ReminderLog::STATUS_SKIPPED, $log->kind === ReminderLog::KIND_RECEIPT
                ? 'Invoice belum lunas atau pesanannya dibatalkan.'
                : 'Semua invoice sudah lunas atau dibatalkan.');

            return;
        }

        $log->increment('attempts');

        try {
            $messageId = $this->channel($log->channel)->send($log, $log->customer, $items, function (array $content) use ($log) {
                $log->forceFill($content)->save();
            });
        } catch (ReminderSendException $e) {
            // Mencoba ulang tidak akan berhasil, atau akan mengirim dobel: langsung gagal tanpa retry.
            $this->finish($log, ReminderLog::STATUS_FAILED, mb_substr($e->getMessage(), 0, 2000));

            return;
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
            ReminderLog::CHANNEL_WHATSAPP => app(WhatsAppReminderChannel::class),
            default => throw new \RuntimeException("Channel reminder {$channel} belum didukung."),
        };
    }

    private function finish(ReminderLog $log, string $status, ?string $error): void
    {
        $log->forceFill(['status' => $status, 'error' => $error])->save();
    }
}

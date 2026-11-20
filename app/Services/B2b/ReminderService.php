<?php

namespace App\Services\B2b;

use App\Jobs\B2b\SendReminderJob;
use App\Models\B2b\Customer;
use App\Models\B2b\Invoice;
use App\Models\B2b\Order;
use App\Models\B2b\ReminderLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Memilih tagihan yang perlu diingatkan dan mengantrekan kiriman reminder.
 * Jadwal per customer: hari relatif sebelum jatuh tempo (reminder_days), lalu kalau reminder_overdue_daily aktif
 * harian sampai H+7 dan seminggu sekali setelahnya. Satu kiriman per customer per channel per hari (email gabungan).
 */
class ReminderService
{
    /** Setelah lewat jatuh tempo: harian sampai hari ke-N, lalu tiap N hari. */
    public const OVERDUE_DAILY_UNTIL = 7;

    /** Channel yang sudah punya pengirim. WhatsApp menyusul (langkah 4). */
    public const SUPPORTED_CHANNELS = [ReminderLog::CHANNEL_EMAIL];

    public function __construct(private readonly B2bSettings $settings) {}

    /** Apakah hari relatif ke jatuh tempo ($offset: -3 = H-3, 2 = lewat 2 hari) masuk jadwal reminder customer. */
    public function matchesSchedule(Customer $customer, int $offset): bool
    {
        if ($offset <= 0) {
            return in_array($offset, array_map('intval', $customer->reminder_days ?? []), true);
        }

        return (bool) $customer->reminder_overdue_daily
            && ($offset <= self::OVERDUE_DAILY_UNTIL || $offset % self::OVERDUE_DAILY_UNTIL === 0);
    }

    /**
     * Antrekan reminder otomatis hari ini (dipanggil b2b:send-due-reminders jam 09:00 WIB).
     *
     * @return array{queued:int, skipped:int, duplicate:int, disabled:int}
     */
    public function queueDailyReminders(?Carbon $date = null): array
    {
        $date = ($date ?? today())->copy()->startOfDay();
        $result = ['queued' => 0, 'skipped' => 0, 'duplicate' => 0, 'disabled' => 0];

        $invoices = $this->openCreditInvoices()
            ->whereHas('customer', fn (Builder $customer) => $customer->where('reminder_enabled', true))
            ->with('customer')
            ->get();

        foreach ($invoices->groupBy('b2b_customer_id') as $customerInvoices) {
            $customer = $customerInvoices->first()->customer;
            $items = $customerInvoices
                ->map(fn (Invoice $invoice) => ['invoice' => $invoice, 'offset' => $this->offset($invoice, $date)])
                ->filter(fn (array $item) => $this->matchesSchedule($customer, $item['offset']))
                ->values();
            if ($items->isEmpty()) {
                continue;
            }

            foreach ($this->channelsFor($customer) as $channel) {
                if (! $this->settings->reminderChannelEnabled($channel)) {
                    $result['disabled']++;

                    continue;
                }

                $log = $this->createLog($customer, $channel, $items, $date, ReminderLog::TRIGGER_AUTO);
                if ($log === null) {
                    $result['duplicate']++;
                } else {
                    $result[$log->status === ReminderLog::STATUS_QUEUED ? 'queued' : 'skipped']++;
                }
            }
        }

        return $result;
    }

    /**
     * Buat log kiriman lalu antrekan job-nya. Null kalau kiriman otomatis hari itu sudah ada (anti dobel).
     * Data tujuan yang kosong langsung dicatat sebagai skipped supaya terlihat di menu Reminder.
     *
     * @param  Collection<int, array{invoice: Invoice, offset: int}>  $items
     */
    public function createLog(Customer $customer, string $channel, Collection $items, Carbon $date, string $trigger, ?User $sender = null): ?ReminderLog
    {
        $recipient = $this->recipient($customer, $channel);

        try {
            $log = DB::transaction(function () use ($customer, $channel, $items, $date, $trigger, $sender, $recipient) {
                $log = ReminderLog::create([
                    'b2b_customer_id' => $customer->id,
                    'channel' => $channel,
                    'recipient' => $recipient,
                    'reminder_date' => $date->toDateString(),
                    'trigger' => $trigger,
                    'status' => $recipient ? ReminderLog::STATUS_QUEUED : ReminderLog::STATUS_SKIPPED,
                    'error' => $recipient ? null : $this->missingRecipientMessage($channel),
                    'dedupe_key' => $trigger === ReminderLog::TRIGGER_AUTO ? "auto:{$customer->id}:{$channel}:{$date->toDateString()}" : null,
                    'sent_by' => $sender?->id,
                ]);
                foreach ($items as $item) {
                    $log->invoices()->attach($item['invoice']->id, [
                        'b2b_invoice_schedule_id' => $item['invoice']->schedules()->value('id'),
                        'offset_days' => $item['offset'],
                    ]);
                }

                return $log;
            });
        } catch (UniqueConstraintViolationException) {
            return null;
        }

        if ($log->status === ReminderLog::STATUS_QUEUED) {
            SendReminderJob::dispatch($log->id);
        }

        return $log;
    }

    /** Hari relatif tanggal reminder terhadap jatuh tempo invoice: negatif = sebelum, positif = lewat. */
    public function offset(Invoice $invoice, Carbon $date): int
    {
        return (int) $invoice->due_date->copy()->startOfDay()->diffInDays($date->copy()->startOfDay(), false);
    }

    /** Invoice tempo yang masih punya sisa tagihan dan pesanannya tidak dibatalkan. */
    public function openCreditInvoices(): Builder
    {
        return Invoice::query()
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_OVERDUE])
            ->whereColumn('paid_amount', '<', 'total_to_pay')
            ->whereHas('order', fn (Builder $order) => $order
                ->where('payment_type', Order::PAYMENT_CREDIT)
                ->where('status', '!=', Order::STATUS_CANCELLED));
    }

    /** @return list<string> */
    private function channelsFor(Customer $customer): array
    {
        return array_values(array_intersect(self::SUPPORTED_CHANNELS, (array) ($customer->reminder_channels ?? [])));
    }

    private function recipient(Customer $customer, string $channel): ?string
    {
        $value = trim((string) ($channel === ReminderLog::CHANNEL_EMAIL ? $customer->email : $customer->phone));
        if ($value === '') {
            return null;
        }

        return $channel === ReminderLog::CHANNEL_EMAIL && ! filter_var($value, FILTER_VALIDATE_EMAIL) ? null : $value;
    }

    private function missingRecipientMessage(string $channel): string
    {
        return $channel === ReminderLog::CHANNEL_EMAIL
            ? 'Email customer kosong atau tidak valid. Lengkapi di data customer.'
            : 'Nomor telepon customer kosong. Lengkapi di data customer.';
    }
}

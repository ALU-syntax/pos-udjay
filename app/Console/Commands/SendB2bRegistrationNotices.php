<?php

namespace App\Console\Commands;

use App\Services\B2b\ReminderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendB2bRegistrationNotices extends Command
{
    protected $signature = 'b2b:send-registration-notices';

    protected $description = 'Antrekan konfirmasi pendaftaran (email/WhatsApp) untuk customer B2B baru dari form pendaftaran umum';

    public function handle(ReminderService $reminders): int
    {
        $result = $reminders->queuePendingRegistrationNotices();

        // Dijalankan tiap menit: log hanya kalau ada yang dikirim supaya laravel.log tidak penuh.
        if ($result['customers'] > 0) {
            Log::info('b2b:send-registration-notices dijalankan', $result);
        }
        $this->info("Customer: {$result['customers']}, diantrekan: {$result['queued']}, dilewati: {$result['skipped']}");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\B2b\ReminderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendB2bDueReminders extends Command
{
    protected $signature = 'b2b:send-due-reminders';

    protected $description = 'Antrekan reminder tagihan B2B hari ini sesuai jadwal per customer (email gabungan per customer)';

    public function handle(ReminderService $reminders): int
    {
        $result = $reminders->queueDailyReminders();

        Log::info('b2b:send-due-reminders dijalankan', $result);
        $this->info("Diantrekan: {$result['queued']}, dilewati: {$result['skipped']}, sudah ada: {$result['duplicate']}, channel dimatikan: {$result['disabled']}");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('promo:update-status')->everyMinute();
        $schedule->command('order-table:dispatch-push')->everyMinute()->withoutOverlapping();
        // 01:00–06:00 WIB. Tanpa timezone(), jam dihitung dalam UTC (config app.timezone).
        $schedule->command('email:birthday')
            ->timezone('Asia/Jakarta')
            ->hourly()
            ->between('1:00', '6:00');
        // Tiap 00:30 WIB: buang isi log email ulang tahun yang lebih tua dari 30 hari.
        $schedule->command('email:birthday-log-prune')
            ->timezone('Asia/Jakarta')
            ->dailyAt('00:30');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

<?php

namespace App\Console\Commands;

use App\Services\B2b\OverdueService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MarkB2bOverdue extends Command
{
    protected $signature = 'b2b:mark-overdue';

    protected $description = 'Tandai invoice dan jadwal tagihan B2B tempo yang lewat jatuh tempo sebagai overdue';

    public function handle(OverdueService $overdue): int
    {
        $result = $overdue->markOverdue();

        Log::info('b2b:mark-overdue dijalankan', $result);
        $this->info("Invoice ditandai overdue: {$result['invoices']}, jadwal tagihan: {$result['schedules']}");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\B2b\ContractService;
use Illuminate\Console\Command;

class SyncB2bContractStatus extends Command
{
    protected $signature = 'b2b:sync-contract-status';

    protected $description = 'Tandai kontrak B2B aktif yang sudah lewat tanggal berakhir sebagai expired';

    public function handle(ContractService $contracts): int
    {
        $count = $contracts->expireEndedContracts();
        $this->info("{$count} kontrak B2B ditandai berakhir.");

        return self::SUCCESS;
    }
}

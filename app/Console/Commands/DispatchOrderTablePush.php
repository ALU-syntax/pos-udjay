<?php

namespace App\Console\Commands;

use App\Jobs\SendOrderTablePush;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DispatchOrderTablePush extends Command
{
    protected $signature = 'order-table:dispatch-push {--retry-failed : Requeue failed deliveries before dispatch}';

    protected $description = 'Dispatch durable Order Table FCM outbox deliveries';

    public function handle(): int
    {
        if (! config('order-table.fcm.enabled')) {
            $this->info('FCM disabled.');

            return self::SUCCESS;
        }
        if ($this->option('retry-failed')) {
            DB::table('order_table_push_deliveries')->where('status', 'failed')->update([
                'status' => 'pending', 'attempts' => 0, 'available_at' => now(), 'updated_at' => now(),
            ]);
        }
        $ids = DB::table('order_table_push_deliveries')->where('available_at', '<=', now())
            ->where(fn ($q) => $q->where('status', 'pending')
                ->orWhere(fn ($q) => $q->where('status', 'sending')->where('locked_until', '<=', now())))
            ->orderBy('id')->limit(500)->pluck('id');
        foreach ($ids as $id) {
            SendOrderTablePush::dispatch((int) $id);
        }
        $this->info('Queued '.$ids->count().' deliveries.');

        return self::SUCCESS;
    }
}

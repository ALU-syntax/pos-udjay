<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\OrderTable\FcmSender;
use App\Services\OrderTable\PushRecipients;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SendOrderTablePush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 45;

    public function __construct(public int $deliveryId)
    {
        $this->onConnection(config('order-table.fcm.connection', 'database'));
        $this->onQueue(config('order-table.fcm.queue', 'order-table'));
    }

    public function handle(FcmSender $sender, PushRecipients $recipients): void
    {
        if (! config('order-table.fcm.enabled')) {
            return;
        }
        $lease = (string) Str::uuid();
        $claimed = DB::table('order_table_push_deliveries')->where('id', $this->deliveryId)
            ->where('available_at', '<=', now())
            ->where(fn ($q) => $q->where('status', 'pending')
                ->orWhere(fn ($q) => $q->where('status', 'sending')->where('locked_until', '<=', now())))
            ->update(['status' => 'sending', 'lease' => $lease, 'locked_until' => now()->addMinutes(2),
                'attempts' => DB::raw('attempts + 1'), 'updated_at' => now()]);
        if (! $claimed) {
            return;
        }
        $delivery = DB::table('order_table_push_deliveries')->where('id', $this->deliveryId)->first();
        $event = DB::table('order_table_events')->where('id', $delivery->event_id)->first();
        $status = 'skipped';
        $error = null;
        try {
            $device = $event ? $recipients->query($event->outlet_id)->where('d.id', $delivery->device_id)
                ->where('d.shift_session_id', $delivery->shift_session_id)->where('d.fcm_token_hash', $delivery->token_hash)->first() : null;
            $user = $device ? User::find($device->user_id) : null;
            if ($device && $user && in_array((int) $event->outlet_id, $user->outletIds(), true)) {
                $status = $sender->send(Crypt::decryptString($device->fcm_token), json_decode($event->payload, true));
                if ($status === 'invalid') {
                    DB::table('order_table_devices')->where('id', $device->id)->where('fcm_token_hash', $delivery->token_hash)
                        ->update(['fcm_token' => null, 'fcm_token_hash' => null, 'updated_at' => now()]);
                }
            }
        } catch (\Throwable $e) {
            // Outbox persists safe error codes only; never serialize HTTP/key/token exceptions into failed_jobs.
            $status = $delivery->attempts >= 10 ? 'failed' : 'pending';
            $error = 'FCM_DELIVERY_RETRY';
        }
        DB::table('order_table_push_deliveries')->where('id', $this->deliveryId)->where('lease', $lease)->update([
            'status' => $status, 'lease' => null, 'locked_until' => null, 'last_error' => $error,
            'available_at' => now()->addSeconds(min(3600, 30 * (2 ** min(7, $delivery->attempts)))), 'updated_at' => now(),
        ]);
    }
}

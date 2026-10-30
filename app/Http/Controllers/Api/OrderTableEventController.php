<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendOrderTablePush;
use App\Models\OrderTable\Order;
use App\Services\OrderTable\CashierError;
use App\Services\OrderTable\PushRecipients;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderTableEventController extends Controller
{
    public function __construct()
    {
        $this->abilities = [];
    }

    public function store(Request $request, PushRecipients $recipients)
    {
        $key = (string) config('order-table.fcm.event_key');
        if (! $key || ! hash_equals($key, (string) $request->header('X-Internal-Api-Key'))) {
            throw new CashierError('INTERNAL_AUTH_REQUIRED', 401);
        }
        if (! config('order-table.fcm.enabled')) {
            throw new CashierError('FCM_DISABLED', 503);
        }
        $data = $request->validate([
            'eventId' => ['required', 'uuid'], 'orderId' => ['required', 'integer', 'min:1'],
            'outletId' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'in:order.placed,order.settle_requested,order.paid,order.expired,order.cancelled,order.served,order.bridge_updated,order.status_changed'],
        ]);
        $result = DB::transaction(function () use ($data, $recipients) {
            $order = Order::whereKey($data['orderId'])->where('outlet_id', $data['outletId'])->lockForUpdate()->firstOrFail();
            $hash = hash('sha256', json_encode([(int) $data['orderId'], (int) $data['outletId'], $data['type']]));
            $inserted = DB::table('order_table_events')->insertOrIgnore([
                'event_id' => strtolower($data['eventId']), 'payload_hash' => $hash, 'order_id' => $order->id,
                'outlet_id' => $order->outlet_id, 'type' => $data['type'], 'payload' => json_encode([
                    'eventId' => strtolower($data['eventId']), 'type' => $data['type'],
                    'orderId' => (string) $order->id, 'outletId' => (string) $order->outlet_id,
                    'orderNo' => $order->order_no,
                    // Android memutar suara pesanan baru: bayar di kasir saat order.placed, QRIS saat order.paid.
                    'paymentMode' => $order->payment_mode,
                ]), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $event = DB::table('order_table_events')->where('event_id', strtolower($data['eventId']))->lockForUpdate()->first();
            if (! hash_equals($event->payload_hash, $hash)) {
                throw new CashierError('EVENT_ID_CONFLICT');
            }
            // Only the initial insertion owns the recipient snapshot. Event timestamps are immutable.
            if ($inserted) {
                foreach ($recipients->query($order->outlet_id)->get() as $device) {
                    $user = \App\Models\User::find($device->user_id);
                    if (! $user || ! in_array($order->outlet_id, $user->outletIds(), true)) {
                        continue;
                    }
                    DB::table('order_table_push_deliveries')->insertOrIgnore([
                        'event_id' => $event->id, 'device_id' => $device->id, 'shift_session_id' => $device->shift_session_id,
                        'token_hash' => $device->fcm_token_hash, 'available_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }

            return ['eventId' => $event->event_id, 'accepted' => true, 'eventRowId' => $inserted ? $event->id : null];
        }, 3);

        // Kirim langsung setelah response agar order baru muncul di HP kasir dalam hitungan detik.
        // Scheduler `order-table:dispatch-push` tetap menjadi fallback untuk retry/kegagalan.
        if ($result['eventRowId'] && config('order-table.fcm.dispatch_immediately')) {
            DB::table('order_table_push_deliveries')->where('event_id', $result['eventRowId'])
                ->where('status', 'pending')->pluck('id')
                ->each(fn ($id) => SendOrderTablePush::dispatchAfterResponse((int) $id));
        }
        unset($result['eventRowId']);

        // The scheduler drains committed outbox rows; queue outage cannot lose accepted events.
        return response()->json(['status' => 'success', 'data' => $result], 202);
    }
}

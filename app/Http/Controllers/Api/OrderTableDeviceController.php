<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderTable\CashierError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class OrderTableDeviceController extends Controller
{
    public function __construct()
    {
        $this->abilities = [];
    }

    public function fcmToken(Request $request): JsonResponse
    {
        $data = $request->validate(['fcmToken' => ['required', 'string', 'max:4096']]);
        $token = $request->user()->currentAccessToken();
        DB::transaction(function () use ($token, $data) {
            $binding = DB::table('order_table_devices')->where('access_token_id', $token->id)->lockForUpdate()->first();
            $hash = hash('sha256', $data['fcmToken']);
            $existing = DB::table('order_table_devices')->where('fcm_token_hash', $hash)->lockForUpdate()->first();
            if ($existing && $existing->device_id !== $binding->device_id) {
                throw new CashierError('FCM_TOKEN_IN_USE');
            }
            // Re-login on the same physical device retires its old user/outlet registration.
            DB::table('order_table_devices')->where('device_id', $binding->device_id)
                ->where('id', '!=', $binding->id)->update(['fcm_token' => null, 'fcm_token_hash' => null, 'updated_at' => now()]);
            DB::table('order_table_devices')->where('id', $binding->id)->update([
                'fcm_token' => Crypt::encryptString($data['fcmToken']), 'fcm_token_hash' => $hash, 'updated_at' => now(),
            ]);
        }, 3);

        return response()->json(['status' => 'success', 'data' => ['registered' => true]]);
    }

    public function destroy(Request $request): JsonResponse
    {
        DB::table('order_table_devices')->where('access_token_id', $request->user()->currentAccessToken()->id)
            ->update(['fcm_token' => null, 'fcm_token_hash' => null, 'updated_at' => now()]);

        return response()->json(['status' => 'success', 'data' => ['registered' => false]]);
    }
}

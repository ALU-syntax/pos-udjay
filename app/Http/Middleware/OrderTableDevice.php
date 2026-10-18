<?php

namespace App\Http\Middleware;

use App\Models\ShiftSession;
use App\Services\OrderTable\CashierError;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderTableDevice
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = $request->user();
            $token = $user->currentAccessToken();
            if (! $token instanceof \Laravel\Sanctum\PersonalAccessToken || ! $token->getKey()) {
                throw new CashierError('DEVICE_TOKEN_REQUIRED', 401);
            }
            if ((int) $user->status !== 1 || (int) $user->deleted !== 0) {
                throw new CashierError('ACTIVE_USER_REQUIRED', 403);
            }
            $device = (string) $request->header('X-Device-Id');
            $shiftId = (string) $request->header('X-Shift-Session-Id');
            if (! preg_match('/^[A-Za-z0-9_.:-]{1,64}$/D', $device) || ! ctype_digit($shiftId)) {
                throw new CashierError('DEVICE_CONTEXT_REQUIRED', 422);
            }
            $shift = ShiftSession::whereKey($shiftId)->where('user_id', $user->id)
                ->where('android_id', $device)->where('status', 'ACTIVE')->whereNull('closed_at')
                ->whereIn('outlet_id', $user->outletIds())
                ->whereHas('pettyCash', fn ($q) => $q->whereNull('close')->whereColumn('petty_cashes.outlet_id', 'shift_sessions.outlet_id'))
                ->first();
            if (! $shift) {
                throw new CashierError('ACTIVE_SHIFT_REQUIRED', 403);
            }
            DB::transaction(function () use ($token, $shift, $device, $user) {
                // Lock the existing token row, including the first registration race.
                $token->newQuery()->whereKey($token->getKey())->lockForUpdate()->firstOrFail();
                $binding = DB::table('order_table_devices')->where('access_token_id', $token->getKey())->first();
                if ($binding && ($binding->device_id !== $device || (int) $binding->outlet_id !== (int) $shift->outlet_id)) {
                    throw new CashierError('DEVICE_BINDING_MISMATCH', 403);
                }
                DB::table('order_table_devices')->updateOrInsert(['access_token_id' => $token->getKey()], [
                    'user_id' => $user->id, 'device_id' => $device, 'outlet_id' => $shift->outlet_id,
                    'shift_session_id' => $shift->id, 'updated_at' => now(),
                ]);
            });
            $request->attributes->set('order_table_shift', $shift);

            return $next($request);
        } catch (CashierError $e) {
            return response()->json(['status' => 'error', 'code' => $e->businessCode, 'message' => $e->businessCode], $e->status);
        } catch (QueryException $e) {
            // Never expose SQL, bindings, tokens, or connection details to a mobile client.
            return response()->json(['status' => 'error', 'code' => 'ORDER_TABLE_RETRY_REQUIRED', 'message' => 'Refresh payment-state before retrying.'], 503);
        }
    }
}

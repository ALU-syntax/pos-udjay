<?php

namespace App\Services\OrderTable;

use Illuminate\Support\Facades\DB;

class PushRecipients
{
    public function query(int $outletId)
    {
        return DB::table('order_table_devices as d')
            ->join('personal_access_tokens as t', 't.id', '=', 'd.access_token_id')
            ->join('shift_sessions as s', 's.id', '=', 'd.shift_session_id')
            ->join('petty_cashes as p', 'p.id', '=', 's.petty_cash_id')
            ->join('users as u', 'u.id', '=', 'd.user_id')
            ->where('d.outlet_id', $outletId)->where('s.outlet_id', $outletId)->where('p.outlet_id', (string) $outletId)
            ->whereColumn('s.user_id', 'd.user_id')->whereColumn('s.android_id', 'd.device_id')
            ->whereColumn('t.tokenable_id', 'd.user_id')->where('t.tokenable_type', (new \App\Models\User)->getMorphClass())
            ->where('s.status', 'ACTIVE')->whereNull('s.closed_at')->whereNull('p.close')
            ->where('u.status', 1)->where('u.deleted', 0)
            ->where(fn ($q) => $q->whereNull('t.expires_at')->orWhere('t.expires_at', '>', now()))
            ->whereNotNull('d.fcm_token')->whereNotNull('d.fcm_token_hash')->select('d.*');
    }
}

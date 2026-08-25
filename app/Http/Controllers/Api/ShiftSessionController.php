<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PettyCash;
use App\Models\ShiftSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShiftSessionController extends Controller
{
    /**
     * Cek petty cash aktif di outlet user, lalu pastikan device memiliki
     * shift session untuk petty cash tersebut.
     *
     * Identifikasi device menggunakan android_id, bukan user_id,
     * karena satu user bisa login di outlet yang sama dari device berbeda.
     *
     * Flow:
     * 1. Cari petty cash aktif (close IS NULL) di outlet user.
     * 2. Tidak ada → return is_active: false.
     * 3. Ada → cek apakah device (android_id) sudah punya shift session untuk petty cash ini.
     *    - Sudah ada → update last_sync_at, return existing shift session.
     *    - Belum ada → buat shift session baru sebagai child, return data baru.
     *
     * Request body:
     * - android_id:  string (required) — Android ID dari Settings.Secure.ANDROID_ID
     * - device_name: string (nullable) — nama perangkat, misal "Samsung Galaxy Tab A8"
     *
     * GET /api/v1/shift/petty-cash/active
     */
    public function checkActivePettyCash(Request $request): JsonResponse
    {
        $request->validate([
            'device_name' => ['nullable', 'string', 'max:100'],
            'android_id'  => ['required', 'string', 'max:64'],
        ]);

        $user      = $request->user();
        $outletIds = $user->outletIds();

        if (empty($outletIds)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak memiliki outlet yang terdaftar.',
            ], 422);
        }

        $outletId = $outletIds[0];

        // 1. Cari petty cash aktif di outlet
        $pettyCash = PettyCash::where('outlet_id', (string) $outletId)
            ->whereNull('close')
            ->latest()
            ->first();

        if (!$pettyCash) {
            return response()->json([
                'status'    => 'success',
                'is_active' => false,
                'message'   => 'Tidak ada petty cash aktif di outlet ini.',
                'data'      => null,
            ]);
        }

        // 2. Cek apakah device ini sudah punya shift session untuk petty cash ini
        $shiftSession = ShiftSession::where('petty_cash_id', $pettyCash->id)
            ->where('android_id', $request->input('android_id'))
            ->first();

        if (!$shiftSession) {
            // 3. Belum ada — device ini baru join, daftarkan sebagai child session.
            // Cari parent session (parent_session_id = NULL) untuk petty cash ini.
            $parentSession = ShiftSession::where('petty_cash_id', $pettyCash->id)
                ->whereNull('parent_session_id')
                ->first();

            $shiftSession = ShiftSession::create([
                'petty_cash_id'     => $pettyCash->id,
                'outlet_id'         => $outletId,
                'user_id'           => $user->id,
                'parent_session_id' => $parentSession?->id,
                'status'            => 'ACTIVE',
                'device_name'       => $request->input('device_name'),
                'android_id'        => $request->input('android_id'),
                'last_sync_at'      => now(),
            ]);

            $sessionStatus = 'created';
            $message       = 'Petty cash aktif ditemukan. Shift session baru dibuat.';
        } else {
            // 4. Sudah ada — update last_sync_at dan data device jika dikirim
            $shiftSession->update([
                'last_sync_at' => now(),
                'device_name'  => $request->input('device_name', $shiftSession->device_name),
                'android_id'   => $request->input('android_id', $shiftSession->android_id),
            ]);

            $sessionStatus = 'existing';
            $message       = 'Petty cash aktif ditemukan. Shift session sudah ada.';
        }

        return response()->json([
            'status'         => 'success',
            'is_active'      => true,
            'session_status' => $sessionStatus,
            'message'        => $message,
            'data'           => [
                'petty_cash'    => $pettyCash->only([
                    'id', 'outlet_id', 'amount_awal', 'amount_akhir',
                    'user_id_started', 'user_id_ended', 'open', 'close', 'created_at',
                ]),
                'shift_session' => $shiftSession->only([
                    'id', 'petty_cash_id', 'outlet_id', 'user_id',
                    'parent_session_id', 'status', 'device_name',
                    'android_id', 'last_sync_at', 'created_at',
                ]),
            ],
        ]);
    }

    /**
     * Buka petty cash baru di outlet user yang sedang login.
     *
     * Hanya bisa dibuka jika tidak ada petty cash aktif di outlet tersebut.
     * Setelah petty cash dibuat, langsung dibuat pula parent shift session
     * untuk device yang membuka petty cash ini.
     *
     * Request body:
     * - amount_awal: numeric (required) — saldo awal kas
     * - android_id:  string  (required) — Android ID dari Settings.Secure.ANDROID_ID
     * - device_name: string  (nullable) — nama perangkat
     *
     * POST /api/v1/shift/petty-cash
     */
    public function storePettyCash(Request $request): JsonResponse
    {
        $request->validate([
            'amount_awal' => ['required', 'numeric', 'min:0'],
            'android_id'  => ['required', 'string', 'max:64'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user      = $request->user();
        $outletIds = $user->outletIds();

        if (empty($outletIds)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak memiliki outlet yang terdaftar.',
            ], 422);
        }

        $outletId = $outletIds[0];

        // Buat petty cash dan parent shift session dalam satu transaksi DB.
        // GET_LOCK adalah advisory lock MySQL per outlet — tidak tergantung keberadaan row,
        // sehingga aman meski tabel kosong. Request lain dengan outlet yang sama
        // akan menunggu maksimal 5 detik sebelum gagal.
        try {
            $result = DB::transaction(function () use ($request, $user, $outletId) {
                $lockName     = "petty_cash_outlet_{$outletId}";
                $lockAcquired = DB::selectOne("SELECT GET_LOCK(?, 5) AS acquired", [$lockName]);

                if (!$lockAcquired || !$lockAcquired->acquired) {
                    throw new \RuntimeException('LOCK_TIMEOUT');
                }

                try {
                    $existing = PettyCash::where('outlet_id', (string) $outletId)
                        ->whereNull('close')
                        ->exists();

                    if ($existing) {
                        throw new \RuntimeException('PETTY_CASH_ACTIVE');
                    }

                    $pettyCash = PettyCash::create([
                        'outlet_id'       => (string) $outletId,
                        'amount_awal'     => $request->input('amount_awal'),
                        'user_id_started' => $user->id,
                        'open'            => now(),
                    ]);

                    // Kasir yang membuka petty cash otomatis jadi parent shift session
                    $shiftSession = ShiftSession::create([
                        'petty_cash_id'     => $pettyCash->id,
                        'outlet_id'         => $outletId,
                        'user_id'           => $user->id,
                        'parent_session_id' => null,
                        'status'            => 'ACTIVE',
                        'device_name'       => $request->input('device_name'),
                        'android_id'        => $request->input('android_id'),
                        'last_sync_at'      => now(),
                    ]);

                    return compact('pettyCash', 'shiftSession');
                } finally {
                    DB::statement("SELECT RELEASE_LOCK(?)", [$lockName]);
                }
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'PETTY_CASH_ACTIVE') {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Masih ada petty cash aktif di outlet ini. Tutup shift terlebih dahulu.',
                ], 422);
            }

            if ($e->getMessage() === 'LOCK_TIMEOUT') {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Server sedang sibuk memproses permintaan lain. Silakan coba lagi.',
                ], 503);
            }

            throw $e;
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Shift berhasil dibuka.',
            'data'    => [
                'petty_cash'    => $result['pettyCash']->only([
                    'id', 'outlet_id', 'amount_awal', 'amount_akhir',
                    'user_id_started', 'user_id_ended', 'open', 'close', 'created_at',
                ]),
                'shift_session' => $result['shiftSession']->only([
                    'id', 'petty_cash_id', 'outlet_id', 'user_id',
                    'parent_session_id', 'status', 'device_name',
                    'android_id', 'last_sync_at', 'created_at',
                ]),
            ],
        ], 201);
    }

    /**
     * Tutup shift session.
     *
     * Behaviour berbeda tergantung apakah session ini parent atau child:
     *
     * CHILD SESSION (parent_session_id != null):
     * - Tidak perlu amount_akhir
     * - Langsung set status = CLOSED dan closed_at = now()
     *
     * PARENT SESSION (parent_session_id = null):
     * - Wajib kirim amount_akhir
     * - Cek semua child session milik petty cash ini sudah CLOSED
     *   → Jika belum → return error beserta list child yang masih aktif
     * - Tutup parent session + petty cash sekaligus
     *
     * Request body:
     * - amount_akhir: numeric (required hanya untuk parent session)
     *
     * PATCH /api/v1/shift/session/{id}/close
     */
    public function closeSession(Request $request, int $id): JsonResponse
    {
        $shiftSession = ShiftSession::find($id);

        if (!$shiftSession) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Shift session tidak ditemukan.',
            ], 404);
        }

        if ($shiftSession->status === 'CLOSED') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Shift session sudah ditutup.',
            ], 422);
        }

        $isParent = is_null($shiftSession->parent_session_id);

        if ($isParent) {
            // Parent session — validasi amount_akhir wajib
            $request->validate([
                'amount_akhir' => ['required', 'numeric', 'min:0'],
            ]);

            // Cek apakah masih ada child session yang belum CLOSED
            $activeChildren = ShiftSession::where('petty_cash_id', $shiftSession->petty_cash_id)
                ->whereNotNull('parent_session_id')
                ->where('status', 'ACTIVE')
                ->get(['id', 'user_id', 'device_name', 'android_id', 'status']);

            if ($activeChildren->isNotEmpty()) {
                return response()->json([
                    'status'          => 'error',
                    'message'         => 'Masih ada shift session lain yang belum ditutup.',
                    'active_children' => $activeChildren,
                ], 422);
            }

            // Semua child sudah close — tutup parent session dan petty cash
            DB::transaction(function () use ($request, $shiftSession) {
                $shiftSession->update([
                    'status'    => 'CLOSED',
                    'closed_at' => now(),
                ]);

                PettyCash::where('id', $shiftSession->petty_cash_id)
                    ->update([
                        'amount_akhir'  => $request->input('amount_akhir'),
                        'user_id_ended' => $shiftSession->user_id,
                        'close'         => now(),
                    ]);
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Shift berhasil ditutup.',
                'data'    => [
                    'shift_session' => $shiftSession->fresh()->only([
                        'id', 'petty_cash_id', 'outlet_id', 'user_id',
                        'parent_session_id', 'status', 'closed_at',
                    ]),
                    'petty_cash'    => PettyCash::find($shiftSession->petty_cash_id)?->only([
                        'id', 'outlet_id', 'amount_awal', 'amount_akhir',
                        'user_id_started', 'user_id_ended', 'open', 'close',
                    ]),
                ],
            ]);
        }

        // Child session — cukup close session ini saja
        $shiftSession->update([
            'status'    => 'CLOSED',
            'closed_at' => now(),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Shift session berhasil ditutup.',
            'data'    => [
                'shift_session' => $shiftSession->fresh()->only([
                    'id', 'petty_cash_id', 'outlet_id', 'user_id',
                    'parent_session_id', 'status', 'closed_at',
                ]),
            ],
        ]);
    }
}

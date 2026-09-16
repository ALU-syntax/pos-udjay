<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\CustomerRegistered;
use App\Mail\RegistrasiMembershipKomunitas;
use App\Models\BirthdayRewardClaims;
use App\Models\Customer;
use App\Models\CustomerPoinExp;
use App\Models\CustomerReferral;
use App\Models\ExpRewardClaims;
use App\Models\HistoryExpMembershipLevel;
use App\Models\LevelMembership;
use App\Models\Outlets;
use App\Models\ProductBirthdayReward;
use App\Models\ProductExpReward;
use App\Models\RewardConfirmation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * Cari/ambil daftar customer — ringkas untuk kolom "Pilih Pelanggan".
     *
     * - Hanya info dasar: nama, no. HP, level, poin, EXP, kapan dibuat
     * - Data reward (birthday/EXP/level) ada di endpoint detail
     *
     * GET /api/v1/customers?q={search}&limit={n}
     */
    public function index(Request $request): JsonResponse
    {
        $outletIds = $request->user()->outletIds();

        if (empty($outletIds)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak memiliki outlet yang terdaftar.',
            ], 422);
        }

        $search = trim((string) $request->query('q', ''));
        $limit  = min((int) $request->query('limit', 50), 200);

        $customers = Customer::with(['levelMembership'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('telfon', 'like', "%{$search}%");
            })
            ->orderBy('name', 'asc')
            ->take($limit)
            ->get();

        $data = $customers->map(function ($customer) {
            return [
                'id'                   => $customer->id,
                'name'                 => $customer->name,
                'phone'                => $customer->telfon,
                'point'                => (int) $customer->point,
                'exp'                  => (int) $customer->exp,
                'level_memberships_id' => $customer->level_memberships_id,
                'level'                => $customer->levelMembership
                    ? [
                        'id'    => $customer->levelMembership->id,
                        'name'  => $customer->levelMembership->name,
                        'color' => $customer->levelMembership->color,
                    ]
                    : null,
                'created_at'           => $customer->created_at?->format('d M Y'),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    /**
     * Delta sync customer untuk cache lokal mobile (Room / SQLite).
     *
     * Scope data GLOBAL (semua customer, tidak difilter per outlet).
     *
     * Cara pakai:
     * 1. Sync awal  : panggil tanpa `updated_since` & tanpa `cursor`, lalu ikuti
     *                 `next_cursor` sampai `has_more` = false.
     * 2. Sync berkala: simpan `server_time` dari response terakhir, kirim sebagai
     *                 `updated_since` pada sync berikutnya.
     *
     * - `cursor` adalah opaque base64 (jangan diparse client, cukup disimpan & dikirim balik)
     * - Baris soft-deleted tetap dikirim dengan `is_deleted` = true agar device
     *   dapat menghapus data lokalnya
     * - Hasil diurutkan `updated_at ASC, id ASC` (memakai index customers_updated_at_id_index)
     *
     * GET /api/v1/customers/sync?updated_since=&cursor=&limit=
     */
    public function sync(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 500);
        $limit = max(1, min($limit, 1000));

        $updatedSince = $request->query('updated_since');
        $cursorRaw     = $request->query('cursor');

        $cursor = null;
        if (!empty($cursorRaw)) {
            $cursor = $this->decodeSyncCursor((string) $cursorRaw);

            if ($cursor === null) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Cursor tidak valid. Silakan mulai ulang sync tanpa cursor.',
                ], 422);
            }
        }

        $query = Customer::withTrashed()->with([
            'levelMembership' => function ($q) {
                $q->select('id', 'name', 'color');
            },
            'createdBy' => function ($q) {
                $q->select('id', 'name', 'outlet_id');
            },
        ]);

        // Prioritas: cursor (keyset pagination) > updated_since (delta) > full sync
        if ($cursor !== null) {
            $query->where(function ($q) use ($cursor) {
                $q->where('updated_at', '>', $cursor['updated_at'])
                    ->orWhere(function ($q2) use ($cursor) {
                        $q2->where('updated_at', '=', $cursor['updated_at'])
                            ->where('id', '>', $cursor['id']);
                    });
            });
        } elseif (!empty($updatedSince)) {
            try {
                $since = Carbon::parse($updatedSince)->utc();
            } catch (\Throwable $e) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Format updated_since tidak valid. Gunakan ISO-8601 (contoh: 2026-09-11T10:00:00Z).',
                ], 422);
            }

            // Gunakan >= agar baris yang berubah tepat pada server_time terakhir tidak terlewat.
            // Duplikasi aman karena device melakukan upsert berdasarkan id.
            $query->where('updated_at', '>=', $since);
        }

        // Ambil limit + 1 untuk mengetahui apakah masih ada halaman berikutnya
        $rows = $query->orderBy('updated_at', 'asc')
            ->orderBy('id', 'asc')
            ->limit($limit + 1)
            ->get();

        $hasMore = $rows->count() > $limit;
        $rows    = $rows->take($limit)->values();

        // Kumpulkan outlet_id dari user pembuat (index pertama outlet user),
        // lalu ambil semua outlet dalam satu query untuk menghindari N+1.
        $outletIds = $rows
            ->map(function (Customer $customer) {
                $ids = $customer->createdBy?->outletIds() ?? [];
                return $ids[0] ?? null;
            })
            ->filter()
            ->unique()
            ->values();

        $outlets = $outletIds->isNotEmpty()
            ? Outlets::whereIn('id', $outletIds)->pluck('name', 'id')
            : collect();

        $data = $rows->map(function (Customer $customer) use ($outlets) {
            // Outlet diambil dari index pertama outlet_id milik user pembuat
            $creatorOutletId = $customer->createdBy?->outletIds()[0] ?? null;

            return [
                'id'                   => $customer->id,
                'name'                 => $customer->name,
                'phone'                => $customer->telfon,
                'email'                => $customer->email,
                'umur'                 => $customer->umur !== null ? (int) $customer->umur : null,
                'tanggal_lahir'        => $customer->tanggal_lahir,
                'domisili'             => $customer->domisili,
                'gender'               => $customer->gender,
                'community_id'         => $customer->community_id,
                'referral_id'          => $customer->referral_id,
                'level_memberships_id' => $customer->level_memberships_id,
                'level'                => $customer->levelMembership
                    ? [
                        'id'    => $customer->levelMembership->id,
                        'name'  => $customer->levelMembership->name,
                        'color' => $customer->levelMembership->color,
                    ]
                    : null,
                'point'                => (int) $customer->point,
                'exp'                  => (int) $customer->exp,
                'level_batch'          => $customer->level_batch,
                'user_id'              => $customer->user_id,
                'user_name'            => $customer->createdBy?->name,
                'outlet_id'            => $creatorOutletId,
                'outlet_name'          => $creatorOutletId ? ($outlets[$creatorOutletId] ?? null) : null,
                'is_deleted'           => $customer->deleted_at !== null,
                'deleted_at'           => $customer->deleted_at,
                'created_at'           => $customer->created_at,
                'updated_at'           => $customer->updated_at,
            ];
        });

        $nextCursor = null;
        if ($hasMore && $rows->isNotEmpty()) {
            $last = $rows->last();
            $nextCursor = $this->encodeSyncCursor($last->updated_at, $last->id);
        }

        return response()->json([
            'status'      => 'success',
            'data'        => $data,
            'has_more'    => $hasMore,
            'next_cursor' => $nextCursor,
            'server_time' => Carbon::now()->utc()->toIso8601String(),
        ]);
    }

    /**
     * Delta sync pencatatan claim birthday reward untuk cache lokal mobile (Room / SQLite).
     *
     * Scope data GLOBAL (semua outlet, tidak difilter per outlet). Tujuannya agar
     * customer yang sudah claim di outlet A tidak bisa claim lagi di outlet B.
     *
     * Kunci validasi claim di mobile: `customer_id` + `age`.
     * Customer hanya boleh claim sekali per umur, dan pengecekan ini harus
     * mempertimbangkan record dari seluruh outlet.
     *
     * Cara pakai:
     * 1. Sync awal  : panggil tanpa `updated_since` & tanpa `cursor`, lalu ikuti
     *                 `next_cursor` sampai `has_more` = false.
     * 2. Sync berkala: simpan `server_time` dari response terakhir, kirim sebagai
     *                 `updated_since` pada sync berikutnya.
     *
     * - `cursor` adalah opaque base64 (jangan diparse client, cukup disimpan & dikirim balik)
     * - Baris soft-deleted tetap dikirim dengan `is_deleted` = true agar device
     *   dapat menghapus data lokalnya
     * - `customer_name`, `outlet_name`, dan `product_name` ikut dikirim agar mobile
     *   tidak perlu join di Room
     * - Hasil diurutkan `updated_at ASC, id ASC`
     *
     * GET /api/v1/customers/birthday-claims/sync?updated_since=&cursor=&limit=
     */
    public function birthdayClaimsSync(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 500);
        $limit = max(1, min($limit, 1000));

        $updatedSince = $request->query('updated_since');
        $cursorRaw    = $request->query('cursor');

        $cursor = null;
        if (!empty($cursorRaw)) {
            $cursor = $this->decodeSyncCursor((string) $cursorRaw);

            if ($cursor === null) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Cursor tidak valid. Silakan mulai ulang sync tanpa cursor.',
                ], 422);
            }
        }

        $query = BirthdayRewardClaims::withTrashed()->with([
            'customer' => function ($q) {
                $q->withTrashed()->select('id', 'name');
            },
            'outlet' => function ($q) {
                $q->select('id', 'name');
            },
            'product' => function ($q) {
                $q->select('id', 'name');
            },
        ]);

        // Prioritas: cursor (keyset pagination) > updated_since (delta) > full sync
        if ($cursor !== null) {
            $query->where(function ($q) use ($cursor) {
                $q->where('updated_at', '>', $cursor['updated_at'])
                    ->orWhere(function ($q2) use ($cursor) {
                        $q2->where('updated_at', '=', $cursor['updated_at'])
                            ->where('id', '>', $cursor['id']);
                    });
            });
        } elseif (!empty($updatedSince)) {
            try {
                $since = Carbon::parse($updatedSince)->utc();
            } catch (\Throwable $e) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Format updated_since tidak valid. Gunakan ISO-8601 (contoh: 2026-09-11T10:00:00Z).',
                ], 422);
            }

            // Gunakan >= agar baris yang berubah tepat pada server_time terakhir tidak terlewat.
            // Duplikasi aman karena device melakukan upsert berdasarkan id.
            $query->where('updated_at', '>=', $since);
        }

        // Ambil limit + 1 untuk mengetahui apakah masih ada halaman berikutnya
        $rows = $query->orderBy('updated_at', 'asc')
            ->orderBy('id', 'asc')
            ->limit($limit + 1)
            ->get();

        $hasMore = $rows->count() > $limit;
        $rows    = $rows->take($limit)->values();

        $data = $rows->map(function (BirthdayRewardClaims $claim) {
            return [
                'id'            => $claim->id,
                'customer_id'   => $claim->customer_id,
                'customer_name' => $claim->customer?->name,
                'outlet_id'     => $claim->outlet_id,
                'outlet_name'   => $claim->outlet?->name,
                'product_id'    => $claim->product_id,
                'product_name'  => $claim->product?->name,
                'age'           => (int) $claim->age,
                'is_deleted'    => $claim->deleted_at !== null,
                'deleted_at'    => $claim->deleted_at,
                'created_at'    => $claim->created_at,
                'updated_at'    => $claim->updated_at,
            ];
        });

        $nextCursor = null;
        if ($hasMore && $rows->isNotEmpty()) {
            $last = $rows->last();
            $nextCursor = $this->encodeSyncCursor($last->updated_at, $last->id);
        }

        return response()->json([
            'status'      => 'success',
            'data'        => $data,
            'has_more'    => $hasMore,
            'next_cursor' => $nextCursor,
            'server_time' => Carbon::now()->utc()->toIso8601String(),
        ]);
    }

    /**
     * Delta sync pencatatan claim EXP milestone reward untuk cache lokal mobile (Room / SQLite).
     *
     * Scope data GLOBAL (semua outlet, tidak difilter per outlet). Tujuannya agar
     * customer yang sudah claim di outlet A tidak bisa claim lagi di outlet B.
     *
     * Kunci validasi claim di mobile: `customer_id` + `exp` + `level_batch`.
     * Customer hanya boleh claim satu kali untuk tiap milestone EXP
     * (kelipatan 5000 terbesar dari `customers.exp`) pada `level_batch` yang sama.
     *
     * Cara pakai:
     * 1. Sync awal  : panggil tanpa `updated_since` & tanpa `cursor`, lalu ikuti
     *                 `next_cursor` sampai `has_more` = false.
     * 2. Sync berkala: simpan `server_time` dari response terakhir, kirim sebagai
     *                 `updated_since` pada sync berikutnya.
     *
     * - `cursor` adalah opaque base64 (jangan diparse client, cukup disimpan & dikirim balik)
     * - Baris soft-deleted tetap dikirim dengan `is_deleted` = true agar device
     *   dapat menghapus data lokalnya
     * - `customer_name`, `outlet_name`, dan `product_name` ikut dikirim agar mobile
     *   tidak perlu join di Room
     * - Hasil diurutkan `updated_at ASC, id ASC`
     *
     * GET /api/v1/customers/exp-claims/sync?updated_since=&cursor=&limit=
     */
    public function expClaimsSync(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 500);
        $limit = max(1, min($limit, 1000));

        $updatedSince = $request->query('updated_since');
        $cursorRaw    = $request->query('cursor');

        $cursor = null;
        if (!empty($cursorRaw)) {
            $cursor = $this->decodeSyncCursor((string) $cursorRaw);

            if ($cursor === null) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Cursor tidak valid. Silakan mulai ulang sync tanpa cursor.',
                ], 422);
            }
        }

        $query = ExpRewardClaims::withTrashed()->with([
            'customer' => function ($q) {
                $q->withTrashed()->select('id', 'name');
            },
            'outlet' => function ($q) {
                $q->select('id', 'name');
            },
            'product' => function ($q) {
                $q->select('id', 'name');
            },
        ]);

        // Prioritas: cursor (keyset pagination) > updated_since (delta) > full sync
        if ($cursor !== null) {
            $query->where(function ($q) use ($cursor) {
                $q->where('updated_at', '>', $cursor['updated_at'])
                    ->orWhere(function ($q2) use ($cursor) {
                        $q2->where('updated_at', '=', $cursor['updated_at'])
                            ->where('id', '>', $cursor['id']);
                    });
            });
        } elseif (!empty($updatedSince)) {
            try {
                $since = Carbon::parse($updatedSince)->utc();
            } catch (\Throwable $e) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Format updated_since tidak valid. Gunakan ISO-8601 (contoh: 2026-09-11T10:00:00Z).',
                ], 422);
            }

            // Gunakan >= agar baris yang berubah tepat pada server_time terakhir tidak terlewat.
            // Duplikasi aman karena device melakukan upsert berdasarkan id.
            $query->where('updated_at', '>=', $since);
        }

        // Ambil limit + 1 untuk mengetahui apakah masih ada halaman berikutnya
        $rows = $query->orderBy('updated_at', 'asc')
            ->orderBy('id', 'asc')
            ->limit($limit + 1)
            ->get();

        $hasMore = $rows->count() > $limit;
        $rows    = $rows->take($limit)->values();

        $data = $rows->map(function (ExpRewardClaims $claim) {
            return [
                'id'            => $claim->id,
                'customer_id'   => $claim->customer_id,
                'customer_name' => $claim->customer?->name,
                'outlet_id'     => $claim->outlet_id,
                'outlet_name'   => $claim->outlet?->name,
                'product_id'    => $claim->product_id,
                'product_name'  => $claim->product?->name,
                'exp'           => (int) $claim->exp,
                'level_batch'   => (int) $claim->level_batch,
                'is_deleted'    => $claim->deleted_at !== null,
                'deleted_at'    => $claim->deleted_at,
                'created_at'    => $claim->created_at,
                'updated_at'    => $claim->updated_at,
            ];
        });

        $nextCursor = null;
        if ($hasMore && $rows->isNotEmpty()) {
            $last = $rows->last();
            $nextCursor = $this->encodeSyncCursor($last->updated_at, $last->id);
        }

        return response()->json([
            'status'      => 'success',
            'data'        => $data,
            'has_more'    => $hasMore,
            'next_cursor' => $nextCursor,
            'server_time' => Carbon::now()->utc()->toIso8601String(),
        ]);
    }

    /**
     * Delta sync reward_confirmations (level reward yang sudah diambil customer)
     * untuk cache lokal mobile (Room / SQLite).
     *
     * Scope data GLOBAL (semua outlet, tidak difilter per outlet), dengan dua tujuan:
     * 1. Mencegah double claim level reward: pengecekan dilakukan lokal di Room
     *    sehingga customer yang sudah ambil reward di outlet A tidak bisa ambil lagi
     *    di outlet B.
     * 2. Menampilkan riwayat reward yang sudah pernah diambil customer.
     *
     * Kunci validasi claim di mobile: `customer_id` + `reward_memberships_id` + `level_batch`.
     * Customer hanya boleh mengambil satu reward yang sama sekali per `level_batch`.
     *
     * Cara pakai:
     * 1. Sync awal  : panggil tanpa `updated_since` & tanpa `cursor`, lalu ikuti
     *                 `next_cursor` sampai `has_more` = false.
     * 2. Sync berkala: simpan `server_time` dari response terakhir, kirim sebagai
     *                 `updated_since` pada sync berikutnya.
     *
     * - `cursor` adalah opaque base64 (jangan diparse client, cukup disimpan & dikirim balik)
     * - Baris soft-deleted tetap dikirim dengan `is_deleted` = true agar device
     *   dapat menghapus data lokalnya
     * - `customer_name`, `outlet_name`, `reward_name`, dan `level_name` ikut dikirim
     *   agar mobile tidak perlu join di Room
     * - Hasil diurutkan `updated_at ASC, id ASC`
     *
     * GET /api/v1/customers/reward-confirmations/sync?updated_since=&cursor=&limit=
     */
    public function rewardConfirmationsSync(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 500);
        $limit = max(1, min($limit, 1000));

        $updatedSince = $request->query('updated_since');
        $cursorRaw    = $request->query('cursor');

        $cursor = null;
        if (!empty($cursorRaw)) {
            $cursor = $this->decodeSyncCursor((string) $cursorRaw);

            if ($cursor === null) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Cursor tidak valid. Silakan mulai ulang sync tanpa cursor.',
                ], 422);
            }
        }

        $query = RewardConfirmation::withTrashed()->with([
            'customer' => function ($q) {
                $q->withTrashed()->select('id', 'name');
            },
            'outlet' => function ($q) {
                $q->select('id', 'name');
            },
            'user' => function ($q) {
                $q->select('id', 'name');
            },
            'levelMembership' => function ($q) {
                $q->select('id', 'name');
            },
            'rewardMembership' => function ($q) {
                $q->select('id', 'name');
            },
        ]);

        // Prioritas: cursor (keyset pagination) > updated_since (delta) > full sync
        if ($cursor !== null) {
            $query->where(function ($q) use ($cursor) {
                $q->where('updated_at', '>', $cursor['updated_at'])
                    ->orWhere(function ($q2) use ($cursor) {
                        $q2->where('updated_at', '=', $cursor['updated_at'])
                            ->where('id', '>', $cursor['id']);
                    });
            });
        } elseif (!empty($updatedSince)) {
            try {
                $since = Carbon::parse($updatedSince)->utc();
            } catch (\Throwable $e) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Format updated_since tidak valid. Gunakan ISO-8601 (contoh: 2026-09-11T10:00:00Z).',
                ], 422);
            }

            // Gunakan >= agar baris yang berubah tepat pada server_time terakhir tidak terlewat.
            // Duplikasi aman karena device melakukan upsert berdasarkan id.
            $query->where('updated_at', '>=', $since);
        }

        // Ambil limit + 1 untuk mengetahui apakah masih ada halaman berikutnya
        $rows = $query->orderBy('updated_at', 'asc')
            ->orderBy('id', 'asc')
            ->limit($limit + 1)
            ->get();

        $hasMore = $rows->count() > $limit;
        $rows    = $rows->take($limit)->values();

        $data = $rows->map(function (RewardConfirmation $confirmation) {
            $snapshot = $confirmation->snapshot;
            if (is_string($snapshot)) {
                $snapshot = json_decode($snapshot, true);
            }

            return [
                'id'                    => $confirmation->id,
                'customer_id'           => $confirmation->customer_id,
                'customer_name'         => $confirmation->customer?->name,
                'level_membership_id'   => $confirmation->level_membership_id,
                'level_membership_name' => $confirmation->levelMembership?->name,
                'reward_memberships_id' => $confirmation->reward_memberships_id,
                'reward_name'           => $confirmation->rewardMembership?->name,
                'level_batch'           => $confirmation->level_batch !== null
                    ? (int) $confirmation->level_batch
                    : null,
                'outlet_id'             => $confirmation->outlet_id,
                'outlet_name'           => $confirmation->outlet?->name,
                'user_id'               => $confirmation->user_id,
                'user_name'             => $confirmation->user?->name,
                'snapshot'              => $snapshot,
                'is_deleted'            => $confirmation->deleted_at !== null,
                'deleted_at'            => $confirmation->deleted_at,
                'created_at'            => $confirmation->created_at,
                'updated_at'            => $confirmation->updated_at,
            ];
        });

        $nextCursor = null;
        if ($hasMore && $rows->isNotEmpty()) {
            $last = $rows->last();
            $nextCursor = $this->encodeSyncCursor($last->updated_at, $last->id);
        }

        return response()->json([
            'status'      => 'success',
            'data'        => $data,
            'has_more'    => $hasMore,
            'next_cursor' => $nextCursor,
            'server_time' => Carbon::now()->utc()->toIso8601String(),
        ]);
    }

    /**
     * Encode cursor sync menjadi opaque base64 (URL-safe).
     */
    private function encodeSyncCursor($updatedAt, int $id): string
    {
        $payload = json_encode([
            'updated_at' => Carbon::parse($updatedAt)->toIso8601String(),
            'id'         => $id,
        ]);

        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    }

    /**
     * Decode cursor sync. Mengembalikan null jika tidak valid.
     */
    private function decodeSyncCursor(string $cursor): ?array
    {
        $normalized = strtr($cursor, '-_', '+/');
        $padding    = strlen($normalized) % 4;
        if ($padding > 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($normalized, true);
        if ($decoded === false) {
            return null;
        }

        $payload = json_decode($decoded, true);
        if (!is_array($payload) || !isset($payload['updated_at'], $payload['id'])) {
            return null;
        }

        try {
            // toIso8601String() sudah memuat offset timezone, jadi TIDAK boleh
            // dipanggil ->utc() lagi (akan menggeser waktu dua kali).
            $updatedAt = Carbon::parse($payload['updated_at']);
        } catch (\Throwable $e) {
            return null;
        }

        return [
            'updated_at' => $updatedAt->toDateTimeString(),
            'id'         => (int) $payload['id'],
        ];
    }

    /**
     * Daftarkan member baru dari kasir Android.
     *
     * Meniru logika web CustomerController@store:
     * - Level awal = level dengan benchmark terendah
     * - Referral: referrer mendapat bonus 75 poin + dicatat di customer_poin_exps
     * - History level awal dibuat (HistoryExpMembershipLevel)
     * - Email registrasi dikirim dalam try/catch agar tidak menggagalkan pendaftaran
     *
     * POST /api/v1/customers
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'umur'          => ['required', 'integer'],
            'telfon'        => ['required', 'string', 'max:20', 'regex:/^\+?[0-9\-]+$/', Rule::unique('customers', 'telfon')],
            'email'         => ['required', 'string', 'max:254', 'email:rfc', Rule::unique('customers', 'email')],
            'tanggal_lahir' => ['required', 'date'],
            'domisili'      => ['required', 'string', 'max:255'],
            'gender'        => ['required', Rule::in(['laki-laki', 'perempuan'])],
            'community_id'  => ['nullable', 'integer', 'exists:communities,id'],
            'referral_id'   => ['nullable', 'integer', 'exists:customers,id'],
        ]);

        $level = LevelMembership::orderBy('benchmark', 'asc')->first();

        if (!$level) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Level membership belum diatur.',
            ], 422);
        }

        $user = $request->user();

        $customer = DB::transaction(function () use ($request, $user, $level) {
            $customer = Customer::create(array_merge(
                $request->only([
                    'name', 'umur', 'telfon', 'email',
                    'tanggal_lahir', 'domisili', 'gender',
                    'community_id', 'referral_id',
                ]),
                [
                    'level_memberships_id' => $level->id,
                    'user_id'              => $user->id,
                ]
            ));

            // Bonus referral: referrer mendapat 75 poin
            if ($request->filled('referral_id')) {
                CustomerReferral::create([
                    'customer_id' => $customer->id,
                    'referral_id' => $request->input('referral_id'),
                    'user_id'     => $user->id,
                ]);

                $referrer = Customer::find($request->input('referral_id'));
                if ($referrer) {
                    $referrer->increment('point', 75);

                    CustomerPoinExp::create([
                        'customer_id' => $referrer->id,
                        'point'       => 75,
                        'referee_id'  => $customer->id,
                        'log'         => 'mendapatkan poin dari referee sebesar 75 poin',
                    ]);
                }
            }

            // Catat history level awal member
            HistoryExpMembershipLevel::create([
                'customer_id'          => $customer->id,
                'level_memberships_id' => $level->id,
                'exp'                  => $level->benchmark,
            ]);

            return $customer;
        });

        // Email registrasi & komunitas — dibungkus try/catch agar mail
        // yang gagal (mis. alamat email tidak valid) tidak menggagalkan pendaftaran.
        try {
            if ($request->filled('community_id')) {
                $community = $customer->community;

                Mail::to($customer->email)->send(new RegistrasiMembershipKomunitas([
                    'name'          => $customer->name,
                    'namaKomunitas' => $community?->name,
                    'poin'          => 0,
                    'exp'           => 0,
                    'expCommunity'  => $community?->exp,
                    'expired'       => Carbon::parse($customer->created_at)->addYear()->format('d-m-Y'),
                ]));
            }

            Mail::to($customer->email)->send(new CustomerRegistered([
                'name'         => $customer->name,
                'email'        => $customer->email,
                'level_member' => $level->name,
                'expired'      => Carbon::parse($customer->created_at)->addYear()->format('d-m-Y'),
            ]));
        } catch (\Throwable $e) {
            Log::warning('Gagal kirim email registrasi customer #' . $customer->id . ': ' . $e->getMessage());
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer berhasil didaftarkan.',
            'data'    => [
                'id'                   => $customer->id,
                'name'                 => $customer->name,
                'phone'                => $customer->telfon,
                'point'                => (int) $customer->point,
                'exp'                  => (int) $customer->exp,
                'level_memberships_id' => (int) $customer->level_memberships_id,
                'level'                => [
                    'id'    => $level->id,
                    'name'  => $level->name,
                    'color' => $level->color,
                ],
                'created_at'           => $customer->created_at?->format('d M Y'),
            ],
        ]);
    }

    /**
     * Daftar member untuk dropdown referral di form "Tambah Member".
     *
     * - Cukup id, nama, no. HP (padanan select referral di web)
     * - Bisa dicari berdasarkan nama atau no. HP
     *
     * GET /api/v1/customers/referrals?q={search}&limit={n}
     */
    public function referrals(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $limit  = min((int) $request->query('limit', 50), 200);

        $customers = Customer::when($search !== '', function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('telfon', 'like', "%{$search}%");
        })
            ->orderBy('name', 'asc')
            ->take($limit)
            ->get(['id', 'name', 'telfon']);

        $data = $customers->map(function ($customer) {
            return [
                'id'    => $customer->id,
                'name'  => $customer->name,
                'phone' => $customer->telfon,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    /**
     * Ambil detail satu customer beserta info membership & reward.
     *
     * - Profil lengkap customer
     * - Poin, EXP, level saat ini, dan progress menuju level berikutnya
     * - Komunitas, referrer, dan total transaksi
     * - Reward: birthday reward, EXP milestone reward, level rewards
     *
     * GET /api/v1/customers/{id}
     */
    public function show(Request $request, $id): JsonResponse
    {
        $outletIds = $request->user()->outletIds();

        if (empty($outletIds)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak memiliki outlet yang terdaftar.',
            ], 422);
        }

        $outletId = $outletIds[0];

        $customer = Customer::with([
            'levelMembership' => function ($query) use ($outletId) {
                $query->with(['rewards' => function ($rewards) use ($outletId) {
                    $rewards->with(['rewardProduct' => function ($rp) use ($outletId) {
                        $rp->where('outlet_id', $outletId)
                            ->select('id', 'reward_membership_id', 'product_id');
                    }])->select('id', 'level_membership_id', 'name', 'description', 'icon');
                }]);
            },
            'rewardConfirmations' => function ($query) {
                $query->with(['outlet' => fn ($o) => $o->select('id', 'name')])
                    ->select('id', 'reward_memberships_id', 'level_batch', 'customer_id', 'outlet_id', 'created_at');
            },
            'community',
            'referral',
        ])
        ->withCount('transactions')
        ->find($id);

        if (!$customer) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Customer tidak ditemukan.',
            ], 404);
        }

        $levels = LevelMembership::where('is_active', true)
            ->orderBy('benchmark', 'asc')
            ->get(['id', 'name', 'benchmark', 'color']);

        $current = $levels->firstWhere('id', $customer->level_memberships_id);
        $next    = $levels->first(fn ($level) => $level->benchmark > (int) $customer->exp);

        $progress = null;
        if ($current && $next && $next->benchmark > $current->benchmark) {
            $base  = $next->benchmark - $current->benchmark;
            $delta = (int) $customer->exp - $current->benchmark;
            $progress = $base > 0 ? min(max((int) round(($delta / $base) * 100), 0), 100) : 0;
        }

        $today = Carbon::today();
        $tanggalLahir = $customer->tanggal_lahir ? Carbon::parse($customer->tanggal_lahir) : null;

        // ---- Birthday reward ----
        $canClaimBirthday = false;
        if ($tanggalLahir && $customer->created_at && $customer->created_at->diffInDays(now()) >= 30) {
            $canClaimBirthday = $this->isBirthdayInRange($today, $tanggalLahir);
        }

        $nowAge = $tanggalLahir ? $tanggalLahir->age : null;

        $checkClaimBirthday = null;
        if ($nowAge !== null) {
            $claimBirthday = BirthdayRewardClaims::with('outlet:id,name')
                ->where('customer_id', $customer->id)
                ->where('age', $nowAge)
                ->first();

            if ($claimBirthday) {
                $checkClaimBirthday = [
                    'created_at' => $claimBirthday->created_at->format('d M Y H:i'),
                    'outlet'     => $claimBirthday->outlet
                        ? ['id' => $claimBirthday->outlet->id, 'name' => $claimBirthday->outlet->name]
                        : null,
                ];
            }
        }

        $rewardBirthday = ProductBirthdayReward::with('product')
            ->where('outlet_id', $outletId)
            ->first();
        $birthdayProduct = $rewardBirthday?->product;

        // ---- EXP milestone reward ----
        $exp = (int) $customer->exp;
        $claimableExp = $exp >= 5000 ? intdiv($exp, 5000) * 5000 : 0;

        $rewardExp = ProductExpReward::with('product')
            ->where('outlet_id', $outletId)
            ->first();
        $expProduct = $rewardExp?->product;

        $checkClaimExp = null;
        if ($exp >= 5000) {
            $claimExp = ExpRewardClaims::with('outlet:id,name')
                ->where('customer_id', $customer->id)
                ->where('exp', $claimableExp)
                ->where('level_batch', $customer->level_batch)
                ->first();

            if ($claimExp) {
                $checkClaimExp = [
                    'created_at' => $claimExp->created_at->format('d M Y H:i'),
                    'outlet'     => $claimExp->outlet
                        ? ['id' => $claimExp->outlet->id, 'name' => $claimExp->outlet->name]
                        : null,
                ];
            }
        }

        // ---- Level rewards ----
        $levelRewards = collect();
        if ($customer->levelMembership) {
            $levelRewards = $customer->levelMembership->rewards
                ->map(function ($reward) use ($customer) {
                    $confirmation = $customer->rewardConfirmations->first(
                        fn ($rc) => $rc->reward_memberships_id == $reward->id
                            && $rc->level_batch == $customer->level_batch
                            && $rc->customer_id == $customer->id
                    );

                    return [
                        'reward_id'   => $reward->id,
                        'name'        => $reward->name,
                        'description' => $reward->description,
                        'icon'        => $reward->icon,
                        'product_id'  => $reward->rewardProduct->first()?->product_id,
                        'claim'       => $confirmation
                            ? [
                                'id'          => $confirmation->id,
                                'level_batch' => $confirmation->level_batch,
                                'created_at'  => Carbon::parse($confirmation->created_at)->format('d M Y H:i'),
                                'outlet'      => $confirmation->outlet
                                    ? ['id' => $confirmation->outlet->id, 'name' => $confirmation->outlet->name]
                                    : null,
                            ]
                            : null,
                    ];
                })
                ->values();
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'               => $customer->id,
                'name'             => $customer->name,
                'phone'            => $customer->telfon,
                'email'            => $customer->email,
                'umur'             => $customer->umur,
                'tanggal_lahir'    => $customer->tanggal_lahir,
                'domisili'         => $customer->domisili,
                'gender'           => $customer->gender,
                'point'            => (int) $customer->point,
                'exp'              => $exp,
                'level_batch'      => (int) $customer->level_batch,
                'level'            => $current
                    ? [
                        'id'        => $current->id,
                        'name'      => $current->name,
                        'benchmark' => (int) $current->benchmark,
                        'color'     => $current->color,
                    ]
                    : null,
                'next_level'       => $next
                    ? [
                        'id'        => $next->id,
                        'name'      => $next->name,
                        'benchmark' => (int) $next->benchmark,
                        'color'     => $next->color,
                    ]
                    : null,
                'progress_to_next_level' => $progress,
                'community'        => $customer->community
                    ? [
                        'id'   => $customer->community->id,
                        'name' => $customer->community->name,
                    ]
                    : null,
                'referral'         => $customer->referral
                    ? [
                        'id'   => $customer->referral->id,
                        'name' => $customer->referral->name,
                    ]
                    : null,
                'total_transaction_count' => (int) $customer->transactions_count,
                'birthday_reward'  => [
                    'can_claim'    => $canClaimBirthday,
                    'period_claim' => $tanggalLahir
                        ? $tanggalLahir->format('d M') . ' - ' . $tanggalLahir->copy()->addMonths(1)->format('d M')
                        : null,
                    'product_id'   => $rewardBirthday?->product_id,
                    'product'      => $birthdayProduct
                        ? [
                            'id'          => $birthdayProduct->id,
                            'name'        => $birthdayProduct->name,
                            'photo'       => $birthdayProduct->photo,
                            'description' => $birthdayProduct->description,
                        ]
                        : null,
                    'claim'        => $checkClaimBirthday,
                ],
                'exp_reward'       => [
                    'can_claim'     => $rewardExp && $exp >= 5000,
                    'claimable_exp' => $claimableExp,
                    'product_id'    => $rewardExp?->product_id,
                    'product'       => $expProduct
                        ? [
                            'id'          => $expProduct->id,
                            'name'        => $expProduct->name,
                            'photo'       => $expProduct->photo,
                            'description' => $expProduct->description,
                        ]
                        : null,
                    'claim'         => $checkClaimExp,
                ],
                'level_rewards'    => $levelRewards,
            ],
        ]);
    }

    /**
     * Cek apakah tanggal lahir customer berada dalam periode klaim ulang tahun
     * (1 bulan sebelum & 1 bulan setelah ulang tahun), mengikuti logika web.
     */
    private function isBirthdayInRange(Carbon $today, Carbon $birth): bool
    {
        $birthdayThisYear = $birth->copy()->year($today->year);
        $endDateThisYear = $birthdayThisYear->copy()->addMonths();
        $endDateThisYear->endOfDay();

        $birthdayLastYear = $birthdayThisYear->copy()->subYear();
        $endDateLastYear = $birthdayLastYear->copy()->addMonth();
        $endDateLastYear->endOfDay();

        return $today->between($birthdayThisYear, $endDateThisYear)
            || $today->between($birthdayLastYear, $endDateLastYear);
    }
}
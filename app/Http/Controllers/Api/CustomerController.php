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
use App\Models\ProductBirthdayReward;
use App\Models\ProductExpReward;
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
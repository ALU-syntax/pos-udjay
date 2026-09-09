<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\BerandaMember;
use App\Mail\KenaikanLevelMember;
use App\Mail\PenambahanPoinMembershipKomunitas;
use App\Mail\PenambahanPointExpMembership;
use App\Mail\PenukaranPoin;
use App\Models\BirthdayRewardClaims;
use App\Models\Community;
use App\Models\Customer;
use App\Models\ExpRewardClaims;
use App\Models\HistoryExpMembershipLevel;
use App\Models\LevelMembership;
use App\Models\NoteReceiptScheduling;
use App\Models\OpenBill;
use App\Models\PettyCash;
use App\Models\RewardConfirmation;
use App\Models\RewardMembership;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\VariantProduct;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Jenssegers\Agent\Agent;

class TransactionController extends Controller
{
    /**
     * Endpoint bayar / checkout untuk aplikasi mobile (Android/iOS POS).
     *
     * Fitur Utama:
     * 1. Mendukung koneksi latar belakang / offline sync (terima `reference_id` / UUID & custom `created_at`).
     * 2. Idempotency: Jika `reference_id` sudah ada di database atau sedang diproses, kembalikan transaksi yang ada (mencegah double payment).
     * 3. Clean JSON structure: `items` sebagai array of objects.
     * 4. Integrasi OpenBill (penanganan split bill dan full pay).
     * 5. Kalkulasi Pajak, Diskon, Modifier, Customer Loyalty (Poin, Exp, Membership Level Up, Mail notification).
     * 6. Pengurangan Stok VariantProduct.
     * 7. Respons Struk Lengkap untuk dicetak oleh mobile.
     *
     * POST /api/v1/transactions/pay
     */
    public function pay(Request $request): JsonResponse
    {
        $user = $request->user();
        $outletIds = $user->outletIds();

        if (empty($outletIds)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak memiliki outlet yang terdaftar.',
            ], 422);
        }

        $outletId = $outletIds[0];

        // Validasi Payload Clean JSON
        $validated = $request->validate([
            'reference_id'           => ['nullable', 'string', 'max:100'],
            'patty_cash_id'          => ['required', 'integer'],
            'customer_id'            => ['nullable', 'integer'],
            'bill_id'                => ['nullable', 'integer'],
            'split_bill'             => ['nullable', 'boolean'],
            'total'                  => ['required', 'numeric', 'min:0'],
            'nominal_bayar'          => ['required', 'numeric', 'min:0'],
            'change'                 => ['required', 'numeric'],
            'category_payment_id'    => ['nullable', 'integer'],
            'tipe_pembayaran'        => ['nullable', 'integer'],
            'nama_tipe_pembayaran'   => ['nullable', 'string', 'max:100'],
            'total_pajak'            => ['nullable'], // json string or array
            'diskon_all_item'        => ['nullable'], // json string or array
            'rounding'               => ['nullable', 'numeric'],
            'tanda_rounding'         => ['nullable', 'string', 'max:10'],
            'potongan_point'         => ['nullable', 'numeric', 'min:0'],
            'catatan_transaksi'      => ['nullable', 'string'],
            'created_at'             => ['nullable', 'date'],

            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['nullable', 'integer'],
            'items.*.variant_id'     => ['nullable', 'integer'],
            'items.*.harga'          => ['required', 'numeric', 'min:0'],
            'items.*.quantity'       => ['nullable', 'integer', 'min:1'],
            'items.*.catatan'        => ['nullable', 'string'],
            'items.*.sales_type_id'  => ['nullable', 'integer'],
            'items.*.promo_id'       => ['nullable', 'integer'],
            'items.*.reward'         => ['nullable', 'boolean'],
            'items.*.tmp_id'         => ['nullable', 'string'],
            'items.*.discount_id'    => ['nullable'], // array or json string
            'items.*.modifier_id'    => ['nullable'], // array or json string
        ]);

        // 1. Cek Idempotency berdasarkan reference_id
        $referenceId = $validated['reference_id'] ?? null;
        if ($referenceId) {
            $existingTransaction = Transaction::where('outlet_id', $outletId)
                ->where('reference_id', $referenceId)
                ->first();

            if ($existingTransaction) {
                return $this->buildSuccessResponse($existingTransaction, "Transaksi sudah pernah diproses.");
            }

            // Lock cache temporary untuk mencegah race condition request paralel
            $cacheKey = 'mobile_pay_lock_' . md5($outletId . '_' . $referenceId);
            if (Cache::has($cacheKey)) {
                return response()->json([
                    'status'  => 'warning',
                    'message' => 'Transaksi sedang diproses di server. Silakan tunggu.',
                ], 429);
            }
            Cache::put($cacheKey, true, now()->addSeconds(30));
        }

        // Jalankan transaksi dalam DB Transaction
        try {
            $transactionResult = DB::transaction(function () use ($validated, $user, $outletId, $referenceId) {

                $billId = isset($validated['bill_id']) && (int)$validated['bill_id'] !== 0 ? (int)$validated['bill_id'] : null;
                $isSplitBill = filter_var($validated['split_bill'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $listIdItemOpenBill = [];

                // 2. Penanganan Open Bill jika bill_id ada
                if ($billId) {
                    $bill = OpenBill::with(['item'])->where('id', $billId)->where('outlet_id', $outletId)->first();

                    if ($bill) {
                        if ($isSplitBill) {
                            // Hitung frekuensi kemunculan item dari payload (berdasarkan tmp_id atau product_id/variant_id)
                            $countsItem = [];
                            foreach ($validated['items'] as $itemPayload) {
                                $tmpId = $itemPayload['tmp_id'] ?? null;
                                $qty = $itemPayload['quantity'] ?? 1;
                                if ($tmpId) {
                                    $countsItem[$tmpId] = ($countsItem[$tmpId] ?? 0) + $qty;
                                }
                            }

                            foreach ($bill->item as $billItem) {
                                if (isset($countsItem[$billItem->tmp_id])) {
                                    $mergeQty = $countsItem[$billItem->tmp_id];
                                    for ($x = 0; $x < $mergeQty; $x++) {
                                        $listIdItemOpenBill[] = $billItem->id;
                                    }

                                    $itemQty = (int) $billItem->quantity;
                                    $itemTerbayar = $billItem->qty_terbayar ? (int)$billItem->qty_terbayar : 0;

                                    if ($itemQty > $mergeQty) {
                                        $newQty = $itemQty - $mergeQty;
                                        $billItem->quantity = (string) $newQty;
                                        $billItem->result_total = $billItem->harga * $newQty;
                                        $billItem->qty_terbayar = $itemTerbayar + $mergeQty;
                                        $billItem->save();
                                    } else {
                                        $billItem->delete();
                                    }
                                }
                            }
                        } else {
                            // Full pay dari Open Bill
                            foreach ($bill->item as $itemBill) {
                                for ($i = 0; $i < intval($itemBill->quantity); $i++) {
                                    $listIdItemOpenBill[] = $itemBill->id;
                                }
                            }
                            $bill->item()->delete();
                            $bill->delete();
                        }
                    }
                }

                // 3. Normalisasi & Hitung totalModifier & totalDiskon
                $hargaModifier = 0;
                $totalNominalDiskon = 0;

                foreach ($validated['items'] as $itemPayload) {
                    $qty = $itemPayload['quantity'] ?? 1;
                    
                    // Modifier calculation
                    $modifierData = $itemPayload['modifier_id'] ?? [];
                    if (is_string($modifierData)) {
                        $modifierData = json_decode($modifierData, true) ?: [];
                    }
                    if (is_array($modifierData)) {
                        foreach ($modifierData as $mod) {
                            if (is_array($mod) && isset($mod['harga'])) {
                                $hargaModifier += (float) $mod['harga'] * $qty;
                            } elseif (is_object($mod) && isset($mod->harga)) {
                                $hargaModifier += (float) $mod->harga * $qty;
                            }
                        }
                    }

                    // Item discount calculation
                    $discountData = $itemPayload['discount_id'] ?? [];
                    if (is_string($discountData)) {
                        $discountData = json_decode($discountData, true) ?: [];
                    }
                    if (is_array($discountData)) {
                        foreach ($discountData as $disc) {
                            if (is_array($disc) && isset($disc['result'])) {
                                $totalNominalDiskon += (float) $disc['result'];
                            } elseif (is_object($disc) && isset($disc->result)) {
                                $totalNominalDiskon += (float) $disc->result;
                            }
                        }
                    }
                }

                // All item discount calculation
                $diskonAllItemsPayload = $validated['diskon_all_item'] ?? [];
                if (is_string($diskonAllItemsPayload)) {
                    $diskonAllItemsPayload = json_decode($diskonAllItemsPayload, true) ?: [];
                }
                if (is_array($diskonAllItemsPayload)) {
                    foreach ($diskonAllItemsPayload as $discAll) {
                        if (is_array($discAll) && isset($discAll['value'])) {
                            $totalNominalDiskon += (float) $discAll['value'];
                        } elseif (is_object($discAll) && isset($discAll->value)) {
                            $totalNominalDiskon += (float) $discAll->value;
                        }
                    }
                }

                // 4. Customer & Loyalty Calculation
                $customerId = $validated['customer_id'] ?? null;
                $umurCustomer = 0;
                $customerLevelMembershipId = '';
                $customerLevelbatch = '';
                $customerClaimableExp = '';

                if ($customerId) {
                    $customer = Customer::find($customerId);
                    if ($customer) {
                        $customerExp = $customer->exp;
                        $customerClaimableExp = intdiv((int)$customerExp, 5000) * 5000;
                        $customerLevelMembershipId = $customer->level_memberships_id;
                        $customerLevelbatch = $customer->level_batch;

                        if ($customer->tanggal_lahir) {
                            $birthCarbon = Carbon::parse($customer->tanggal_lahir);
                            $umurCustomer = $birthCarbon->age;
                        }

                        // Hitung pajak terhitung
                        $pajakTerhitung = 0;
                        $totalPajakPayload = $validated['total_pajak'] ?? [];
                        if (is_string($totalPajakPayload)) {
                            $totalPajakPayload = json_decode($totalPajakPayload, true) ?: [];
                        }
                        if (is_array($totalPajakPayload) && count($totalPajakPayload) && (float)$validated['total'] > 0) {
                            foreach ($totalPajakPayload as $pj) {
                                $pajakTerhitung += is_array($pj) ? ($pj['total'] ?? 0) : ($pj->total ?? 0);
                            }
                        }

                        // Potongan Poin
                        $potonganPoint = (float)($validated['potongan_point'] ?? 0);
                        if ($potonganPoint > 0) {
                            $customer->point -= $potonganPoint;

                            $dataEmailPointUse = [
                                'name'            => $customer->name,
                                'pointDigunakan'  => formatRupiah(strval($potonganPoint), ""),
                                'point'           => floor($customer->point),
                                'exp'             => floor($customer->exp),
                                'potongan'        => formatRupiah(strval($potonganPoint), "Rp. "),
                                'expired'         => Carbon::parse($customer->created_at)->addYear()->format('d-m-Y'),
                                'levelMembership' => $customer->levelMembership?->name ?? '-',
                            ];
                            if (!empty($customer->email)) {
                                Mail::to($customer->email)->send(new PenukaranPoin($dataEmailPointUse));
                            }
                        }

                        // Poin & Exp Baru
                        $pointExpKurangPajak = (float)$validated['total'] - (float)$pajakTerhitung;
                        $pointExp = $pointExpKurangPajak / 100;
                        $pointExpDidapat = floor($pointExp);

                        $customer->point += $pointExp;
                        $customer->exp += $pointExp;

                        $formatRupiahPoint = formatRupiah(strval(floor($customer->point)), "");
                        $formatExp = formatRupiah(strval(floor($customer->exp)), "");
                        $formatPointDidapat = formatRupiah(strval($pointExpDidapat), "");

                        $dataEmail = [
                            'name'            => $customer->name,
                            'exp'             => $formatExp,
                            'nominalExp'      => floor($customer->exp),
                            'point'           => $formatRupiahPoint,
                            'pointDidapat'    => $formatPointDidapat,
                            'levelMembership' => $customer->levelMembership?->name ?? '-',
                            'expired'         => Carbon::parse($customer->created_at)->addYear()->format('d-m-Y')
                        ];
                        if (!empty($customer->email)) {
                            Mail::to($customer->email)->send(new PenambahanPointExpMembership($dataEmail));
                        }

                        $levelNameMember = $customer->levelMembership?->name ?? '-';

                        if (isset($customer->community_id)) {
                            $community = Community::find($customer->community_id);
                            if ($community) {
                                $community->exp += intval($pointExpKurangPajak) / 100;
                                $community->save();

                                $dataPointCommunity = [
                                    'name'          => $customer->name,
                                    'namaKomunitas' => $community->name,
                                    'poin'          => $customer->point,
                                    'exp'           => $customer->exp,
                                    'expCommunity'  => $community->exp,
                                    'expired'       => Carbon::parse($customer->created_at)->addYear()->format('d-m-Y')
                                ];
                                if (!empty($customer->email)) {
                                    Mail::to($customer->email)->send(new PenambahanPoinMembershipKomunitas($dataPointCommunity));
                                }
                            }
                        }

                        // Level membership benchmark check
                        $listLevelMembership = LevelMembership::all();
                        foreach ($listLevelMembership as $index => $level) {
                            $exists = HistoryExpMembershipLevel::where('customer_id', $customer->id)
                                ->where('level_memberships_id', $level->id)
                                ->exists();

                            if (!$exists) {
                                if (intval($customer->exp) >= $level->benchmark) {
                                    if ($customer->level_memberships_id != $level->id) {
                                        $dataEmailLevelUp = [
                                            'name'               => $customer->name,
                                            'exp'                => $formatExp,
                                            'nominalExp'         => floor($customer->exp),
                                            'reward'             => $level->rewards()->get(),
                                            'levelMembership'    => $customer->levelMembership?->name ?? '-',
                                            'levelMembershipNow' => $level->name,
                                            'expired'            => Carbon::parse($customer->created_at)->addYear()->format('d-m-Y'),
                                        ];

                                        $levelNameMember = $level->name;

                                        if (($index + 1) >= count($listLevelMembership)) {
                                            $dataEmailLevelUp['nextMember'] = "-";
                                        } else {
                                            $dataEmailLevelUp['nextMember'] = $listLevelMembership[$index + 1]->name;
                                        }
                                        if (!empty($customer->email)) {
                                            Mail::to($customer->email)->send(new KenaikanLevelMember($dataEmailLevelUp));
                                        }

                                        $customer->level_memberships_id = $level->id;

                                        $historyMembership = new HistoryExpMembershipLevel([
                                            'customer_id'          => $customer->id,
                                            'level_memberships_id' => $level->id,
                                            'exp'                  => floor($customer->exp),
                                        ]);
                                        $historyMembership->save();
                                        break;
                                    }
                                }
                            }
                        }

                        $dataEmailBeranda = [
                            'name'            => $customer->name,
                            'exp'             => $formatExp,
                            'nominalExp'      => floor($customer->exp),
                            'point'           => $formatRupiahPoint,
                            'pointDidapat'    => $formatPointDidapat,
                            'levelMembership' => $levelNameMember,
                            'expired'         => Carbon::parse($customer->created_at)->addYear()->format('d-m-Y')
                        ];
                        if (!empty($customer->email)) {
                            Mail::to($customer->email)->send(new BerandaMember($dataEmailBeranda));
                        }

                        $customer->save();
                    }
                }

                $transactionTime = isset($validated['created_at']) ? Carbon::parse($validated['created_at']) : Carbon::now();

                $totalPajakJson = is_array($validated['total_pajak'] ?? null)
                    ? json_encode($validated['total_pajak'])
                    : ($validated['total_pajak'] ?? null);

                $diskonAllItemJson = is_array($validated['diskon_all_item'] ?? null)
                    ? json_encode($validated['diskon_all_item'])
                    : ($validated['diskon_all_item'] ?? '[]');

                // 5. Create Transaction Record
                $dataTransaction = [
                    'outlet_id'            => $outletId,
                    'user_id'              => $user->id,
                    'customer_id'          => $customerId,
                    'total'                => $validated['total'],
                    'nominal_bayar'        => $validated['nominal_bayar'],
                    'category_payment_id'  => $validated['category_payment_id'] ?? 1,
                    'nama_tipe_pembayaran' => $validated['nama_tipe_pembayaran'] ?? 'Cash',
                    'change'               => $validated['change'],
                    'tipe_pembayaran'      => $validated['tipe_pembayaran'] ?? null,
                    'total_pajak'          => $totalPajakJson,
                    'total_modifier'       => $hargaModifier,
                    'total_diskon'         => $totalNominalDiskon,
                    'diskon_all_item'      => $diskonAllItemJson,
                    'rounding_amount'      => $validated['rounding'] ?? 0,
                    'tanda_rounding'       => $validated['tanda_rounding'] ?? null,
                    'patty_cash_id'        => $validated['patty_cash_id'],
                    'catatan'              => $validated['catatan_transaksi'] ?? null,
                    'potongan_point'       => (int)($validated['potongan_point'] ?? 0),
                    'reference_id'         => $referenceId,
                    'receipt_number'       => null,
                    'created_at'           => $transactionTime,
                    'updated_at'           => Carbon::now(),
                    'open_bill_id'         => $billId
                ];

                $transaction = Transaction::create($dataTransaction);

                // 6. Insert Transaction Items & Stok Decrement
                $itemIndexCounter = 0;
                foreach ($validated['items'] as $itemPayload) {
                    $qty = isset($itemPayload['quantity']) ? max(1, (int)$itemPayload['quantity']) : 1;
                    $idProduct = $itemPayload['product_id'] ?? null;
                    $variantId = $itemPayload['variant_id'] ?? null;

                    if ($variantId) {
                        VariantProduct::whereKey($variantId)->decrement('stok', $qty);
                    }

                    $checkCatatan = $itemPayload['catatan'] ?? '';
                    $discountIdJson = is_array($itemPayload['discount_id'] ?? null) 
                        ? json_encode($itemPayload['discount_id']) 
                        : ($itemPayload['discount_id'] ?? '[]');
                    $modifierIdJson = is_array($itemPayload['modifier_id'] ?? null) 
                        ? json_encode($itemPayload['modifier_id']) 
                        : ($itemPayload['modifier_id'] ?? '[]');
                    $promoIdJson = is_array($itemPayload['promo_id'] ?? null) 
                        ? json_encode($itemPayload['promo_id']) 
                        : (is_numeric($itemPayload['promo_id'] ?? null) ? (string)$itemPayload['promo_id'] : '[]');
                    $isReward = filter_var($itemPayload['reward'] ?? false, FILTER_VALIDATE_BOOLEAN);

                    // Replikasi baris item berdasarkan quantity untuk konsistensi dengan schema KasirController
                    for ($q = 0; $q < $qty; $q++) {
                        $dataProduct = [
                            'product_id'      => $idProduct,
                            'discount_id'     => $discountIdJson,
                            'modifier_id'     => $modifierIdJson,
                            'harga'           => $itemPayload['harga'],
                            'variant_id'      => $variantId,
                            'promo_id'        => $promoIdJson,
                            'reward_item'     => $isReward ? 1 : 0,
                            'transaction_id'  => $transaction->id,
                            'catatan'         => $checkCatatan,
                            'sales_type_id'   => $itemPayload['sales_type_id'] ?? null,
                            'created_at'      => $transactionTime,
                            'updated_at'      => Carbon::now(),
                        ];

                        if ($billId && count($listIdItemOpenBill)) {
                            $dataProduct['item_open_bill_id'] = $listIdItemOpenBill[$itemIndexCounter] ?? $billId;
                        }

                        if ($customerId) {
                            if ($checkCatatan === "Birthday Reward" && $umurCustomer != 0) {
                                BirthdayRewardClaims::create([
                                    'customer_id' => (int) $customerId,
                                    'outlet_id'   => (int) $outletId,
                                    'product_id'  => (int) $idProduct,
                                    'age'         => (int) $umurCustomer
                                ]);
                            }

                            if ($checkCatatan === "Level Reward" && $idProduct) {
                                $dataRewardMembership = RewardMembership::join('products', 'products.name', '=', 'reward_memberships.name')
                                    ->join('level_memberships', 'level_memberships.id', '=', 'reward_memberships.level_membership_id')
                                    ->where('products.id', $idProduct)
                                    ->where('reward_memberships.level_membership_id', $customerLevelMembershipId)
                                    ->select(
                                        'reward_memberships.id as reward_id',
                                        'products.id as product_id',
                                        'products.name as product_name',
                                        'level_memberships.id as level_id',
                                        'level_memberships.name as level_name'
                                    )
                                    ->first();

                                if ($dataRewardMembership) {
                                    $dataSnapshot = [
                                        'product_id'            => (int) $dataRewardMembership->product_id,
                                        'product_name'          => $dataRewardMembership->product_name,
                                        'level_membership_id'   => (int) $dataRewardMembership->level_id,
                                        'level_membership_name' => $dataRewardMembership->level_name,
                                    ];

                                    RewardConfirmation::insert([
                                        'level_membership_id'  => (int) $customerLevelMembershipId,
                                        'reward_memberships_id' => $dataRewardMembership->reward_id ?? 0,
                                        'customer_id'           => (int) $customerId,
                                        'user_id'               => $user->id,
                                        'outlet_id'             => (int) $outletId,
                                        'snapshot'              => json_encode($dataSnapshot),
                                        'level_batch'           => (int) $customerLevelbatch,
                                        'created_at'            => $transactionTime,
                                        'updated_at'            => Carbon::now()
                                    ]);
                                }
                            }

                            if ($checkCatatan === "Exp Reward" && $idProduct) {
                                ExpRewardClaims::create([
                                    'customer_id' => (int) $customerId,
                                    'outlet_id'   => (int) $outletId,
                                    'product_id'  => (int) $idProduct,
                                    'exp'         => (int) $customerClaimableExp,
                                    'level_batch' => (int) $customerLevelbatch
                                ]);
                            }
                        }

                        TransactionItem::insert($dataProduct);
                        $itemIndexCounter++;
                    }
                }

                return $transaction;
            });

            return $this->buildSuccessResponse($transactionResult, "Transaksi Berhasil");

        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memproses transaksi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper untuk membendung response success beserta format struk lengkap.
     */
    private function buildSuccessResponse(Transaction $transaction, string $message): JsonResponse
    {
        $transaction->load(['outlet', 'user', 'customer']);

        // Tax calculation for receipt
        $transactionPajak = $transaction->pajak();
        $checkTotalPajak = json_decode($transaction->total_pajak, true) ?: [];
        $totalPajak = 0;

        if (is_array($checkTotalPajak) && count($checkTotalPajak)) {
            foreach ($checkTotalPajak as $pajak) {
                $totalPajak += is_array($pajak) ? ($pajak['total'] ?? 0) : ($pajak->total ?? 0);
            }
        }

        $transaction['tax'] = ($totalPajak > 0) ? $transactionPajak : [];

        // Aggregated items for receipt
        $transactionItems = TransactionItem::select(
            'variant_id',
            DB::raw('COUNT(*) as total_count'),
            'product_id',
            'discount_id',
            'modifier_id',
            'promo_id',
            'sales_type_id',
            'transaction_id',
            'catatan',
            'reward_item',
            'harga'
        )
        ->with(['product', 'variant'])
        ->where('transaction_id', $transaction->id)
        ->groupBy('variant_id', 'product_id', 'discount_id', 'modifier_id', 'promo_id', 'sales_type_id', 'transaction_id', 'catatan', 'deleted_at', 'created_at', 'updated_at', 'reward_item', 'harga')
        ->get();

        foreach ($transactionItems as $transactionItem) {
            $tmpModifier = [];
            $modifierItem = $transactionItem->modifiers();
            foreach ($modifierItem as $modifier) {
                $tmpModifier[] = $modifier->name;
            }

            $transactionItem['modifier'] = $tmpModifier;
            if (!is_null($transactionItem->variant)) {
                $transactionItem['total_transaction'] = $transactionItem->total_count * $transactionItem->variant->harga;
            } else {
                $transactionItem['total_transaction'] = $transactionItem->total_count * $transactionItem->harga;
            }
        }

        $agent = new Agent();
        $device = $agent->device();

        $transactionProductIds = $transactionItems->pluck('product_id')->unique()->filter()->values()->all();
        $now = Carbon::now();
        $idOutlet = $transaction->outlet->id ?? $transaction->outlet_id;

        $dataNoteReceipt = NoteReceiptScheduling::where('status', true)
            ->whereTime('start', '<=', $now)
            ->whereTime('end', '>=', $now)
            ->where('outlet_id', $idOutlet)
            ->where(function ($query) use ($transactionProductIds) {
                $query->whereNull('product_id')
                    ->orWhere('product_id', '[]');

                if (!empty($transactionProductIds)) {
                    $query->orWhere(function ($q) use ($transactionProductIds) {
                        foreach ($transactionProductIds as $productId) {
                            $q->orWhereRaw("JSON_CONTAINS(product_id, '\"{$productId}\"')");
                        }
                    });
                }
            })
            ->get();

        $dataStruk = [
            'status'               => true,
            'transaction'          => $transaction,
            'transactionItems'     => $transactionItems,
            'user'                 => $transaction->user,
            'device'               => $device,
            'noteReceiptScheduler' => $dataNoteReceipt,
            'pelanggan'            => $transaction->customer,
        ];

        return response()->json([
            'status'    => 'success',
            'id'        => $transaction->id,
            'change'    => $transaction->change,
            'metode'    => $transaction->nama_tipe_pembayaran,
            'message'   => $message,
            'pelanggan' => $transaction->customer,
            'dataStruk' => $dataStruk
        ]);
    }
}

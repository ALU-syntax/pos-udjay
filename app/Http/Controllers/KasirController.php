<?php

namespace App\Http\Controllers;

use App\DataTables\PilihPelangganDataTable;
use App\Mail\KenaikanLevelMember;
use App\Mail\PenambahanPoinMembershipKomunitas;
use App\Mail\PenambahanPointExpMembership;
use App\Mail\PenukaranPoin;
use App\Models\Category;
use App\Models\CategoryPayment;
use App\Models\Checkout;
use App\Models\Community;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\HistoryExpMembershipLevel;
use App\Models\ItemOpenBill;
use App\Models\LevelMembership;
use App\Models\ModifierGroup;
use App\Models\Modifiers;
use App\Models\OpenBill;
use App\Models\Outlets;
use App\Models\PettyCash;
use App\Models\Product;
use App\Models\Promo;
use App\Models\SalesType;
use App\Models\Taxes;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\VariantProduct;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;
use function PHPUnit\Framework\isNull;
use Illuminate\Support\Facades\Mail;

class KasirController extends Controller
{
    public function index(Request $request)
    {
        $outletUser = auth()->user()->outlet_id;
        $dataOutletUser = json_decode($outletUser);

        $userOutletJson = auth()->user()->outlet_id;
        $userOutlet = json_decode($userOutletJson);

        $outlet = Outlets::find($userOutlet[0]);

        $diskon = Discount::where('outlet_id', '=', $userOutlet[0])->get();

        $rounding = Checkout::find(1);
        // dd($rounding);

        $pajak = Taxes::where('outlet_id', $dataOutletUser[0])->get();
        $outletUser = json_decode(auth()->user()->outlet_id);

        $promos = Promo::where('outlet_id', $userOutlet[0])->whereNull('deleted_at')->where('status', true)->get();

        $pettyCash = PettyCash::with(['userStarted', 'outlet'])->where('outlet_id', $outletUser[0])->where('close', null)->get();

        $soldItem = [];

        if (count($pettyCash)) {
            // $pettyCash[0]['user_data_started'] = auth()->user();
            $pettyCash[0]['outlet_data'] = $outlet;

            $soldItem = VariantProduct::with(['itemTransaction' => function($itemTransaction) use($pettyCash) {
                $itemTransaction->whereHas('transaction', function($transaction) use($pettyCash)  {
                    $transaction->where('patty_cash_id', $pettyCash[0]->id);
                });
            }, 'product.category'])
            ->whereHas('itemTransaction.transaction', function($transaction) use($pettyCash) {
                $transaction->where('patty_cash_id', $pettyCash[0]->id);
            })
            ->whereHas('itemTransaction', function($itemTransaction) {
                $itemTransaction->whereHas('transaction');
            })
            ->whereHas('product') // Memastikan ada relasi product
            ->get()->map(function($item) {
                $item->total_transaction = count($item->itemTransaction);

                // $totalHarga = $item->harga * count($item->itemTransaction);
                // $item->total_transaction_amount = formatRupiah(strval($totalHarga), "Rp. ");
                return $item;
            })
            ->select(['harga', 'product', 'name', 'total_transaction']);

        }

        $listCategoryPayment = CategoryPayment::with(['transactions' => function ($transaction) use ($pettyCash) {
            if (count($pettyCash)) {
                $transaction->with(['payments'])->where('patty_cash_id', $pettyCash[0]->id);
            }
        }, 'payment' => function ($payment) use ($pettyCash) {
            $payment->with(['transactions' => function ($transaction) use ($pettyCash) {
                if (count($pettyCash)) {
                    $transaction->where('patty_cash_id', $pettyCash[0]->id);
                }
            }]);
        }])->get();

        // dd($listCategoryPayment);
        return view('layouts.kasir.index', [
            'categorys' => Category::with(['products' => function ($product) use ($outletUser) {
                $product->with(['variants'])->where('outlet_id', $outletUser[0])->orderBy('name', 'asc');
            }])->get(),
            'pajak' => $pajak,
            'rounding' => $rounding,
            'promos' => $promos,
            'discounts' => $diskon,
            'pettyCash' => $pettyCash,
            'listCategoryPayment' => $listCategoryPayment,
            'soldItem' => $soldItem
        ]);
    }

    public function findProduct(Product $product)
    {
        $product->load(['variants']);
        $outletUser = auth()->user()->outlet_id;
        $dataOutletUser = json_decode($outletUser);

        $dataProduct = $product;
        $dataProduct['harga_jual'] = round($dataProduct['harga_jual']);

        $dataVariants = $dataProduct->variants()->get();

        $modifiers = $product->modifierGroups()->with(['modifier'])->get();

        $salesType = SalesType::where('outlet_id', $dataOutletUser[0])->where('status', true)->get();

        $pilihan = $product->pilihanGroups()->with(['pilihans'])->get();
        // dd($salesType);

        $discounts = Discount::where('type_input', 'fixed')->where('outlet_id', $dataOutletUser[0])->where('satuan', 'percent')->get();
        return view('layouts.kasir.kasir-modal-product', [
            'data' => $dataProduct,
            'variants' => $dataVariants,
            'discounts' => $discounts,
            'modifiers' => $modifiers,
            'salesType' => $salesType,
            'pilihans' => $pilihan,
        ]);
    }

    public function choosePayment(Request $request)
    {
        // dd($request);
        $userData = auth()->user();
        $outletUser = json_decode($userData->outlet_id);
        $listCategoryPayment = CategoryPayment::with(['payment' => function ($payment) {
            $payment->where('status', true);
        }])->where('status', true)->orderBy('name', 'asc')->get();

        $pettyCash = PettyCash::where('outlet_id', $outletUser[0])->where('close', null)->get();

        if (count($pettyCash) > 0) {
            return view('layouts.kasir.modal-choose-payment', [
                'listPayment' => $listCategoryPayment
            ]);
        } else {
            return view('layouts.kasir.modal-petty-cash');
        }
    }

    public function pattyCash(Request $request)
    {
        $userData = auth()->user();
        $outletUser = json_decode($userData->outlet_id);

        $outlet = Outlets::find($outletUser[0]);

        $data = [
            'outlet_id' => $outletUser[0],
            'amount_awal' => getAmount($request->saldo_awal),
            'user_id_started' => $userData->id,
            'open' => now()
        ];

        $dataPattyCash = PettyCash::create($data);

        $dataPattyCash['user_data_started'] = $userData;
        $dataPattyCash['outlet_data'] = $outlet;
        // $data['tipe_pembayaran'] = $;

        return response()->json([
            'status' => 'success',
            'message' => "Shift Berhasil dibuka",
            'data' => $dataPattyCash
        ]);
    }

    public function viewPattyCash()
    {
        return view('layouts.kasir.modal-petty-cash');
    }

    public function bayar(Request $request)
    {
        $outletUser = auth()->user()->outlet_id;
        $dataOutletUser = json_decode($outletUser);

        //jika split bill dari open bill
        if(($request->bill_id != "0" || $request->bill_id != 0) && $request->split_bill == true){
            $bill = OpenBill::with(['item'])->where('id', $request->bill_id)->first();

            // Hitung frekuensi kemunculan setiap tmpId
            $countsItem = array_count_values($request->tmpId);

            // Buat array hasil dengan struktur yang diinginkan
            $resultCountItem = [];

            foreach ($countsItem as $tmpId => $qty) {
                $resultCountItem[] = [
                    'tmpId' => $tmpId,
                    'qty' => $qty
                ];
            }

            foreach ($bill->item as $item) {
                // Cari data gabungan yang matching tmp_id
                $match = null;
                foreach ($resultCountItem as $data) {
                    if ($data['tmpId'] === $item['tmp_id']) {
                        $match = $data;
                        break;
                    }
                }

                if ($match) {
                    $itemQty = (int) $item['quantity'];
                    $mergeQty = (int) $match['qty'];

                    if ($itemQty > $mergeQty) {
                        $newQty = $itemQty - $mergeQty;
                        // Kurangi quantity dan update result_total
                        $item['quantity'] = (string) $newQty;
                        $item['result_total'] = $item['harga'] * $newQty;

                        $item->save();
                    }else{
                        $item->delete();
                    }
                }
            }
        }

        if (($request->bill_id != "0" || $request->bill_id != 0) && $request->split_bill == false) {
            $bill = OpenBill::with(['item'])->where('id', $request->bill_id)->first();
            $bill->item()->delete();
            $bill->delete();
        }

        $hargaModifier = 0;
        foreach ($request->modifier_id as $modifier) {
            $dataModifier = json_decode($modifier);
            if (count($dataModifier)) {
                foreach ($dataModifier as $listModifier) {
                    $hargaModifier += $listModifier->harga;
                }
            }
        }

        $totalNominalDiskon = 0;
        foreach ($request->discount_id as $discount) {
            $dataDiscount = json_decode($discount);
            if (count($dataDiscount)) {
                foreach ($dataDiscount as $itemDiscount) {
                    $bulatkanDiscount = intval($itemDiscount->result);
                    $totalNominalDiskon += $bulatkanDiscount;
                }
            }
        }

        $dataDiscountAllItem = json_decode($request->diskonAllItems);
        if (count($dataDiscountAllItem)) {
            foreach ($dataDiscountAllItem as $discountAllItemData) {
                $totalNominalDiskon += $discountAllItemData->value;
            }
        }

        $customerId = $request->customer_id == 'null' ? null : $request->customer_id;

        if ($customerId) {
            $customer = Customer::find($customerId);

            if(intval($request->potongan_point) > 0){
                $customer->point -= $request->potongan_point;

                $dataEmailPointUse = [
                    'name' => $customer->name,
                    'pointDigunakan'=> $request->potongan_point,
                    'point' => floor($customer->point),
                    'exp' => floor($customer->exp),
                    'potongan' => formatRupiah(strval($request->potongan_point), "Rp. "),
                    'expired' => Carbon::parse($customer->created_at)->addYear()->format('d-m-Y'),
                    'levelMembership' => $customer->levelMembership->name,
                ];
                Mail::to($customer->email)->send(new PenukaranPoin($dataEmailPointUse));
            }

            $customer->point += intval($request->total) / 100;
            $customer->exp += intval($request->total) / 100;

            $dataEmail = [
                'name' => $customer->name,
                'exp' => floor($customer->exp),
                'point' => floor($customer->point),
                'levelMembership' => $customer->levelMembership->name,
                'expired' => Carbon::parse($customer->created_at)->addYear()->format('d-m-Y')
            ];

            Mail::to($customer->email)->send(new PenambahanPointExpMembership($dataEmail));

            if(isset($customer->community_id)){
                $community = Community::find($customer->community_id);
                $community->exp += intval($request->total) / 100;

                $community->save();
                $dataPointCommunity = [
                    'name' => $customer->name,
                    'namaKomunitas' => $community->name,
                    'poin' => $customer->point,
                    'exp' => $customer->exp,
                    'expCommunity' => $community->exp,
                    'expired' => Carbon::parse($customer->created_at)->addYear()->format('d-m-Y')
                ];

                Mail::to($customer->email)->send(new PenambahanPoinMembershipKomunitas($dataPointCommunity));
            }

            $listLevelMembership = LevelMembership::all();

            foreach($listLevelMembership as $index => $level){
                // Cek apakah level_memberships_id ada di history_exp_membership_levels
                $exists = HistoryExpMembershipLevel::where('customer_id', $customer->id)
                ->where('level_memberships_id', $level->id)
                ->exists();

                // dd($exists, !$exists);
                if(!$exists){
                    if(intval($customer->exp) > $level->benchmark){
                        if($customer->level_memberships_id != $level->id){
                            $dataEmailLevelUp = [
                                'name' => $customer->name,
                                'exp' => floor($customer->exp),
                                'reward' => $level->rewards()->get(),
                                'levelMembership' => $customer->levelMembership->name,
                                'levelMembershipNow' => $level->name,
                                'expired' => Carbon::parse($customer->created_at)->addYear()->format('d-m-Y'),
                            ];

                            if(($index+1) >= count($listLevelMembership)){
                                $dataEmailLevelUp['nextMember'] = "-";
                            }else{
                                $dataEmailLevelUp['nextMember'] = $listLevelMembership[$index+1]->name;
                            }
                            Mail::to($customer->email)->send(new KenaikanLevelMember($dataEmailLevelUp));

                            $customer->level_memberships_id = $level->id;


                            $historyMembership = new HistoryExpMembershipLevel([
                                'customer_id' => $customer->id,
                                'level_memberships_id' => $level->id,
                                'exp' => floor($customer->exp),
                            ]);

                            $historyMembership->save();

                            break;
                        }
                    }
                }
            }

            $customer->save();
        } else {
            $customer = '';
        }

        $dataTransaction = [
            'outlet_id' => $dataOutletUser[0],
            'user_id' => auth()->user()->id,
            'customer_id' => $customerId,
            'total' => $request->total,
            'nominal_bayar' => $request->nominal_bayar,
            'category_payment_id' => $request->category_payment_id,
            'nama_tipe_pembayaran' => $request->nama_tipe_pembayaran,
            'change' => $request->change,
            'tipe_pembayaran' => $request->tipe_pembayaran == 'null' ? null : $request->tipe_pembayaran,
            'total_pajak' => $request->total_pajak,
            'total_modifier' => $hargaModifier,
            'total_diskon' => $totalNominalDiskon,
            'diskon_all_item' => $request->diskonAllItems,
            'rounding_amount' => $request->rounding,
            'tanda_rounding' => $request->tanda_rounding,
            'patty_cash_id' => $request->patty_cash_id,
            'catatan' => $request->catatan_transaksi,
            'potongan_point' => intval($request->potongan_point),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        $transaction = Transaction::create($dataTransaction);

        for ($x = 0; $x < count($request->idProduct); $x++) {
            $idProduct = $request->idProduct[$x] == 'null' ? null : intval($request->idProduct[$x]);
            $dataProduct = [
                'product_id' => $idProduct,
                'discount_id' => $request->discount_id[$x],
                'modifier_id' => $request->modifier_id[$x],
                'harga' => $request->harga[$x],
                'variant_id' => ($request->idVariant[$x] == 'null' || $request->idVariant[$x] == 'undefined') ? null : $request->idVariant[$x],
                'promo_id' => $request->promo_id[$x],
                'reward_item' => $request->reward[$x] == "true" ? true : false,
                'transaction_id' => $transaction->id,
                'catatan' => isset($request->catatan[$x]) ? $request->catatan[$x] : '',
                'sales_type_id' => ($request->sales_type[$x] == 'null' || $request->sales_type[$x] == 'undefined') ?  null : $request->sales_type[$x],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];

            TransactionItem::insert($dataProduct);
        }

        $respond = [
            'status'    => 'success',
            'id'        => $transaction->id,
            // 'waLink'    => $whatsappLink,
            'change'     => $request->change,
            'metode'    => $request->nama_tipe_pembayaran,
            'message' => "Transaksi Berhasil",
            'pelanggan' => $customer,
        ];
        // return responseSuccess(false, "Transaksi Berhasil");
        return response()->json($respond);
    }

    public function customDiskon(Discount $diskon)
    {
        return view('layouts.kasir.modal-custom-amount', [
            'data' => $diskon
        ]);
    }

    public function pilihCustomer(PilihPelangganDataTable $datatable)
    {
        return $datatable->render('layouts.kasir.modal-pilih-customer');
    }

    public function choosePromo()
    {
        return view('layouts.kasir.modal-choose-promo');
    }

    public function chooseRewardItem($queue, $idpromo)
    {
        $promo = Promo::find($idpromo);

        $reward = [];
        $dataReward = json_decode($promo->reward);
        foreach ($dataReward as $listReward) {
            $tmpReward = [];
            foreach ($listReward as $data) {
                $product = Product::with(relations: ['variants'])->where('id', $data[0])->get();
                $dataProduct = [$product[0]->id, $product[0]->name];
                $variant = [];
                if ($data[1] == 0) {
                    foreach ($product[0]->variants as $dataVariant) {
                        $tmpVariant = [$dataVariant->id, $dataVariant->name];
                        array_push($variant, $tmpVariant);
                    }
                } else {
                    $itemVariant = VariantProduct::find($data[1]);
                    $tmpVariant = [$itemVariant->id, $itemVariant->name];

                    array_push($variant, $tmpVariant);
                }
                $resultData = [$dataProduct, $variant, $data[2]];

                array_push($tmpReward, $resultData);
            }
            array_push($reward, $tmpReward);
        }

        return view('layouts.kasir.modal-choose-reward-promo', [
            'queue' => $queue,
            'dataPromo' => $promo,
            'reward' => $reward
        ]);
    }

    public function closePattyCash(Request $request)
    {
        $dataEndingCash = $request->endingCash;

        $userData = auth()->user();
        $outletUser = json_decode($userData->outlet_id);

        $outlet = PettyCash::where('outlet_id', $outletUser[0])->whereNull('close')->get();

        $outlet[0]->update([
            'amount_akhir' => getAmount($dataEndingCash),
            'close' => now(),
            'user_id_ended' => $userData->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Shift Berhasil ditutup",
        ]);
    }

    public function apiStruk($id)
    {
        $transaction = Transaction::with(['outlet', 'user'])->find($id);

        $transactionPajak = $transaction->pajak();

        $transaction['tax'] = $transactionPajak;

        // $transactionItems = TransactionItem::with(['product', 'variant'])->where('transaction_id', $id)->get();


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
        )->with(['product', 'variant'])->where('transaction_id', $id)
        ->groupBy('variant_id', 'product_id', 'discount_id', 'modifier_id', 'promo_id', 'sales_type_id', 'transaction_id', 'catatan', 'deleted_at', 'created_at', 'updated_at', 'reward_item', 'harga')
        ->orderBy('id')
        ->get();

        // dd($transactionItems);

        foreach ($transactionItems as $transactionItem) {
            $tmpModifier = [];
            $modifierItem = $transactionItem->modifiers();
            foreach ($modifierItem as $modifier) {
                array_push($tmpModifier, $modifier->name);
            }

            $transactionItem['modifier'] = $tmpModifier;
            if(!isNull($transactionItem->variant)){
                $transactionItem['total_transaction'] = $transactionItem->total_count * $transactionItem->variant->harga;
            }else{
                $transactionItem['total_transaction'] = $transactionItem->total_count * $transactionItem->harga;
            }
        }


        $user = User::find($transaction->user_id);

        $agent = new Agent();
        $device = $agent->device();

        return response()->json([
            'status' => true,
            'transaction' => $transaction,
            'transactionItems' => $transactionItems,
            'user' => $user,
            'device' => $device,
        ]);
    }

    public function viewOpenBill()
    {
        $userData = auth()->user();
        $outletUser = json_decode($userData->outlet_id);

        $pettyCash = PettyCash::where('outlet_id', $outletUser[0])->where('close', null)->get();

        if (count($pettyCash) > 0) {
            return view('layouts.kasir.modal-open-bill');
        } else {
            return view('layouts.kasir.modal-petty-cash');
        }
    }

    public function openBill(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'outlet_id' => 'required',
            'catatan' => 'array',
            'diskon' => 'array',
            'harga' => 'array',
            'idProduct' => 'array',
            'idVariant' => 'array',
            'modifier' => 'array',
            'namaProduct' => 'array',
            'namaVariant' => 'array',
            'pilihan' => 'array',
            'promo' => 'array',
            'quantity' => 'array',
            'resultTotal' => 'array',
            'salesType' => 'array',
            'tmpId' => 'array',
            'customer_id' => 'nullable',
        ]);

        // DB::transaction(function () use ($validatedData) {
        //     $dataOpenBill = [
        //         'name' => $validatedData['name'],
        //         'user_id' => auth()->user()->id,
        //         'outlet_id' => $validatedData['outlet_id'],
        //         'queue_order' => 1
        //     ];

        //     $openBill = OpenBill::create($dataOpenBill);

        //     $dataItemOpenBill = [];
        //     for ($x = 0; $x < count($validatedData['tmpId']); $x++) {
        //         $dataItemOpenBill[] = [
        //             'open_bill_id' => $openBill->id,
        //             'catatan' => $validatedData['catatan'][$x],
        //             'diskon' => $validatedData['diskon'][$x],
        //             'harga' => $validatedData['harga'][$x],
        //             'product_id' => $validatedData['idProduct'][$x],
        //             'variant_id' => $validatedData['idVariant'][$x],
        //             'modifier' => $validatedData['modifier'][$x],
        //             'nama_product' => $validatedData['namaProduct'][$x],
        //             'nama_variant' => $validatedData['namaVariant'][$x],
        //             'pilihan' => $validatedData['pilihan'][$x],
        //             'promo' => $validatedData['promo'][$x],
        //             'quantity' => $validatedData['quantity'][$x],
        //             'result_total' => $validatedData['resultTotal'][$x],
        //             'sales_type' => $validatedData['salesType'][$x],
        //             'tmp_id' => $validatedData['tmpId'][$x],
        //             'created_at' => Carbon::now(),
        //             'updated_at' => Carbon::now(),
        //             'queue_order' => 1,
        //         ];
        //     }

        //     ItemOpenBill::insert($dataItemOpenBill);

        //     return response()->json([
        //         'data' => $openBill
        //     ]);
        // });

        $dataOpenBill = [
            'name' => $validatedData['name'],
            'user_id' => auth()->user()->id,
            'outlet_id' => $validatedData['outlet_id'],
            'queue_order' => 1,
            'customer_id' => $validatedData['customer_id'],
        ];

        $openBill = OpenBill::create($dataOpenBill);

        $dataItemOpenBill = [];
        for ($x = 0; $x < count($validatedData['tmpId']); $x++) {
            $dataItemOpenBill[] = [
                'open_bill_id' => $openBill->id,
                'catatan' => $validatedData['catatan'][$x],
                'diskon' => $validatedData['diskon'][$x],
                'harga' => $validatedData['harga'][$x],
                'product_id' => $validatedData['idProduct'][$x],
                'variant_id' => $validatedData['idVariant'][$x],
                'modifier' => $validatedData['modifier'][$x],
                'nama_product' => $validatedData['namaProduct'][$x],
                'nama_variant' => $validatedData['namaVariant'][$x],
                'pilihan' => $validatedData['pilihan'][$x],
                'promo' => $validatedData['promo'][$x],
                'quantity' => $validatedData['quantity'][$x],
                'result_total' => $validatedData['resultTotal'][$x],
                'sales_type' => $validatedData['salesType'][$x],
                'tmp_id' => $validatedData['tmpId'][$x],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'queue_order' => 1,
            ];
        }

        ItemOpenBill::insert($dataItemOpenBill);

        return response()->json([
            'data' => $openBill
        ]);


        // return responseSuccess(false);

    }

    public function billList()
    {
        $userOutletJson = auth()->user()->outlet_id;
        $userOutlet = json_decode($userOutletJson);
        $listBill = OpenBill::with(['user', 'outlet'])
            ->where('outlet_id', $userOutlet[0])
            ->get()
            ->map(function ($bill) {
                $bill->created_at_human = Carbon::parse($bill->created_at)->diffForHumans();
                return $bill;
            });
        // $listBill['created_at'] = Carbon::parse($listBill->created_at)->diffForHumans()
        return view('layouts.kasir.modal-bill-list', [
            'listBills' => $listBill
        ]);
    }

    public function chooseBill($id)
    {
        $data = OpenBill::with(['item', 'customer'])->where('id', $id)->first();

        return response()->json([
            'data' => $data
        ]);
    }

    public function updateBill(Request $request)
    {
        $openBill = OpenBill::where('id', $request->bill_id)->first();
        $openBill->queue_order += 1;

        $openBill->save();
        DB::transaction(function () use ($request, $openBill) {
            $dataItemOpenBill = [];
            for ($x = 0; $x < count($request['tmpId']); $x++) {
                $dataItemOpenBill[] = [
                    'open_bill_id' => $request->bill_id,
                    'catatan' => $request['catatan'][$x],
                    'diskon' => $request['diskon'][$x],
                    'harga' => $request['harga'][$x],
                    'product_id' => $request['idProduct'][$x],
                    'variant_id' => $request['idVariant'][$x],
                    'modifier' => $request['modifier'][$x],
                    'nama_product' => $request['namaProduct'][$x],
                    'nama_variant' => $request['namaVariant'][$x],
                    'pilihan' => $request['pilihan'][$x],
                    'promo' => $request['promo'][$x],
                    'quantity' => $request['quantity'][$x],
                    'result_total' => $request['resultTotal'][$x],
                    'sales_type' => $request['salesType'][$x],
                    'tmp_id' => $request['tmpId'][$x],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                    'queue_order' => $openBill->queue_order,
                ];
            }

            ItemOpenBill::insert($dataItemOpenBill);
        });

        // return responseSuccess(true);
        return response()->json([
            'data' => $openBill
        ]);
    }

    public function printOpenBillOrder($bill_id)
    {
        $openBill = OpenBill::where('id', $bill_id)->first();

        // Jika OpenBill ditemukan, ambil item berdasarkan queue_order
        if ($openBill) {
            $openBill->load(['item' => function ($query) use ($openBill) {
                // Ambil queue_order dari objek $openBill
                $query->where('queue_order', $openBill->queue_order);
            },
             'user']);

            // Decode the modifier field from JSON string to array of objects for each item
            foreach ($openBill->item as $item) {
                $item->modifier = json_decode($item->modifier, true);
                $item->pilihan = json_decode($item->pilihan, true);
            }

        }

        return response()->json([
            'status' => 'success',
            'data' => $openBill
        ]);
    }

    public function tambahCustomer(){
        return view('layouts.kasir.modal-tambah-customer',[
            'communities' => Community::orderBy('name', 'asc')->get(),
            'customer' => Customer::orderBy('name', 'asc')->get()
        ]);
    }

    public function getListTransactionToday($id){
        $today = Carbon::today(); // Mendapatkan tanggal hari ini
        $activeShift = PettyCash::find($id);
        $transaction = Transaction::with(['itemTransaction' => function($itemTransaction){
            $itemTransaction->with(['product', 'variant']);
        }])
        ->whereDate('created_at', $today)
        ->where('outlet_id', $activeShift->outlet_id)
        ->where('patty_cash_id', $id)
        ->orderBy('created_at', 'desc')
        ->get();

        $transaction->map(function($item){
            $item->created_time = Carbon::parse($item->created_at)->format('H:i');
            $item->created_tanggal = Carbon::parse($item->created_at)->format('d-m-Y');
            return $item;
        });

        return response()->json([
            'data' => $transaction
        ]);
    }

    public function printShiftDetail($petty_cash_id){
        $soldModifier = 0;
        $soldProduct = 0;
        $rounding = 0;

        $pattyCash = PettyCash::find($petty_cash_id)->load(['outlet', 'userStarted', 'userEnded']);

        // Mengambil VariantProduct yang memiliki itemTransaction terkait dengan petty_cash_id dan transaction tidak null
        $data = VariantProduct::with(['itemTransaction' => function($itemTransaction) use($petty_cash_id) {
            $itemTransaction->whereHas('transaction', function($transaction) use($petty_cash_id)  {
                $transaction->where('patty_cash_id', $petty_cash_id);
            });
        }, 'product.category'])
        ->whereHas('itemTransaction.transaction', function($transaction) use($petty_cash_id) {
            $transaction->where('patty_cash_id', $petty_cash_id);
        })
        ->whereHas('itemTransaction', function($itemTransaction) {
            $itemTransaction->whereHas('transaction');
        })
        ->whereHas('product') // Memastikan ada relasi product
        ->get()->map(function($item) use($soldProduct){
            $item->total_transaction = count($item->itemTransaction);

            $totalHarga = $item->harga * count($item->itemTransaction);
            $item->total_transaction_amount = formatRupiah(strval($totalHarga), "Rp. ");
            return $item;
        });


        foreach($data as $item){
            $soldProduct += count($item->itemTransaction);
        }


        $listModifierTransaction = [];

        $dataModifier = Modifiers::whereHas('modifierGroup', function($modifierGroup) use($pattyCash){
            $modifierGroup->where('outlet_id', $pattyCash->outlet_id);
        })->get();

        foreach($dataModifier as $modifier){
            $transactions = TransactionItem::whereJsonContains('modifier_id', ['id' => strval($modifier->id)])->whereHas('transaction', function($query) use($petty_cash_id){
                $query->where('patty_cash_id', $petty_cash_id);
            })->get();

            if(count($transactions)){
                $soldModifier += count($transactions);
                $modifier->item_transactions = $transactions;
                $modifier->total_transaction = count($transactions);

                $totalHarga = $modifier->harga * count($transactions);
                $modifier->total_transaction_amount = formatRupiah(strval($totalHarga), "Rp. ");
                array_push($listModifierTransaction, $modifier);
            }
        }

        $listCategoryPayment = CategoryPayment::with(['transactions' => function ($transaction) use ($pattyCash) {
            $transaction->with(['payments'])->where('patty_cash_id', $pattyCash->id);
        }, 'payment' => function ($payment) use ($pattyCash) {
            $payment->with(['transactions' => function ($transaction) use ($pattyCash) {
                $transaction->where('patty_cash_id', $pattyCash->id);
            }]);
        }])->get();


        $roundingTransaction = Transaction::where('patty_cash_id', $petty_cash_id)->get();


        foreach($roundingTransaction as $dataTransaction){
            $rounding += $dataTransaction->rounding_amount;
        }

        return response()->json([
            'data_product_transaction' => $data,
            'data_modifier_transaction' => $listModifierTransaction,
            'patty_cash' => $pattyCash,
            'sold_product' => $soldProduct,
            'sold_modifier' => $soldModifier,
            'data_payment' => $listCategoryPayment,
            'rounding' => $rounding
        ]);
    }

    public function historyShift($outletid){
        $shifts = PettyCash::where('outlet_id', $outletid)->limit(30)->orderBy('created_at', 'desc')->get(); // Mengambil 10 data per halaman
        return response()->json($shifts);
    }

    public function detailHistoryShift($shiftid){
        $shift = PettyCash::find($shiftid)->load(['userStarted', 'userEnded', 'outlet']);
        $listCategoryPayment = CategoryPayment::with(['transactions' => function ($transaction) use ($shift) {
            $transaction->with(['payments'])->where('patty_cash_id', $shift->id);
        }, 'payment' => function ($payment) use ($shift) {
            $payment->with(['transactions' => function ($transaction) use ($shift) {
                $transaction->where('patty_cash_id', $shift->id);
            }]);
        }])->get();

        $soldItem = VariantProduct::with(['itemTransaction' => function($itemTransaction) use($shift) {
            $itemTransaction->whereHas('transaction', function($transaction) use($shift)  {
                $transaction->where('patty_cash_id', $shift->id);
            });
        }, 'product.category'])
        ->whereHas('itemTransaction.transaction', function($transaction) use($shift) {
            $transaction->where('patty_cash_id', $shift->id);
        })
        ->whereHas('itemTransaction', function($itemTransaction) {
            $itemTransaction->whereHas('transaction');
        })
        ->whereHas('product') // Memastikan ada relasi product
        ->get()->map(function($item) {
            $item->total_transaction = count($item->itemTransaction);

            // $totalHarga = $item->harga * count($item->itemTransaction);
            // $item->total_transaction_amount = formatRupiah(strval($totalHarga), "Rp. ");
            return $item;
        })
        ->select(['harga', 'product', 'name', 'total_transaction']);

        return response()->json([
            'data' => $shift,
            'listCategoryPayment' => $listCategoryPayment,
            'soldItem' => $soldItem
        ]);
    }

    public function viewResendReceipt($id){
        return view('layouts.kasir.modal-resend-struk', [
            "action" => route('report/transaction/resendReceipt', $id)
        ]);
    }

    public function viewSplitBill(){
        return view('layouts.kasir.modal-split-bill');
    }

    public function choosePaymentSplitBill(){
        $listCategoryPayment = CategoryPayment::with(['payment' => function ($payment) {
            $payment->where('status', true);
        }])->where('status', true)->orderBy('name', 'asc')->get();
        $rounding = Checkout::find(1);

        return view('layouts.kasir.modal-bayar-splitbill', [
            'listPayment' => $listCategoryPayment,
            'rounding' => $rounding
        ]);
    }

}

<?php

namespace App\Http\Controllers;

use App\DataTables\PilihPelangganDataTable;
use App\Models\Category;
use App\Models\CategoryPayment;
use App\Models\Checkout;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Outlets;
use App\Models\PettyCash;
use App\Models\Product;
use App\Models\Promo;
use App\Models\SalesType;
use App\Models\Taxes;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\VariantProduct;
use Illuminate\Http\Request;

use function PHPUnit\Framework\isNull;

class KasirController extends Controller
{
    public function index()
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

        $pettyCash = PettyCash::where('outlet_id', $outletUser[0])->where('close', null)->get();

        if(count($pettyCash)){
            $pettyCash[0]['user_data_started'] = auth()->user();
            $pettyCash[0]['outlet_data'] = $outlet;
        }

        $listCategoryPayment = CategoryPayment::with(['transactions' => function($transaction)use($pettyCash){
            if(count($pettyCash)){
                $transaction->with(['payments'])->where('patty_cash_id', $pettyCash[0]->id);
            }
        }, 'payment' => function($payment){
            $payment->with(['transactions']);
        }])->get();
        
        // dd($listCategoryPayment);
        return view('layouts.kasir.index', [
            'categorys' => Category::with(['products' => function ($product) use($outletUser) {
                $product->with(['variants'])->where('outlet_id', $outletUser[0])->orderBy('name', 'asc');
            }])->get(),
            'pajak' => $pajak,
            'rounding' => $rounding,
            'promos' => $promos,
            'discounts' => $diskon,
            'pettyCash' => $pettyCash,
            'listCategoryPayment' =>$listCategoryPayment
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
        $listCategoryPayment = CategoryPayment::with(['payment' => function($payment){
            $payment->where('status', true);
        }])->where('status', true)->orderBy('name', 'asc')->get();

        $pettyCash = PettyCash::where('outlet_id', $outletUser[0])->where('close', null)->get();

        if (count($pettyCash) > 0) {
            return view('layouts.kasir.modal-choose-payment',[
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

    public function viewPattyCash(){
        return view('layouts.kasir.modal-petty-cash');        
    }

    public function bayar(Request $request)
    {
        $outletUser = auth()->user()->outlet_id;
        $dataOutletUser = json_decode($outletUser);


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
        if(count($dataDiscountAllItem)){
            foreach($dataDiscountAllItem as $discountAllItemData){
                $totalNominalDiskon += $discountAllItemData->value;
            }
        }

        $customerId = $request->customer_id == 'null' ? null : $request->customer_id;

        if($customerId){
            $customer = Customer::find($customerId);
        }else{
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
        ];

        $transaction = Transaction::create($dataTransaction);

        for ($x = 0; $x < count($request->idProduct); $x++) {
            $idProduct = $request->idProduct[$x] == 'null' ? null : intval($request->idProduct[$x]);
            $dataProduct = [
                'product_id' => $idProduct,
                'discount_id' => $request->discount_id[$x],
                'modifier_id' => $request->modifier_id[$x],
                'promo_id' => $request->promo_id[$x],
                'reward_item' => $request->reward[$x] == "true" ? true : false,
                'transaction_id' => $transaction->id,
                'catatan' => isset($request->catatan[$x]) ? $request->catatan[$x] : '',
                'sales_type_id' => ($request->sales_type[$x] == 'null' || $request->sales_type[$x] == 'undefined') ?  null : $request->sales_type[$x],
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

    public function customDiskon(Discount $diskon){
        return view('layouts.kasir.modal-custom-amount', [
            'data' => $diskon
        ]);
    }

    public function pilihCustomer(PilihPelangganDataTable $datatable){
        return $datatable->render('layouts.kasir.modal-pilih-customer');
    }

    public function choosePromo(){
        return view('layouts.kasir.modal-choose-promo');
    }

    public function chooseRewardItem($queue, $idpromo){
        $promo = Promo::find($idpromo);

        $reward = [];
        $dataReward = json_decode($promo->reward);
        foreach($dataReward as $listReward){
            $tmpReward = [];
            foreach($listReward as $data){
                $product = Product::with(relations: ['variants'])->where('id', $data[0])->get();
                $dataProduct = [$product[0]->id, $product[0]->name];
                $variant = [];
                if($data[1] == 0){
                    foreach($product[0]->variants as $dataVariant){
                        $tmpVariant = [$dataVariant->id, $dataVariant->name];
                        array_push($variant, $tmpVariant);
                    }
                }else{
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

    public function closePattyCash(Request $request){
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

    public function apiStruk($id){
        $transaction = Transaction::find($id);
        $transactionItems = TransactionItem::where('transaction_id', $id);

        return response()->json([
            'status' => true,
            'transaction' => $transaction,
            'transactionItems' => $transactionItems
        ]);
    }
}

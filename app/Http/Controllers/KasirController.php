<?php

namespace App\Http\Controllers;

use App\DataTables\PilihPelangganDataTable;
use App\Models\Category;
use App\Models\CategoryPayment;
use App\Models\Checkout;
use App\Models\Discount;
use App\Models\PettyCash;
use App\Models\Product;
use App\Models\Taxes;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\VariantProduct;
use Illuminate\Http\Request;

class KasirController extends Controller
{
    public function index()
    {
        $outletUser = auth()->user()->outlet_id;
        $dataOutletUser = json_decode($outletUser);

        $userOutletJson = auth()->user()->outlet_id;
        $userOutlet = json_decode($userOutletJson);

        $diskon = Discount::where('outlet_id', '=', $userOutlet[0])->get();

        $rounding = Checkout::find(1);
        // dd($rounding);

        $pajak = Taxes::where('outlet_id', $dataOutletUser[0])->get();
        $outletUser = json_decode(auth()->user()->outlet_id);

        return view('layouts.kasir.index', [
            'categorys' => Category::with(['products' => function ($product) use($outletUser) {
                $product->where('outlet_id', $outletUser[0])->orderBy('name', 'asc');
            }])->get(),
            'pajak' => $pajak,
            'rounding' => $rounding,
            'discounts' => $diskon
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

        $discounts = Discount::where('type_input', 'fixed')->where('outlet_id', $dataOutletUser[0])->where('satuan', 'percent')->get();
        return view('layouts.kasir.kasir-modal-product', [
            'data' => $dataProduct,
            'variants' => $dataVariants,
            'discounts' => $discounts,
            'modifiers' => $modifiers
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

        $data = [
            'outlet_id' => $outletUser[0],
            'amount_awal' => getAmount($request->saldo_awal),
            'user_id_started' => $userData->id,
            'open' => now()
        ];

        PettyCash::create($data);

        return responseSuccess(false, "Shift Berhasil dibuka");
    }

    public function bayar(Request $request)
    {
        $outletUser = auth()->user()->outlet_id;
        $dataOutletUser = json_decode($outletUser);

        // dd($request);

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

        $dataTransaction = [
            'outlet_id' => $dataOutletUser[0],
            'user_id' => auth()->user()->id,
            'customer_id' => $customerId,
            'total' => $request->total,
            'nominal_bayar' => $request->nominal_bayar,
            'category_payment_id' => $request->category_payment_id,
            'nama_tipe_pembayaran' => $request->nama_tipe_pembayaran,
            'change' => $request->change,
            'tipe_pembayaran' => $request->tipe_pembayaran,
            'total_pajak' => $request->total_pajak,
            'total_modifier' => $hargaModifier,
            'total_diskon' => $totalNominalDiskon,
            'diskon_all_item' => $request->diskonAllItems,
            'rounding_amount' => $request->rounding,
            'tanda_rounding' => $request->tanda_rounding,
            'catatan' => $request->catatan
        ];

        $transaction = Transaction::create($dataTransaction);

        for ($x = 0; $x < count($request->idProduct); $x++) {
            $idProduct = $request->idProduct[$x] == 'null' ? null : intval($request->idProduct[$x]);
            $dataProduct = [
                'product_id' => $idProduct,
                'discount_id' => $request->discount_id[$x],
                'modifier_id' => $request->modifier_id[$x],
                'transaction_id' => $transaction->id,
                'catatan' => $request->catatan[$x]
            ];

            TransactionItem::insert($dataProduct);
        }

        $respond = [
            'status'    => 'success',
            // 'id'        => base64_encode($id_penjualan),
            // 'waLink'    => $whatsappLink,
            'change'     => $request->change,
            'metode'    => $request->nama_tipe_pembayaran,
            'message' => "Transaksi Berhasil"
            // 'pelanggan' => $pelanggan,
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
}

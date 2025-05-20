<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryPaymentController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\HakAksesController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\LevelMembershipController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ModifiersController;
use App\Http\Controllers\OpenBillController;
use App\Http\Controllers\OutletController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PemasukanController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PilihanController;
use App\Http\Controllers\PiutangController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SalesTypeController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\TransactionsController;
use App\Http\Controllers\UserController;
use App\Models\ModifierGroup;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/login', [AuthController::class, 'login'])->name('login')->middleware('guest');
// Route::post('/login/store', [AuthController::class, 'store'])->name('store');
// Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::get('/', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/getDataSummary', [DashboardController::class, 'getDataSummary'])->name('getDataSummary');
    Route::get('/getDataOutletCompare', [DashboardController::class, 'getDataOutletCompare'])->name('getDataOutletCompare');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::group(['prefix' => 'konfigurasi', 'as' => 'konfigurasi/'], function () {

        Route::prefix('menu')->group(function(){
            Route::get('/', [MenuController::class, 'index'])->name('menu');
            Route::get('/create', [MenuController::class, 'create'])->name('menu/create');
            Route::get('/{menu}/edit', [MenuController::class, 'edit'])->name('menu/edit');
            Route::post('/update{id}', [MenuController::class, 'update'])->name('menu/update');
            Route::post('/store', [MenuController::class, 'store'])->name('menu/store');
            Route::put('/sorting', [MenuController::class, 'sort'])->name('menu/sort');
            Route::post('/{id}/destroy', [MenuController::class, 'destroy'])->name('menu/destroy');

        });


        Route::prefix('permissions')->group(function(){
            Route::get('/', [PermissionController::class, 'index'])->name('permissions');
            Route::get('/create', [PermissionController::class, 'create'])->name('permissions/create');
            Route::get('/edit/{id}', [PermissionController::class, 'edit'])->name('permissions/edit');
            Route::post('/store', [PermissionController::class, 'store'])->name('permissions/store');
            Route::post('/update/{id}', [PermissionController::class, 'update'])->name('permissions/update');
            Route::delete('/destroy/{id}', [PermissionController::class, 'destroy'])->name('permissions/destroy');
        });

        Route::prefix('outlets')->group(function(){
            Route::get('/', [OutletController::class, 'index'])->name('outlets');
            Route::get('/create', [OutletController::class, 'create'])->name('outlets/create');
            Route::post('/store', [OutletController::class, 'store'])->name('outlets/store');
            Route::get('/edit/{outlet}', [OutletController::class, 'edit'])->name('outlets/edit');
            Route::put('/update/{outlet}', [OutletController::class, 'update'])->name('outlets/update');
            Route::delete('/destroy/{outlet}', [OutletController::class,'destroy'])->name('outlets/destroy');
        });

        Route::prefix('checkout')->group(function(){
            Route::get('/', [CheckoutController::class, 'index'])->name('checkout');
            Route::post('/store', [CheckoutController::class, 'store'])->name('checkout/store');
        });

        Route::prefix('category-payment')->group(function(){
            Route::get('/', [CategoryPaymentController::class, 'index'])->name('category-payment');
            Route::get('/create', [CategoryPaymentController::class, 'create'])->name('category-payment/create');
            Route::post('/store', [CategoryPaymentController::class, 'store'])->name('category-payment/store');
            Route::get('/edit/{categoryPayment}', [CategoryPaymentController::class, 'edit'])->name('category-payment/edit');
            Route::put('/update/{categoryPayment}', [CategoryPaymentController::class, 'update'])->name('category-payment/update');
            Route::delete('/destroy/{categoryPayment}', [CategoryPaymentController::class,'destroy'])->name('category-payment/destroy');
        });

        Route::prefix('payment')->group(function(){
            Route::get('/', [PaymentController::class, 'index'])->name('payment');
            Route::get('/create', [PaymentController::class, 'create'])->name('payment/create');
            Route::post('/store', [PaymentController::class, 'store'])->name('payment/store');
            Route::get('/edit/{payment}', [PaymentController::class, 'edit'])->name('payment/edit');
            Route::put('/update/{payment}', [PaymentController::class, 'update'])->name('payment/update');
            Route::delete('/destroy/{payment}', [PaymentController::class,'destroy'])->name('payment/destroy');
        });

    });


    Route::group(['prefix' => 'employee', 'as' => 'employee/'], function(){
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('user');
            Route::get('/create', [UserController::class, 'create'])->name('user/create');
            Route::get('/edit/{id}', [UserController::class, 'edit'])->name('user/edit');
            Route::post('/store', [UserController::class, 'store'])->name('user/store');
            Route::post('/update/{id}', [UserController::class, 'update'])->name('user/update');
            Route::post('/destroy/{id}', [UserController::class, 'destroy'])->name('user/destroy');
        });

        Route::prefix('roles')->group(function(){
            Route::get('/', [RoleController::class, 'index'])->name('roles');
            Route::get('/create', [RoleController::class, 'create'])->name('roles/create');
            Route::post('/store', [RoleController::class, 'store'])->name('roles/store');
            Route::get('/edit/{id}', [RoleController::class, 'edit'])->name('roles/edit');
            Route::post('/update/{id}', [RoleController::class, 'update'])->name('roles/update');
            Route::delete('/destroy/{id}', [RoleController::class, 'destroy'])->name('roles/destroy');
        });

        Route::prefix('hak-akses')->group(function(){
            Route::get('/', [HakAksesController::class, 'index'])->name('hak-akses');
            Route::get('/edit/hak-akses-role/{id}', [HakAksesController::class, 'editAksesRole'])->name('hak-akses/role/edit');
            Route::post('/update/role/{id}', [HakAksesController::class, 'updateAksesRole'])->name('hak-akses/role/update');
            Route::get('/edit/hak-akses-user/{id}', [HakAksesController::class, 'editAksesUser'])->name('hak-akses/user/edit');
            Route::post('/update/user/{id}', [HakAksesController::class, 'updateAksesUser'])->name('hak-akses/user/update');
        });

    });

    Route::group(['prefix'=> 'library', 'as' => 'library/'], function () {

        Route::prefix('category')->group(function(){
            Route::get('/', [CategoryController::class, 'index'])->name('category');
            Route::get('/create', [CategoryController::class, 'create'])->name('category/create');
            Route::post('/store', [CategoryController::class, 'store'])->name('category/store');
            Route::get('/edit/{category}', [CategoryController::class, 'edit'])->name('category/edit');
            Route::put('/update/{category}', [CategoryController::class,'update'])->name('category/update');
            Route::delete('/destroy/{category}', [CategoryController::class,'destroy'])->name('category/destroy');
        });

        Route::prefix('product')->group(function(){
            Route::get('/', [ProductController::class,'index'])->name('product');
            Route::get('/create', [ProductController::class, 'create'])->name('product/create');
            Route::get('/getVariantByProductId/{id}', [ProductController::class, 'findVariantByProductId'])->name('product/findVariantByProductId');
            Route::get('/getVariantByProductName/{name}', [ProductController::class, 'findVariantByProductName'])->name('product/findVariantByProductName');
            Route::get('/getCategoryByOutlet', [ProductController::class, 'getCategoryByOutlet'])->name('product/getCategoryByOutlet');
            Route::get('/getProductByOutlet', [ProductController::class, 'getProductByOutlet'])->name('product/getProductByOutlet');
            Route::post('/store', [ProductController::class, 'store'])->name('product/store');
            Route::get('/edit/{product}', [ProductController::class, 'edit'])->name('product/edit');
            Route::put('/update/{product}', [ProductController::class,'update'])->name('product/update');
            Route::delete('/destroy/{product}', [ProductController::class,'destroy'])->name('product/destroy');
        });

        Route::prefix('tax')->group(function(){
            Route::get('/', [TaxController::class, 'index'])->name('tax');
            Route::get('/create', [TaxController::class, 'create'])->name("tax/create");
            Route::get('/edit/{tax}', [TaxController::class, 'edit'])->name('tax/edit');
            Route::post('/store', [TaxController::class, 'store'])->name('tax/store');
            Route::put('/update/{tax}', [TaxController::class, 'update'])->name('tax/update');
            Route::delete('/delete/{tax}', [TaxController::class, 'destroy'])->name('tax/destroy');
        });

        Route::prefix('modifiers')->group(function(){
            Route::get('/', [ModifiersController::class, 'index'])->name('modifiers');
            Route::get('/create', [ModifiersController::class, 'create'])->name('modifiers/create');
            Route::get('/edit/{modifier}', [ModifiersController::class, 'edit'])->name('modifiers/edit');
            Route::get('/getProduct/{modifierGroup}', [ModifiersController::class, 'getProduct'])->name('modifiers/getProduct');
            Route::post('/store', [ModifiersController::class, 'store'])->name("modifiers/store");
            Route::put('/update/{modifier}', [ModifiersController::class, 'update'])->name('modifiers/update');
            Route::put('/update/product/{modifier}', [ModifiersController::class, 'updateProductModifier'])->name('modifiers/update-product');
            Route::delete('/destroy/{modifier}', [ModifiersController::class, 'destroy'])->name('modifiers/destroy');
        });

        Route::prefix('discount')->group(function(){
            Route::get('/', [DiscountController::class, 'index'])->name('discount');
            Route::get('/create', [DiscountController::class, 'create'])->name('discount/create');
            Route::post('/store', [DiscountController::class, 'store'])->name('discount/store');
            Route::get('/edit/{discount}', [DiscountController::class, 'edit'])->name('discount/edit');
            Route::put('/update/{discount}', [DiscountController::class, 'update'])->name('discount/update');
            Route::delete('/destroy/{discount}', [DiscountController::class,'destroy'])->name('discount/destroy');
        });

        Route::prefix('promo')->group(function(){
            Route::get('/', [PromoController::class, 'index'])->name('promo');
            Route::get('/create', [PromoController::class, 'create'])->name('promo/create');
            Route::post('/store', [PromoController::class, 'store'])->name('promo/store');
            Route::get('/edit/{promo}', [PromoController::class, 'edit'])->name('promo/edit');
            Route::put('/update/{promo}', [PromoController::class, 'update'])->name('promo/update');
            Route::delete('/destroy/{promo}', [PromoController::class,'destroy'])->name('promo/destroy');
        });

        Route::prefix('salestype')->group(function(){
            Route::get('/', [SalesTypeController::class, 'index'])->name('salestype');
            Route::get('/create', [SalesTypeController::class, 'create'])->name('salestype/create');
            Route::get('/getSalesTypeByOutlet', [SalesTypeController::class, 'getSalesTypeByOutlet'])->name('salestype/getSalesTypeByOutlet');
            Route::post('/store', [SalesTypeController::class, 'store'])->name('salestype/store');
            Route::get('/edit/{salesType}', [SalesTypeController::class, 'edit'])->name('salestype/edit');
            Route::put('/update/{salesType}', [SalesTypeController::class, 'update'])->name('salestype/update');
            Route::put('/updateStatus/{id}', [SalesTypeController::class, 'updateStatus'])->name('salestype/update-status');
            Route::delete('/destroy/{salesType}', [SalesTypeController::class,'destroy'])->name('salestype/destroy');
        });

        Route::prefix('pilihan')->group(function(){
            Route::get('/', [PilihanController::class, 'index'])->name('pilihan');
            Route::get('/create', [PilihanController::class, 'create'])->name('pilihan/create');
            Route::get('/edit/{pilihan}', [PilihanController::class, 'edit'])->name('pilihan/edit');
            Route::get('/getProduct/{pilihanGroup}', [PilihanController::class, 'getProduct'])->name('pilihan/getProduct');
            Route::post('/store', [PilihanController::class, 'store'])->name("pilihan/store");
            Route::put('/update/{pilihan}', [PilihanController::class, 'update'])->name('pilihan/update');
            Route::put('/update/product/{pilihan}', [PilihanController::class, 'updateProductPilihan'])->name('pilihan/update-product');
            Route::delete('/destroy/{pilihan}', [PilihanController::class, 'destroy'])->name('pilihan/destroy');
        });
    });

    Route::group(['prefix'=> 'membership', 'as' => 'membership/'], function () {
        Route::prefix('customer')->group(function(){
            Route::get('/', [CustomerController::class, 'index'])->name('customer');
            Route::get('/create', [CustomerController::class, 'create'])->name('customer/create');
            Route::post('/store', [CustomerController::class, 'store'])->name('customer/store');
            Route::get('/detail/{customer}', [CustomerController::class, 'detail'])->name('customer/detail');
            Route::get('/detail-customer/{customer}', [CustomerController::class, 'detailCustomer'])->name('customer/detailCustomer');
            Route::get('/detail-transaction/{transaction}', [CustomerController::class, 'detailTransaction'])->name('customer/detailTransaction');
            Route::get('/history-point-use/{customer}', [CustomerController::class, 'historyPointUse'])->name('customer/historyPointUse');
            Route::post('/reward-confirmation', [CustomerController::class, 'rewardConfirmation'])->name('customer/rewardConfirmation');
            Route::get('/check-reward-confirmation/{customer}', [CustomerController::class, 'checkRewardConfirmation'])->name('customer/checkRewardConfirmation');
            Route::delete('/destroy/{customer}', [CustomerController::class, 'destroy'])->name('customer/destroy');
            Route::get('/edit/{customer}', [CustomerController::class, 'edit'])->name('customer/edit');
            Route::get('/list-referee/{customer}', [CustomerController::class, 'listReferee'])->name('customer/listReferee');
            Route::put('/update/{customer}', [CustomerController::class, 'update'])->name('customer/update');
        });

        Route::prefix('community')->group(function(){
            Route::get('/', [CommunityController::class, 'index'])->name('community');
            Route::get('/create', [CommunityController::class, 'create'])->name('community/create');
            Route::get('/createExchangeExp/{id}', [CommunityController::class, 'createExchangeExp'])->name('community/createExchangeExp');
            Route::get('/historyUseExp/{id}', [CommunityController::class, 'historyUseExp'])->name('community/historyUseExp');
            Route::get('/detail/{id}', [CommunityController::class, 'detail'])->name('community/detail');
            Route::post('/store', [CommunityController::class, 'store'])->name('community/store');
            Route::post('/storeExchangeExp', [CommunityController::class, 'storeExchangeExp'])->name('community/storeExchangeExp');
            Route::get('/edit/{community}', [CommunityController::class, 'edit'])->name('community/edit');
            Route::put('/update/{community}', [CommunityController::class, 'update'])->name('community/update');
            Route::delete('/destroy/{community}', [CommunityController::class, 'destroy'])->name('community/destroy');
        });

        Route::prefix('level-membership')->group(function(){
            Route::get('/', [LevelMembershipController::class, 'index'])->name('level-membership');
            Route::get('/create', [LevelMembershipController::class, 'create'])->name('level-membership/create');
            Route::post('/store', [LevelMembershipController::class, 'store'])->name('level-membership/store');
            Route::get('/edit/{level}', [LevelMembershipController::class, 'edit'])->name('level-membership/edit');
            Route::put('/update/{level}', [LevelMembershipController::class, 'update'])->name('level-membership/update');
            Route::delete('/destroy/{level}', [LevelMembershipController::class, 'destroy'])->name('level-membership/destroy');
        });
    });



    Route::prefix('kasir')->group(function(){
        Route::get('/', [KasirController::class, 'index'])->name('kasir');
        Route::get('/viewPattyCash', [KasirController::class, 'viewPattyCash'])->name('kasir/viewPattyCash');
        Route::get('/choose-payment', [KasirController::class, 'choosePayment'])->name('kasir/choosePayment');
        Route::get('/choose-promo', [KasirController::class, 'choosePromo'])->name('kasir/choosePromo');
        Route::get('/choose-reward-item/{queue}/{idpromo}', [KasirController::class, 'chooseRewardItem'])->name('kasir/chooseRewardItem');
        Route::get('/pilih-customer', [KasirController::class, 'pilihCustomer'])->name('kasir/pilihCustomer');
        Route::get('/tambah-customer', [KasirController::class, 'tambahCustomer'])->name('kasir/tambahCustomer');
        Route::get('/custom-diskon/{diskon}', [KasirController::class, 'customDiskon'])->name('kasir/customDiskon');
        Route::get('/split-bill', [KasirController::class, 'viewSplitBill'])->name('kasir/viewSplitBill');
        Route::get('/choose-payment-split-bill', [KasirController::class, 'choosePaymentSplitBill'])->name('kasir/choosePaymentSplitBill');
        Route::get('/view-open-bill', [KasirController::class, 'viewOpenBill'])->name('kasir/viewOpenBill');
        Route::get('/view-resend-receipt/{id}', [KasirController::class, 'viewResendReceipt'])->name('kasir/viewResendReceipt');
        Route::get('/view-refund/{idTransaction}', [KasirController::class, 'viewRefund'])->name('kasir/viewRefund');
        Route::get('/history-shifts/{outletid}', [KasirController::class, 'historyShift'])->name('kasir/historyShift');
        Route::get('/detail-history-shifts/{shiftid}', [KasirController::class, 'detailHistoryShift'])->name('kasir/detailHistoryShift');
        Route::get('/choose-bill/{bill}', [KasirController::class, 'chooseBill'])->name('kasir/chooseBill');
        Route::get('/getListTransactionToday/{id}', [KasirController::class, 'getListTransactionToday'])->name('kasir/getListTransactionToday');
        Route::get('/bill-list', [KasirController::class, 'billList'])->name('kasir/billList');
        Route::get('/{product}', [KasirController::class, 'findProduct'])->name('kasir/findProduct');
        Route::post('/store-patty-cash', [KasirController::class, 'pattyCash'])->name('kasir/pattyCash');
        Route::post('/close-patty-cash', [KasirController::class, 'closePattyCash'])->name('kasir/closePattyCash');
        Route::post('/bayar', [KasirController::class, 'bayar'])->name('kasir/bayar');
        Route::post('/open-bill', [KasirController::class, 'openBill'])->name('kasir/openBill');
        Route::post('/update-bill-item', [KasirController::class, 'updateBill'])->name('kasir/updateBill');
        Route::post('/refund', [KasirController::class, 'refund'])->name('kasir/refund');
    });

    Route::group(['prefix' => 'accounting', 'as' => 'accounting/'], function(){
        Route::prefix('pengeluaran')->group(function () {
            Route::get('/', [PengeluaranController::class, 'index'])->name('pengeluaran');
            Route::get('/create', [PengeluaranController::class, 'create'])->name('pengeluaran/create');
            Route::get('/edit/{pengeluaran}', [PengeluaranController::class, 'edit'])->name('pengeluaran/edit');
            Route::post('/store', [PengeluaranController::class, 'store'])->name('pengeluaran/store');
            Route::put('/update/{pengeluaran}', [PengeluaranController::class, 'update'])->name('pengeluaran/update');
            Route::delete('/destroy/{pengeluaran}', [PengeluaranController::class, 'destroy'])->name('pengeluaran/destroy');
        });

        Route::prefix('pemasukan')->group(function () {
            Route::get('/', [PemasukanController::class, 'index'])->name('pemasukan');
            Route::get('/create', [PemasukanController::class, 'create'])->name('pemasukan/create');
            Route::get('/edit/{pemasukan}', [PemasukanController::class, 'edit'])->name('pemasukan/edit');
            Route::post('/store', [PemasukanController::class, 'store'])->name('pemasukan/store');
            Route::put('/update/{pemasukan}', [PemasukanController::class, 'update'])->name('pemasukan/update');
            Route::delete('/destroy/{pemasukan}', [PemasukanController::class, 'destroy'])->name('pemasukan/destroy');
        });

        Route::prefix('piutang')->group(function () {
            Route::get('/', [PiutangController::class, 'index'])->name('piutang');
            Route::get('/create', [PiutangController::class, 'create'])->name('piutang/create');
            // Route::get('/edit/{pemasukan}', [PemasukanController::class, 'edit'])->name('pemasukan/edit');
            Route::post('/store', [PiutangController::class, 'store'])->name('piutang/store');
            // Route::put('/update/{pemasukan}', [PemasukanController::class, 'update'])->name('pemasukan/update');
            // Route::delete('/destroy/{pemasukan}', [PemasukanController::class, 'destroy'])->name('pemasukan/destroy');
        });
    });

    Route::group(['prefix' => 'report', 'as' => 'report/'], function(){
        Route::prefix('sales')->group(function () {
            Route::get('/', [SalesController::class, 'index'])->name('sales');
            Route::get('/sales-summary', [SalesController::class, 'getSalesSummary'])->name('sales/getSalesSummary');
            Route::get('/gross-profit', [SalesController::class, 'getGrossProfit'])->name('sales/getGrossProfit');
            Route::get('/payment-method', [SalesController::class, 'getPaymentMethodSales'])->name('sales/getPaymentMethodSales');
            Route::get('/sales-type', [SalesController::class, 'getSalesType'])->name('sales/getSalesType');
            Route::get('/item-sales', [SalesController::class, 'getItemSales'])->name('sales/getItemSales');
            Route::get('/category-sales', [SalesController::class, 'getCategorySales'])->name('sales/getCategorySales');
            Route::get('/modifier-sales', [SalesController::class, 'getModifierSales'])->name('sales/getModifierSales');
            Route::get('/discount-sales', [SalesController::class, 'getDiscountSales'])->name('sales/getDiscountSales');
            Route::get('/tax-sales', [SalesController::class, 'getTaxSales'])->name('sales/getTaxSales');
            Route::get('/collected-by-sales', [SalesController::class, 'getCollectedBySales'])->name('sales/getCollectedBySales');
        });

        Route::prefix('transactions')->group(function () {
            Route::get('/', [TransactionsController::class, 'index'])->name('transaction');
            Route::get('/getTransactionData', [TransactionsController::class, 'getTransactionData'])->name('transaction/getTransactionData');
            Route::get('/getTransactionDataDetail', [TransactionsController::class, 'getTransactionDataDetail'])->name('transaction/getTransactionDataDetail');
            Route::get('/getOpenbillData', [TransactionsController::class, 'getOpenbillData'])->name('transaction/getOpenbillData');
            Route::get('/showReceipt/{idTransaction}', [TransactionsController::class, 'showReceipt'])->name('transaction/showReceipt');
            Route::get('/modalResendReceipt/{idTransaction}', [TransactionsController::class, 'modalResendReceipt'])->name('transaction/modalResendReceipt');
            Route::post('/resendReceipt/{idTransaction}', [TransactionsController::class, 'resendReceipt'])->name('transaction/resendReceipt');
            Route::delete('/destroy/{idTransaction}', [TransactionsController::class, 'destroy'])->name('transaction/destroy');
        });

        Route::prefix('openbill')->group(function () {
            Route::get('/', [OpenBillController::class, 'index'])->name('openbill');
            Route::get('/getOpenBillData', [OpenBillController::class, 'getOpenBillData'])->name('openbill/getOpenBillData');
            Route::get('/getOpenBillDataDetail', [OpenBillController::class, 'getOpenBillDataDetail'])->name('openbill/getOpenBillDataDetail');
            Route::post('/deleteOpenBill/{idOpenBill}', [OpenBillController::class, 'deleteOpenBill'])->name('openbill/deleteOpenBill');

        });
    });

});

Route::get('/api-struk/{id}', [KasirController::class, 'apiStruk'])->name('kasir/apiStruk');
Route::get('/api-open-bill/{bill_id}', [KasirController::class, 'printOpenBillOrder']);
Route::get('/api-print-shift-detail/{petty_cash_id}', [KasirController::class, 'printShiftDetail']);
Route::get('/get-akun/{outlet_id}', [OutletController::class, 'getAkun']);
Route::post('/log-error-android', [LogController::class, 'logErrorAndroid'])->name('log/logErrorAndroid');
Route::post('/log-error-android', [LogController::class, 'logErrorAndroid'])->name('log/logErrorAndroid');

require __DIR__.'/auth.php';

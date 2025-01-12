<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryPaymentController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\HakAksesController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ModifiersController;
use App\Http\Controllers\OutletController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalesTypeController;
use App\Http\Controllers\TaxController;
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

Route::get('/', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
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
            ROute::get('/getProduct/{modifierGroup}', [ModifiersController::class, 'getProduct'])->name('modifiers/getProduct');
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
    });

    Route::prefix('customer')->group(function(){
        Route::get('', [CustomerController::class, 'index'])->name('customer');
        Route::get('/create', [CustomerController::class, 'create'])->name('customer/create');
        Route::post('/store', [CustomerController::class, 'store'])->name('customer/store');
        Route::get('/edit/{customer}', [CustomerController::class, 'edit'])->name('customer/edit');
        Route::put('/update/{customer}', [CustomerController::class, 'update'])->name('customer/update');
        Route::delete('/destroy/{customer}', [CustomerController::class, 'destroy'])->name('customer/destroy');
    });

        
    Route::prefix('kasir')->group(function(){
        Route::get('', [KasirController::class, 'index'])->name('kasir');
        Route::get('/viewPattyCash', [KasirController::class, 'viewPattyCash'])->name('kasir/viewPattyCash');
        Route::get('/choose-payment', [KasirController::class, 'choosePayment'])->name('kasir/choosePayment');
        Route::get('/choose-promo', [KasirController::class, 'choosePromo'])->name('kasir/choosePromo');
        Route::get('/choose-reward-item/{queue}/{idpromo}', [KasirController::class, 'chooseRewardItem'])->name('kasir/chooseRewardItem');
        Route::get('/pilih-customer', [KasirController::class, 'pilihCustomer'])->name('kasir/pilihCustomer');
        Route::get('/custom-diskon/{diskon}', [KasirController::class, 'customDiskon'])->name('kasir/customDiskon');
        Route::get('/{product}', [KasirController::class, 'findProduct'])->name('kasir/findProduct');
        Route::post('/store-patty-cash', [KasirController::class, 'pattyCash'])->name('kasir/pattyCash');
        Route::post('/close-patty-cash', [KasirController::class, 'closePattyCash'])->name('kasir/closePattyCash');
        Route::post('/bayar', [KasirController::class, 'bayar'])->name('kasir/bayar');
    });

    
});

require __DIR__.'/auth.php';

<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ShiftSessionController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\ModifiersController;
use App\Http\Controllers\PilihanController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SalesTypeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| API v1 — Mobile (Android Kasir)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {

    // Public routes (no auth required)
    Route::get('/outlets', [AuthController::class, 'outlets'])->name('api.v1.outlets');
    Route::get('/outlets/{outletId}/users', [AuthController::class, 'usersByOutlet'])->name('api.v1.outlets.users');
    Route::post('/login', [AuthController::class, 'login'])->name('api.v1.login');

    // Protected routes (Sanctum token required)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.logout');

        // Shift session
        Route::prefix('shift')->group(function () {
            Route::get('/petty-cash/active', [ShiftSessionController::class, 'checkActivePettyCash'])->name('api.v1.shift.petty-cash.active');
            Route::post('/petty-cash', [ShiftSessionController::class, 'storePettyCash'])->name('api.v1.shift.petty-cash.store');
            Route::patch('/session/{id}/close', [ShiftSessionController::class, 'closeSession'])->name('api.v1.shift.session.close');
        });
    });

});

Route::get('/getCategoryProductByOutlet/{idOutlet}', [ProductController::class,'getCategoryProductByOutlet'])->name('getCategoryProductByOutlet');
Route::get('/getModifierByOutlet/{idOutlet}', [ModifiersController::class,'getModifierByOutlet'])->name('getModifierByOutlet');
Route::get('/getDiscountByOutlet/{idOutlet}', [DiscountController::class,'getDiscountByOutlet'])->name('getDiscountByOutlet');
Route::get('/apiGetSalesTypeByOutlet/{idOutlet}', [SalesTypeController::class,'apiGetSalesTypeByOutlet'])->name('apiGetSalesTypeByOutlet');
Route::get('/getPilihansByOutlet/{idOutlet}', [PilihanController::class,'getPilihansByOutlet'])->name('getPilihansByOutlet');

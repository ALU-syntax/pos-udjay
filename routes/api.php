<?php

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

Route::get('/getCategoryProductByOutlet/{idOutlet}', [ProductController::class,'getCategoryProductByOutlet'])->name('getCategoryProductByOutlet');
Route::get('/getModifierByOutlet/{idOutlet}', [ModifiersController::class,'getModifierByOutlet'])->name('getModifierByOutlet');
Route::get('/getDiscountByOutlet/{idOutlet}', [DiscountController::class,'getDiscountByOutlet'])->name('getDiscountByOutlet');
Route::get('/apiGetSalesTypeByOutlet/{idOutlet}', [SalesTypeController::class,'apiGetSalesTypeByOutlet'])->name('apiGetSalesTypeByOutlet');
Route::get('/getPilihansByOutlet/{idOutlet}', [PilihanController::class,'getPilihansByOutlet'])->name('getPilihansByOutlet');

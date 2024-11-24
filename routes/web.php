<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\HakAksesController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
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
Route::post('/login/store', [AuthController::class, 'store'])->name('store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

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
            Route::get('', [CategoryController::class, 'index'])->name('category');
            Route::get('/create', [CategoryController::class, 'create'])->name('category/create');
            Route::post('/store', [CategoryController::class, 'store'])->name('category/store');
            Route::get('/edit/{category}', [CategoryController::class, 'edit'])->name('category/edit');
            Route::put('/update/{category}', [CategoryController::class,'update'])->name('category/update');
            Route::delete('/destroy/{category}', [CategoryController::class,'destroy'])->name('category/destroy');
        });

        Route::prefix('product')->group(function(){
            Route::get('/', [ProductController::class,'index'])->name('product');
            Route::get('/create', [ProductController::class, 'create'])->name('product/create');
        });
    });
        
});

require __DIR__.'/auth.php';

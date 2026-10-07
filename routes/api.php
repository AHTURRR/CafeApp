<?php

use App\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\V1\Admin\OptionController as AdminOptionController;
use App\Http\Controllers\Api\V1\Admin\OptionGroupController as AdminOptionGroupController;
use App\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Catalog\CategoryController as CatalogCategoryController;
use App\Http\Controllers\Api\V1\Catalog\ProductController as CatalogProductController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\SettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1  (prefix /api ditambahkan otomatis oleh Laravel -> /api/v1/...)
|--------------------------------------------------------------------------
| I-1: autentikasi dan profil.   I-2: katalog (Customer/Kasir/Owner) dan /admin untuk Owner.
| Grup /orders, /cashier, /kitchen ditambahkan pada langkah I-3 dan I-4.
| Parameter {id} dibatasi angka (lihat AppServiceProvider); tanpa implicit model binding.
*/
Route::prefix('v1')->group(function () {
    // Publik
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    // Terautentikasi (semua role)
    Route::middleware(['auth:sanctum', 'active', 'throttle:api'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::put('profile', [ProfileController::class, 'update']);

        // Katalog: Customer, Kasir, Owner (Kitchen tidak membutuhkan menu)
        Route::middleware('role:customer,cashier,owner')->group(function () {
            Route::get('categories', [CatalogCategoryController::class, 'index']);
            Route::get('products', [CatalogProductController::class, 'index']);
            Route::get('products/{id}', [CatalogProductController::class, 'show']);
            Route::get('settings/public', [SettingController::class, 'public']);
        });

        // Customer
        Route::middleware('role:customer')->group(function () {
            Route::post('orders/preview', [\App\Http\Controllers\Api\V1\Customer\CustomerOrderController::class, 'preview']);
            Route::post('orders', [\App\Http\Controllers\Api\V1\Customer\CustomerOrderController::class, 'store']);
            Route::get('orders', [\App\Http\Controllers\Api\V1\Customer\CustomerOrderController::class, 'index']);
            Route::get('orders/{id}', [\App\Http\Controllers\Api\V1\Customer\CustomerOrderController::class, 'show']);
            Route::post('orders/{id}/cancel', [\App\Http\Controllers\Api\V1\Customer\CustomerOrderController::class, 'cancel']);
        });

        // Owner
        Route::prefix('admin')->middleware('role:owner')->group(function () {
            Route::get('categories', [AdminCategoryController::class, 'index']);
            Route::post('categories', [AdminCategoryController::class, 'store']);
            Route::put('categories/{id}', [AdminCategoryController::class, 'update']);
            Route::delete('categories/{id}', [AdminCategoryController::class, 'destroy']);

            Route::get('products', [AdminProductController::class, 'index']);
            Route::post('products', [AdminProductController::class, 'store']);
            Route::get('products/{id}', [AdminProductController::class, 'show']);
            Route::put('products/{id}', [AdminProductController::class, 'update']);
            Route::delete('products/{id}', [AdminProductController::class, 'destroy']);
            Route::patch('products/{id}/status', [AdminProductController::class, 'updateStatus']);
            Route::put('products/{id}/option-groups', [AdminProductController::class, 'syncOptionGroups']);
            Route::post('products/{id}/image', [AdminProductController::class, 'uploadImage']);
            Route::delete('products/{id}/image', [AdminProductController::class, 'deleteImage']);

            Route::get('customization/groups', [AdminOptionGroupController::class, 'index']);
            Route::post('customization/groups', [AdminOptionGroupController::class, 'store']);
            Route::get('customization/groups/{id}', [AdminOptionGroupController::class, 'show']);
            Route::put('customization/groups/{id}', [AdminOptionGroupController::class, 'update']);
            Route::delete('customization/groups/{id}', [AdminOptionGroupController::class, 'destroy']);

            Route::post('customization/options', [AdminOptionController::class, 'store']);
            Route::put('customization/options/{id}', [AdminOptionController::class, 'update']);
            Route::delete('customization/options/{id}', [AdminOptionController::class, 'destroy']);
        });
    });
});

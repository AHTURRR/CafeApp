<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1  (prefix /api ditambahkan otomatis oleh Laravel -> /api/v1/...)
|--------------------------------------------------------------------------
| Langkah I-1: autentikasi dan profil. Grup per role (/orders, /cashier, /kitchen, /admin)
| ditambahkan pada langkah I-2 dan seterusnya dengan middleware 'role:...'.
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
    });
});

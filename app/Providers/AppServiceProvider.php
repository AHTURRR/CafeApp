<?php

namespace App\Providers;

use App\Services\Storage\ImageStorageInterface;
use App\Services\Storage\LocalImageStorage;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Ganti ke implementasi Cloudinary/S3 di sini bila diperlukan.
        $this->app->bind(ImageStorageInterface::class, LocalImageStorage::class);
    }

    public function boot(): void
    {
        $this->configureRateLimiting();

        // Semua parameter {id} pada route harus angka. Nilai lain menghasilkan 404 sebelum menyentuh controller.
        // Controller tidak memakai implicit model binding agar RBAC (403) selalu dievaluasi sebelum pencarian data (404).
        Route::pattern('id', '[0-9]+');
    }

    /** Batas sesuai API_SPECIFICATION bagian 1.3. */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));

        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(5)->by((string) $request->ip()));

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->getAuthIdentifier() ?: $request->ip()));
    }
}

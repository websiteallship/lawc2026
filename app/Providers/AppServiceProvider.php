<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ===================== SECURITY HARDENING =====================

        // Force HTTPS trong production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Giới hạn DB query log trong production (tránh leak SQL)
        if ($this->app->environment('production')) {
            DB::disableQueryLog();
        }

        // Model strict mode — throw khi truy cập lazy load, accessor không tồn tại, vv.
        // Tắt trong production để không crash user, bật trong dev/testing
        if (! $this->app->environment('production')) {
            Model::shouldBeStrict();
        }
    }
}

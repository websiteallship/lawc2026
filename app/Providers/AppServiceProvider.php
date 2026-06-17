<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Client\Events\ResponseReceived;
use App\Listeners\ApiQuotaListener;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

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

        Event::listen(
            ResponseReceived::class,
            ApiQuotaListener::class,
        );

        \App\Models\Bet::observe(\App\Observers\BetObserver::class);

        // ===================== RATE LIMITING (DDOS PREVENTION) =====================
        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(100)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}

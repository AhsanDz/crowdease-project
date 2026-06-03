<?php

namespace App\Providers;

use App\Models\DensityLog;
use App\Observers\DensityLogObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Daftarkan observer DensityLog untuk trigger webhook outbound (TI-2)
        DensityLog::observe(DensityLogObserver::class);

        // ── Rate Limiters ─────────────────────────────────────────────
        // Dipakai oleh routes via middleware 'throttle:<name>'.

        // Operator dashboard — 120 request/menit per user
        RateLimiter::for('operator', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Public endpoints (login, dll) — 30 request/menit per IP
        RateLimiter::for('public', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // IoT sensor endpoints — 600 request/menit per API key
        RateLimiter::for('iot', function (Request $request) {
            return Limit::perMinute(600)->by($request->header('X-API-Key', $request->ip()));
        });
    }
}

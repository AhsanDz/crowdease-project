<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->configureRateLimiters();
    }

    /**
     * Definisikan tiga tier rate limiter sesuai API Contract.
     *
     * Dipakai di route dengan middleware:
     *   throttle:public   -> endpoint publik (penumpang)
     *   throttle:iot      -> endpoint IoT (X-API-Key)
     *   throttle:operator -> endpoint operator (Sanctum)
     */
    protected function configureRateLimiters(): void
    {
        // Tier publik: 60 request per menit, dibatasi per alamat IP.
        RateLimiter::for('public', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Tier IoT: 600 request per menit, dibatasi per API key.
        // Key di-hash dengan sha1 agar tidak menyimpan rahasia di cache key.
        RateLimiter::for('iot', function (Request $request) {
            $key = $request->header('X-API-Key') ?: $request->ip();

            return Limit::perMinute(600)->by('iot:' . sha1((string) $key));
        });

        // Tier operator: 120 request per menit, dibatasi per user.
        // Jika user belum ter-resolve, jatuh ke pembatasan per IP.
        RateLimiter::for('operator', function (Request $request) {
            $userId = $request->user()?->id;

            return Limit::perMinute(120)->by(
                $userId !== null ? 'op:' . $userId : $request->ip()
            );
        });
    }
}

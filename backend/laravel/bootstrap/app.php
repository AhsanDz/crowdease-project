<?php

use App\Http\Middleware\ApiKeyAuth;
use App\Support\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\SendDensitySummary;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Alias middleware kustom. Dipakai di route sebagai 'api.key'.
        $middleware->alias([
            'api.key' => ApiKeyAuth::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
 
        // Kirim summary kepadatan ke Telegram setiap 5 menit
        $schedule->command(SendDensitySummary::class)
            ->everyFiveMinutes()
            ->withoutOverlapping()           // skip jika run sebelumnya belum selesai
            ->runInBackground()              // tidak memblok worker lain
            ->appendOutputTo(storage_path('logs/density-summary.log'));
 
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Untuk request ke /api/*, kembalikan error dalam format amplop
        // JSON yang konsisten dengan API Contract, bukan halaman HTML.
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null; // route web ditangani Laravel seperti biasa
            }

            return match (true) {
                $e instanceof ValidationException => ApiResponse::error(
                    'VALIDATION_FAILED',
                    'Data yang dikirim tidak valid.',
                    $e->errors(),
                    422
                ),

                $e instanceof AuthenticationException => ApiResponse::error(
                    'UNAUTHORIZED',
                    'Autentikasi diperlukan untuk mengakses sumber daya ini.',
                    null,
                    401
                ),

                $e instanceof ModelNotFoundException,
                $e instanceof NotFoundHttpException => ApiResponse::error(
                    'NOT_FOUND',
                    'Sumber daya yang diminta tidak ditemukan.',
                    null,
                    404
                ),

                $e instanceof ThrottleRequestsException => ApiResponse::error(
                    'RATE_LIMIT_EXCEEDED',
                    'Terlalu banyak permintaan. Coba lagi beberapa saat lagi.',
                    null,
                    429
                ),

                // Exception lain dibiarkan ke handler default Laravel.
                // Saat APP_DEBUG=true, ini menampilkan trace untuk debugging.
                default => null,
            };
        });
    })->create();
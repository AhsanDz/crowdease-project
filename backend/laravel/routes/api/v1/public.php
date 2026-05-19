<?php

use App\Support\ApiResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route Publik
|--------------------------------------------------------------------------
|
| Endpoint tanpa autentikasi untuk dikonsumsi aplikasi penumpang.
| Dibatasi rate limiter 'public' (60 request/menit per IP).
|
| Endpoint penumpang sesungguhnya (daftar koridor, kepadatan kendaraan,
| forecast) akan ditambahkan pada langkah berikutnya.
|
*/

Route::middleware('throttle:public')->group(function () {

    // Health check — verifikasi API hidup.
    Route::get('/ping', function () {
        return ApiResponse::success([
            'message' => 'pong',
            'service' => 'CrowdEase API',
            'time'    => now()->toIso8601String(),
        ]);
    });
});

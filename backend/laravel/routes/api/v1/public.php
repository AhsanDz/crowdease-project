<?php

use App\Http\Controllers\Api\V1\Passenger\RouteController;
use App\Http\Controllers\Api\V1\Passenger\VehicleController;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route Publik (Titik Integrasi TI-3)
|--------------------------------------------------------------------------
|
| Endpoint tanpa autentikasi yang dikonsumsi aplikasi penumpang.
| Dibatasi rate limiter 'public' (60 request/menit per IP).
|
*/

Route::middleware('throttle:public')->group(function () {

    // Health check.
    Route::get('/ping', function () {
        return ApiResponse::success([
            'message' => 'pong',
            'service' => 'CrowdEase API',
            'time'    => now()->toIso8601String(),
        ]);
    });

    // Endpoint koridor.
    Route::get('/routes',                  [RouteController::class, 'index']);
    Route::get('/routes/{route}',          [RouteController::class, 'show']);
    Route::get('/routes/{route}/stops',    [RouteController::class, 'stops']);
    Route::get('/routes/{route}/vehicles', [RouteController::class, 'vehicles']);

    // Endpoint armada (detail saat penumpang menge-tap satu bus).
    Route::get('/vehicles/{vehicle}/forecast',        [VehicleController::class, 'forecast']);
    Route::get('/vehicles/{vehicle}/density/history', [VehicleController::class, 'densityHistory']);
});

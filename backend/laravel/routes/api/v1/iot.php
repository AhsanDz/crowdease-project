<?php

use App\Http\Controllers\Api\V1\Iot\SensorReadingController;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route IoT — Titik Integrasi TI-1
|--------------------------------------------------------------------------
|
| Endpoint yang dikonsumsi oleh perangkat IoT (IoT Simulator).
| Semua route di sini wajib menyertakan header X-API-Key yang valid
| dan dibatasi rate limiter 'iot' (600 request/menit per key).
|
*/

Route::middleware(['api.key', 'throttle:iot'])->group(function () {

    Route::prefix('sensors')->group(function () {

        // Penerimaan satu pencatatan kepadatan dari sensor.
        // POST /api/v1/sensors/readings
        Route::post('/readings', [SensorReadingController::class, 'store']);

        // Smoke-test: cek cepat apakah API key valid (tanpa kirim data).
        // GET /api/v1/sensors/check
        Route::get('/check', function (Request $request) {
            /** @var \App\Models\ApiKey $apiKey */
            $apiKey = $request->attributes->get('api_key');

            return ApiResponse::success([
                'message'  => 'API key valid',
                'key_name' => $apiKey->name,
            ]);
        });
    });
});

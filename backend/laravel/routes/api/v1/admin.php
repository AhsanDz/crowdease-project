<?php

use App\Http\Controllers\Api\V1\Admin\ApiKeyController;
use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\RouteController as AdminRouteController;
use App\Http\Controllers\Api\V1\Admin\StopController as AdminStopController;
use App\Http\Controllers\Api\V1\Admin\VehicleController as AdminVehicleController;
use App\Http\Controllers\Api\V1\Admin\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route Operator (Titik Integrasi TI-4)
| Di-mount oleh routes/api.php di prefix /api/v1/admin/
|--------------------------------------------------------------------------
*/

// ── Tidak butuh auth ─────────────────────────────────────────────────
Route::middleware('throttle:public')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
});

// ── Butuh Sanctum bearer token (rate limit 120/menit per user) ────────
Route::middleware(['auth:sanctum', 'throttle:operator'])->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

    // Dashboard agregat (Fase 3)
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // CRUD master data (Fase 2)
    Route::apiResource('routes',   AdminRouteController::class);
    Route::apiResource('vehicles', AdminVehicleController::class);
    Route::apiResource('stops',    AdminStopController::class);

    // API Keys management (Fase 5)
    Route::get('apikeys',                [ApiKeyController::class, 'index']);
    Route::post('apikeys',               [ApiKeyController::class, 'store']);
    Route::patch('apikeys/{apiKey}/toggle', [ApiKeyController::class, 'toggle']);
    Route::delete('apikeys/{apiKey}',    [ApiKeyController::class, 'destroy']);

    // Webhooks management (Fase 5)
    Route::get('webhooks',                  [WebhookController::class, 'index']);
    Route::post('webhooks',                 [WebhookController::class, 'store']);
    Route::put('webhooks/{webhook}',        [WebhookController::class, 'update']);
    Route::delete('webhooks/{webhook}',     [WebhookController::class, 'destroy']);
    Route::post('webhooks/{webhook}/test',  [WebhookController::class, 'test']);
});

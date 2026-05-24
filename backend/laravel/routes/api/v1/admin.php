<?php

use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\RouteController as AdminRouteController;
use App\Http\Controllers\Api\V1\Admin\StopController as AdminStopController;
use App\Http\Controllers\Api\V1\Admin\VehicleController as AdminVehicleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route Operator (Titik Integrasi TI-4)
|--------------------------------------------------------------------------
| Di-mount oleh routes/api.php di prefix /api/v1/admin/.
| Karena nama controller "RouteController" sama dengan facade Route,
| kita import dengan alias.
*/

// ── Tidak butuh auth ────────────────────────────────────────────────
Route::middleware('throttle:public')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
});

// ── Butuh bearer token Sanctum (rate limit 120/menit per user) ───────
Route::middleware(['auth:sanctum', 'throttle:operator'])->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

    // Dashboard (Fase 3)
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // CRUD master data (Fase 2)
    Route::apiResource('routes',   AdminRouteController::class);
    Route::apiResource('vehicles', AdminVehicleController::class);
    Route::apiResource('stops',    AdminStopController::class);
});

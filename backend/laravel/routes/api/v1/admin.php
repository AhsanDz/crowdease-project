<?php

use App\Http\Controllers\Api\V1\Admin\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route Operator (Titik Integrasi TI-4)
|--------------------------------------------------------------------------
|
| Endpoint untuk dasbor operator. Sebagian besar wajib bearer token
| Sanctum — KECUALI endpoint login itu sendiri (karena login adalah
| cara MENDAPATKAN token).
|
| Endpoint CRUD master data (koridor, halte, armada, dll) akan
| ditambahkan pada langkah berikutnya.
|
*/

// ── Tidak butuh auth: hanya endpoint login ──────────────────────
// Dibatasi rate limiter 'public' (60/menit per IP) sebagai
// proteksi sederhana terhadap brute force.
Route::middleware('throttle:public')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
});

// ── Butuh bearer token Sanctum ──────────────────────────────────
Route::middleware(['auth:sanctum', 'throttle:operator'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);
});

<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Entry Point
|--------------------------------------------------------------------------
|
| Seluruh route API CrowdEase diberi prefix versi /v1. Definisi route
| dipecah per tier ke dalam routes/api/v1/ agar mudah dikelola:
|
|   public.php  : endpoint publik (penumpang)  — tanpa auth
|   iot.php     : endpoint IoT                  — butuh X-API-Key
|   admin.php   : endpoint operator             — butuh bearer token Sanctum
|
| Saat versi 2 dirilis nanti, cukup tambah blok prefix('v2') baru
| tanpa mengubah v1 — menjaga kompatibilitas mundur.
|
*/

Route::prefix('v1')->group(function () {
    require __DIR__ . '/api/v1/public.php';
    require __DIR__ . '/api/v1/iot.php';
    require __DIR__ . '/api/v1/admin.php';
});

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
|   public.php  : endpoint publik (penumpang)  — tanpa auth, di /v1/
|   iot.php     : endpoint IoT                  — butuh X-API-Key, di /v1/iot/
|   admin.php   : endpoint operator             — butuh bearer token, di /v1/admin/
|
| PENTING — Prefix /admin/ ditambahkan SAAT LOAD (bukan di dalam file
| admin.php). Tanpa prefix ini, route apiResource di admin.php (mis.
| 'routes', 'vehicles') akan COLLIDE dengan endpoint publik di public.php
| dengan nama sama — dan karena admin.php di-load belakangan, route admin
| akan menimpa publik, membuat aplikasi penumpang rusak (401/HTML).
|
| Saat versi 2 dirilis nanti, cukup tambah blok prefix('v2') baru
| tanpa mengubah v1 — menjaga kompatibilitas mundur.
|
*/

Route::prefix('v1')->group(function () {
    require __DIR__ . '/api/v1/public.php';

    require __DIR__ . '/api/v1/iot.php';

    // Admin di-mount di /v1/admin/ supaya tidak konflik dengan public.
    // Semua route di admin.php otomatis dapat prefix /admin/, jadi
    // /auth/login di file itu menjadi /api/v1/admin/auth/login, dst.
    Route::prefix('admin')->group(function () {
        require __DIR__ . '/api/v1/admin.php';
    });
});

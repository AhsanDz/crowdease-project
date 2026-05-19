<?php

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route Operator
|--------------------------------------------------------------------------
|
| Endpoint untuk dasbor operator. Wajib bearer token Sanctum dan
| dibatasi rate limiter 'operator' (120 request/menit per user).
|
| Endpoint login, dashboard, dan CRUD master data akan ditambahkan
| pada langkah berikutnya.
|
*/

Route::middleware(['auth:sanctum', 'throttle:operator'])->group(function () {

    // Info user yang sedang login — verifikasi bearer token bekerja.
    // GET /api/v1/me
    Route::get('/me', function (Request $request) {
        $user = $request->user();

        return ApiResponse::success([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ]);
    });
});

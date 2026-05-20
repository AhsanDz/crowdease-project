<?php

use App\Http\Controllers\Passenger\PassengerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Aplikasi Penumpang)
|--------------------------------------------------------------------------
|
| Hanya dua route web: landing page (pilih koridor) dan halaman peta.
| Keduanya mengembalikan HTML kerangka yang kemudian memuat data dari
| /api/v1/* via JavaScript di browser.
|
| Route admin (dasbor operator) akan ditambahkan pada langkah berikutnya
| ketika antarmuka dasbor mulai dibangun.
|
*/

Route::get('/', [PassengerController::class, 'home'])
    ->name('passenger.home');

Route::get('/koridor/{code}', [PassengerController::class, 'map'])
    ->name('passenger.map')
    ->where('code', '[A-Za-z0-9]+');

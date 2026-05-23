<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Passenger\PassengerController;

Route::get('/', [PassengerController::class, 'home'])->name('home');

Route::get('/koridor/{code}', [PassengerController::class, 'map'])->name('passenger.map');

/**
 * Tab "Peta" tidak menerima parameter koridor — kita redirect ke peta koridor
 * pertama yang ada di database. Cukup untuk pengalaman tap-tab di mobile.
 */
Route::get('/peta', function () {
    $first = \App\Models\Route::orderBy('code')->firstOrFail();
    return redirect()->route('passenger.map', $first->code);
})->name('passenger.peta');

Route::get('/halte', [PassengerController::class, 'stops'])->name('passenger.stops');
Route::get('/halte/{stop}', [PassengerController::class, 'stopDetail'])->name('passenger.stop-detail');

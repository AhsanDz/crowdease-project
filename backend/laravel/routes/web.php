<?php

use App\Http\Controllers\Operator\PageController as OperatorPageController;
use App\Http\Controllers\Passenger\PassengerController;
use Illuminate\Support\Facades\Route;

// ──────────────────────────────────────────────────────────────────────
// Passenger (mobile web)
// ──────────────────────────────────────────────────────────────────────

Route::get('/', [PassengerController::class, 'home'])->name('home');

Route::get('/koridor/{code}', [PassengerController::class, 'map'])->name('passenger.map');

Route::get('/peta', function () {
    $first = \App\Models\Route::orderBy('code')->firstOrFail();
    return redirect()->route('passenger.map', $first->code);
})->name('passenger.peta');

Route::get('/halte',        [PassengerController::class, 'stops'])->name('passenger.stops');
Route::get('/halte/{stop}', [PassengerController::class, 'stopDetail'])->name('passenger.stop-detail');


// ──────────────────────────────────────────────────────────────────────
// Operator (desktop web) — semua 7 halaman sudah fungsional di Fase 5
// ──────────────────────────────────────────────────────────────────────

Route::prefix('operator')->name('operator.')->group(function () {
    Route::get('/login',     [OperatorPageController::class, 'login'])->name('login');
    Route::get('/dashboard', [OperatorPageController::class, 'dashboard'])->name('dashboard');
    Route::get('/routes',    [OperatorPageController::class, 'routes'])->name('routes');
    Route::get('/vehicles',  [OperatorPageController::class, 'vehicles'])->name('vehicles');
    Route::get('/stops',     [OperatorPageController::class, 'stops'])->name('stops');
    Route::get('/apikeys',   [OperatorPageController::class, 'apikeys'])->name('apikeys');
    Route::get('/webhooks',  [OperatorPageController::class, 'webhooks'])->name('webhooks');

    Route::redirect('/', '/operator/dashboard');
});

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

Route::get('/halte',         [PassengerController::class, 'stops'])->name('passenger.stops');
Route::get('/halte/{stop}',  [PassengerController::class, 'stopDetail'])->name('passenger.stop-detail');


// ──────────────────────────────────────────────────────────────────────
// Operator (desktop web)
// ──────────────────────────────────────────────────────────────────────

Route::prefix('operator')->name('operator.')->group(function () {
    Route::get('/login',     [OperatorPageController::class, 'login'])->name('login');
    Route::get('/dashboard', [OperatorPageController::class, 'dashboard'])->name('dashboard');

    // Halaman menu yang belum di-implementasi di Fase 3.
    // Akan diganti controller fungsional di Fase 4 (routes/vehicles/stops)
    // dan Fase 5 (apikeys/webhooks).
    Route::get('/{page}', [OperatorPageController::class, 'comingSoon'])
        ->where('page', 'routes|vehicles|stops|apikeys|webhooks')
        ->name('coming-soon');

    // Operator base URL — redirect ke dashboard supaya navigasi lebih intuitif
    Route::redirect('/', '/operator/dashboard');
});

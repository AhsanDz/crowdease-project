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

    // Fase 4: CRUD pages
    Route::get('/routes',    [OperatorPageController::class, 'routes'])->name('routes');
    Route::get('/vehicles',  [OperatorPageController::class, 'vehicles'])->name('vehicles');
    Route::get('/stops',     [OperatorPageController::class, 'stops'])->name('stops');

    // Fase 5 (belum): apikeys & webhooks tetap "coming-soon"
    Route::get('/{page}', [OperatorPageController::class, 'comingSoon'])
        ->where('page', 'apikeys|webhooks')
        ->name('coming-soon');

    Route::redirect('/', '/operator/dashboard');
});

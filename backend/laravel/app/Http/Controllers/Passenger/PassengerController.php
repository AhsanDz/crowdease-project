<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\Route;
use App\Models\Stop;

class PassengerController extends Controller
{
    /**
     * GET /
     * Halaman beranda: daftar koridor.
     */
    public function home()
    {
        return view('passenger.home');
    }

    /**
     * GET /koridor/{code}
     * Halaman peta koridor. Resolve code -> Route untuk dipass ke view.
     * Note: tabel routes belum punya kolom 'status' — jangan filter di sini.
     */
    public function map(string $code)
    {
        $route = Route::where('code', $code)->firstOrFail();
        return view('passenger.map', ['route' => $route]);
    }

    /**
     * GET /halte
     * Daftar semua halte di semua koridor (dengan search di sisi client).
     */
    public function stops()
    {
        return view('passenger.stops');
    }

    /**
     * GET /halte/{stop}
     * Detail satu halte: posisi, koridor pemilik, dan armada yang sedang mendekat.
     */
    public function stopDetail(int $stop)
    {
        $stopModel = Stop::with('route')->findOrFail($stop);
        return view('passenger.stop-detail', ['stop' => $stopModel]);
    }
}

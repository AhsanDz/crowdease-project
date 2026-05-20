<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\Route;
use Illuminate\View\View;

/**
 * Controller untuk halaman web aplikasi penumpang.
 *
 * Catatan arsitektur: controller ini sengaja minimal — ia hanya
 * mengembalikan kerangka HTML kosong. Seluruh data (daftar koridor,
 * halte, armada, kepadatan, forecast) di-fetch oleh JavaScript di
 * sisi browser melalui /api/v1/* secara langsung.
 *
 * Pendekatan ini membuat titik integrasi TI-3 (Backend -> Passenger
 * App) terlihat sebagai cerita yang utuh: aplikasi penumpang
 * benar-benar bertindak sebagai KLIEN dari REST API, sama seperti
 * yang akan dilakukan aplikasi mobile pihak ketiga di kemudian hari.
 * Saat demo, buka DevTools -> Network untuk menunjukkan request
 * polling berulang yang masuk ke /api/v1/routes/{id}/vehicles.
 */
class PassengerController extends Controller
{
    /**
     * Halaman utama — pilih koridor.
     */
    public function home(): View
    {
        return view('passenger.home');
    }

    /**
     * Halaman peta koridor — menampilkan halte dan armada real-time.
     *
     * Parameter $code adalah kode koridor (mis. "K1") dari URL.
     * Eloquent lookup di sini hanya untuk validasi server-side dan
     * meneruskan id koridor ke JavaScript di view.
     */
    public function map(string $code): View
    {
        $route = Route::where('code', $code)
            ->where('is_active', true)
            ->firstOrFail();

        return view('passenger.map', ['route' => $route]);
    }
}

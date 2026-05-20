<?php

namespace App\Http\Controllers\Api\V1\Passenger;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DensityLogResource;
use App\Http\Resources\Api\V1\ForecastResource;
use App\Models\Vehicle;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint armada untuk aplikasi penumpang (titik integrasi TI-3).
 *
 * Detail kepadatan terkini sudah tertanam di endpoint
 * /routes/{route}/vehicles agar polling cukup satu request per
 * koridor. Controller ini menyediakan dua endpoint detail yang
 * dipanggil saat penumpang menge-tap satu armada.
 */
class VehicleController extends Controller
{
    /**
     * GET /api/v1/vehicles/{vehicle}/forecast
     *
     * Tiga forecast (5 / 10 / 15 menit ke depan) untuk armada ini.
     * Forecast di-generate oleh ForecastingService setiap kali ada
     * pencatatan sensor baru, jadi pengguna akan mendapat prediksi
     * berdasarkan data paling baru.
     */
    public function forecast(Vehicle $vehicle): JsonResponse
    {
        $forecasts = $vehicle->forecasts()
            ->orderBy('predicted_for')
            ->get();

        return ApiResponse::success([
            'vehicle_id'    => $vehicle->id,
            'model_version' => $forecasts->first()?->model_version,
            'count'         => $forecasts->count(),
            'forecasts'     => ForecastResource::collection($forecasts),
        ]);
    }

    /**
     * GET /api/v1/vehicles/{vehicle}/density/history?minutes=30
     *
     * Riwayat pencatatan kepadatan dalam jendela waktu tertentu.
     * Dipakai oleh aplikasi penumpang untuk menggambar grafik tren
     * kecil (sparkline) saat armada di-tap.
     *
     * Query parameter:
     *   - minutes (int, default 30, range 1-180)
     */
    public function densityHistory(Vehicle $vehicle, Request $request): JsonResponse
    {
        $minutes = (int) $request->query('minutes', '30');
        $minutes = max(1, min(180, $minutes));

        $logs = $vehicle->densityLogs()
            ->recent($minutes)
            ->orderBy('recorded_at')
            ->limit(500) // sanity cap: ~1 reading/detik selama 8 menit
            ->get();

        return ApiResponse::success([
            'vehicle_id'     => $vehicle->id,
            'window_minutes' => $minutes,
            'count'          => $logs->count(),
            'logs'           => DensityLogResource::collection($logs),
        ]);
    }
}

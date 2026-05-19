<?php

namespace App\Http\Controllers\Api\V1\Iot;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSensorReadingRequest;
use App\Models\DensityLog;
use App\Models\Vehicle;
use App\Services\ForecastingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Controller penerimaan data sensor dari perangkat IoT.
 *
 * Titik integrasi TI-1 — endpoint yang dikonsumsi IoT Simulator.
 *
 * Endpoint : POST /api/v1/sensors/readings
 * Auth     : middleware api.key (header X-API-Key)
 */
class SensorReadingController extends Controller
{
    public function __construct(
        private readonly ForecastingService $forecastingService
    ) {
    }

    /**
     * Terima satu pencatatan kepadatan dari perangkat IoT.
     *
     * Alur:
     *   1. Validasi payload (oleh StoreSensorReadingRequest).
     *   2. Hitung occupancy_ratio lalu simpan ke tabel density_logs.
     *   3. Jalankan ForecastingService untuk memperbarui prediksi.
     *   4. Kembalikan response 201 sesuai API Contract.
     */
    public function store(StoreSensorReadingRequest $request): JsonResponse
    {
        $data = $request->validated();

        $vehicle = Vehicle::findOrFail($data['vehicle_id']);

        // occupancy_ratio = penumpang / kapasitas saat itu (3 angka desimal,
        // sesuai kolom decimal(4,3) pada tabel density_logs).
        $ratio = round($data['passenger_count'] / $data['capacity_at_time'], 3);

        $densityLog = DensityLog::create([
            'vehicle_id'       => $vehicle->id,
            'passenger_count'  => $data['passenger_count'],
            'capacity_at_time' => $data['capacity_at_time'],
            'occupancy_ratio'  => $ratio,
            'recorded_at'      => $data['recorded_at'],
        ]);

        // Perbarui prediksi kepadatan untuk kendaraan ini berdasarkan
        // pencatatan terbaru (termasuk yang baru saja disimpan).
        $forecasts = $this->forecastingService->forecast($vehicle);

        // CATATAN: di titik ini nantinya event DensityRecorded di-dispatch
        // untuk memicu webhook outbound (TI-2). Akan ditambahkan pada
        // langkah bonus webhook.

        return ApiResponse::success([
            'id'                 => $densityLog->id,
            'vehicle_id'         => $densityLog->vehicle_id,
            'passenger_count'    => $densityLog->passenger_count,
            'capacity_at_time'   => $densityLog->capacity_at_time,
            'occupancy_ratio'    => $ratio,
            'occupancy_level'    => $densityLog->occupancy_level,
            'recorded_at'        => $densityLog->recorded_at->toIso8601String(),
            'forecast_triggered' => $forecasts->isNotEmpty(),
        ], null, 201);
    }
}

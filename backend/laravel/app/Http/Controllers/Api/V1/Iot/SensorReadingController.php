<?php

namespace App\Http\Controllers\Api\V1\Iot;

use App\Http\Controllers\Controller;
use App\Models\DensityLog;
use App\Models\Vehicle;
use App\Services\ForecastingService;
use App\Services\WebhookDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SensorReadingController
 *
 * POST /api/v1/iot/sensors/readings
 *
 * Menerima data sensor dari IoT Simulator, simpan ke density_logs,
 * jalankan forecasting, lalu trigger webhook jika level berubah.
 */
class SensorReadingController extends Controller
{
    public function __construct(
        private readonly ForecastingService $forecasting,
        private readonly WebhookDispatcher  $webhook,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_id'      => ['required', 'integer', 'exists:vehicles,id'],
            'passenger_count' => ['required', 'integer', 'min:0'],
            'recorded_at'     => ['required', 'date'],
        ]);

        /** @var Vehicle $vehicle */
        $vehicle = Vehicle::with('route')
            ->where('id', $validated['vehicle_id'])
            ->firstOrFail();

        // Level sebelum reading ini masuk
        $previousLevel = $vehicle->latestDensityLog?->occupancy_level;

        // Buat density log — boot() di model otomatis hitung ratio & level
        /** @var DensityLog $log */
        $log = DensityLog::create([
            'vehicle_id'       => $vehicle->id,
            'passenger_count'  => $validated['passenger_count'],
            'capacity_at_time' => $vehicle->capacity,
            'recorded_at'      => $validated['recorded_at'],
        ]);

        $currentLevel = $log->occupancy_level;

        // Jalankan forecasting — return array of forecast data
        /** @var array<int, array<string, mixed>> $forecasts */
        $forecasts = $this->forecasting->forecast($vehicle->id);

        // Bangun payload untuk webhook
        $payload = $this->buildPayload($vehicle, $log, $forecasts);

        // Deteksi perubahan level dan dispatch event yang sesuai
        $this->dispatchLevelEvents($previousLevel, $currentLevel, $payload);

        return response()->json([
            'message' => 'Sensor reading berhasil dicatat.',
            'data'    => [
                'log_id'          => $log->id,
                'occupancy_level' => $currentLevel,
                'occupancy_ratio' => $log->occupancy_ratio,
                'forecasts'       => $forecasts,
            ],
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────────────

    /**
     * Dispatch webhook event berdasarkan perubahan level.
     *
     * Naik ke high/overcrowded  → density.high_threshold_crossed
     * Turun ke medium/low       → density.low_threshold_recovered
     * Selalu                    → density.recorded
     */
    private function dispatchLevelEvents(
        ?string $previousLevel,
        string  $currentLevel,
        array   $payload
    ): void {
        // Selalu dispatch density.recorded
        $this->webhook->dispatch('density.recorded', $payload);

        $dangerLevels = ['high', 'overcrowded'];
        $normalLevels = ['low', 'medium'];

        $wasNormal = in_array($previousLevel, $normalLevels) || $previousLevel === null;
        $isDanger  = in_array($currentLevel, $dangerLevels);

        if ($wasNormal && $isDanger) {
            $this->webhook->dispatch('density.high_threshold_crossed', $payload);
            return;
        }

        $wasDanger = in_array($previousLevel, $dangerLevels);
        $isNormal  = in_array($currentLevel, $normalLevels);

        if ($wasDanger && $isNormal) {
            $this->webhook->dispatch('density.low_threshold_recovered', $payload);
        }
    }

    /**
     * Bangun payload standar untuk semua event.
     */
    private function buildPayload(Vehicle $vehicle, DensityLog $log, array $forecasts): array
    {
        return [
            'vehicle' => [
                'id'           => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'route_code'   => $vehicle->route->code ?? '-',
                'route_name'   => $vehicle->route->name ?? '-',
            ],
            'density' => [
                'passenger_count'  => $log->passenger_count,
                'capacity'         => $log->capacity_at_time,
                'occupancy_ratio'  => $log->occupancy_ratio,
                'occupancy_level'  => $log->occupancy_level,
                'recorded_at'      => $log->recorded_at
                    ->timezone('Asia/Jakarta')
                    ->toIso8601String(),
            ],
            'forecasts' => $forecasts,
        ];
    }
}
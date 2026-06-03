<?php

namespace App\Observers;

use App\Models\DensityLog;
use App\Services\WebhookDispatchService;

/**
 * Observer untuk DensityLog.
 *
 * Dipasang di AppServiceProvider::boot():
 *   DensityLog::observe(DensityLogObserver::class);
 *
 * Memisahkan side-effect (webhook dispatch) dari controller utama (SensorReadingController).
 * Pendekatan ini membuat controller bersih tanpa perlu tahu soal webhook.
 */
class DensityLogObserver
{
    /**
     * Dipanggil setiap kali DensityLog baru disimpan ke database.
     * Dispatch webhook hanya saat kepadatan ≥ 85% (level "padat").
     */
    public function created(DensityLog $densityLog): void
    {
        $ratio = (float) $densityLog->occupancy_ratio;
        if ($ratio < 0.85) return;

        // Load vehicle + route untuk data payload yang lebih informatif
        $vehicle = $densityLog->vehicle ?? $densityLog->load('vehicle')->vehicle;
        if (!$vehicle) return;

        $event = $ratio >= 0.95 ? 'density.critical' : 'density.alert';

        WebhookDispatchService::dispatch($event, [
            'vehicle_id'      => $vehicle->id,
            'plate_number'    => $vehicle->plate_number,
            'route_code'      => optional($vehicle->route)->code,
            'route_name'      => optional($vehicle->route)->name,
            'occupancy_ratio' => round($ratio, 4),
            'occupancy_pct'   => (int) round($ratio * 100),
            'level'           => $densityLog->occupancy_level ?? ($ratio >= 0.95 ? 'kritis' : 'padat'),
            'passenger_count' => $densityLog->passenger_count,
            'capacity'        => $vehicle->capacity,
            'recorded_at'     => $densityLog->recorded_at?->toIso8601String()
                                    ?? now()->toIso8601String(),
        ]);
    }
}

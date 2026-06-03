<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk respons untuk model DensityLog (pencatatan kepadatan).
 *
 * Dipakai dalam dua konteks:
 *   1. Tertanam (embedded) di dalam VehicleResource sebagai "latest_density"
 *   2. Standalone dalam respons history (/vehicles/{id}/density/history)
 *
 * Field "occupancy_level" dihasilkan oleh accessor pada model DensityLog
 * (low / medium / high / overcrowded) berdasarkan occupancy_ratio.
 */
class DensityLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'passenger_count'  => (int) $this->passenger_count,
            'capacity_at_time' => (int) $this->capacity_at_time,
            'occupancy_ratio'  => (float) $this->occupancy_ratio,
            'occupancy_level'  => $this->occupancy_level,
            'recorded_at'      => $this->recorded_at?->toIso8601String(),
        ];
    }
}

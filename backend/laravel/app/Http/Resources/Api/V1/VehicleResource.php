<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk respons untuk model Vehicle (armada).
 *
 * Bila relasi latestDensityLog sudah eager-loaded di query, field
 * "latest_density" akan disertakan; jika tidak, field itu tidak muncul
 * sama sekali. Pola ini menghindari N+1 query secara tidak sengaja:
 * controller harus eksplisit memanggil ->with('latestDensityLog') untuk
 * mendapat kepadatan terkini.
 */
class VehicleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'plate_number'   => $this->plate_number,
            'route_id'       => $this->route_id,
            'capacity'       => (int) $this->capacity,
            'status'         => $this->status,
            'latest_density' => $this->whenLoaded(
                'latestDensityLog',
                fn () => $this->latestDensityLog
                    ? new DensityLogResource($this->latestDensityLog)
                    : null
            ),
        ];
    }
}

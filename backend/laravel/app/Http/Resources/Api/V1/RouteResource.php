<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk respons untuk model Route (koridor).
 *
 * Field stops_count dan vehicles_count hanya disertakan jika query
 * yang menghasilkan resource ini memakai withCount(['stops','vehicles']).
 */
class RouteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'code'           => $this->code,
            'name'           => $this->name,
            'color'          => $this->color,
            'is_active'      => (bool) $this->is_active,
            'stops_count'    => $this->whenCounted('stops'),
            'vehicles_count' => $this->whenCounted('vehicles'),
        ];
    }
}

<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk respons untuk model Stop (halte).
 *
 * Latitude dan longitude di-cast ke float supaya bersih di JSON
 * (cast bawaan model menghasilkan string karena tipe decimal).
 */
class StopResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'latitude'  => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'sequence'  => (int) $this->sequence,
        ];
    }
}

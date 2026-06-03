<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk respons untuk model User (operator).
 *
 * Hanya menampilkan field yang aman untuk dibagikan ke klien.
 * Password sudah otomatis disembunyikan oleh $hidden pada model User,
 * tapi kita tetap eksplisit di sini agar tidak ada kebocoran tidak sengaja.
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
        ];
    }
}

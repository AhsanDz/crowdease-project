<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk respons untuk model Forecast.
 *
 * Field "minutes_ahead" dihitung di sisi server saat serialize, agar
 * UI tidak perlu menghitung selisih waktu sendiri. Nilainya bisa 0
 * jika forecast nyaris kadaluwarsa antara saat di-generate dan saat
 * di-serve (dibatasi minimal 0 supaya tidak negatif).
 */
class ForecastResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'predicted_count' => (int) $this->predicted_count,
            'predicted_for'   => $this->predicted_for?->toIso8601String(),
            'minutes_ahead'   => $this->minutesAhead(),
            'model_version'   => $this->model_version,
        ];
    }

    /**
     * Selisih menit antara sekarang dan predicted_for, dibatasi >= 0.
     */
    private function minutesAhead(): ?int
    {
        if ($this->predicted_for === null) {
            return null;
        }

        $diffSeconds = $this->predicted_for->getTimestamp() - now()->getTimestamp();

        return max(0, (int) round($diffSeconds / 60));
    }
}

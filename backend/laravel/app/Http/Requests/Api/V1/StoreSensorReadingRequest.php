<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi payload untuk POST /api/v1/sensors/readings.
 *
 * Aturan mengikuti "CrowdEase API Contract v1.0" Section 3.1.
 */
class StoreSensorReadingRequest extends FormRequest
{
    /**
     * Autorisasi sudah ditangani middleware api.key (header X-API-Key),
     * jadi di tahap form request ini cukup mengizinkan.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi untuk tiap field payload.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'vehicle_id'       => ['required', 'integer', 'exists:vehicles,id'],
            'passenger_count'  => ['required', 'integer', 'min:0'],
            'capacity_at_time' => ['required', 'integer', 'min:1'],
            'recorded_at'      => [
                'required',
                'date',
                // Tidak boleh lebih dari 5 menit ke depan (toleransi clock skew).
                'before_or_equal:' . now()->addMinutes(5)->toDateTimeString(),
            ],
        ];
    }

    /**
     * Pesan error kustom dalam Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vehicle_id.required'         => 'Field vehicle_id wajib diisi.',
            'vehicle_id.integer'          => 'Field vehicle_id harus berupa angka.',
            'vehicle_id.exists'           => 'Kendaraan dengan ID tersebut tidak ditemukan.',
            'passenger_count.required'    => 'Field passenger_count wajib diisi.',
            'passenger_count.integer'     => 'Field passenger_count harus berupa angka.',
            'passenger_count.min'         => 'Field passenger_count tidak boleh negatif.',
            'capacity_at_time.required'   => 'Field capacity_at_time wajib diisi.',
            'capacity_at_time.integer'    => 'Field capacity_at_time harus berupa angka.',
            'capacity_at_time.min'        => 'Field capacity_at_time minimal bernilai 1.',
            'recorded_at.required'        => 'Field recorded_at wajib diisi.',
            'recorded_at.date'            => 'Field recorded_at harus berupa tanggal/waktu yang valid.',
            'recorded_at.before_or_equal' => 'Waktu pencatatan tidak boleh lebih dari 5 menit ke depan.',
        ];
    }
}

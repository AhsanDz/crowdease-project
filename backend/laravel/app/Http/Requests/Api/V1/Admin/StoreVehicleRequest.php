<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi pembuatan armada baru.
 */
class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'route_id'     => ['required', 'integer', 'exists:routes,id'],
            'plate_number' => ['required', 'string', 'max:20', 'unique:vehicles,plate_number'],
            'capacity'     => ['required', 'integer', 'min:1', 'max:500'],
            'status'       => ['sometimes', 'string', 'in:active,inactive,maintenance'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plate_number.unique' => 'Nomor plat sudah terdaftar.',
            'route_id.exists'     => 'Koridor tidak ditemukan.',
            'capacity.min'        => 'Kapasitas minimal 1 penumpang.',
            'capacity.max'        => 'Kapasitas maksimal 500 penumpang.',
            'status.in'           => 'Status harus salah satu dari: active, inactive, maintenance.',
        ];
    }
}

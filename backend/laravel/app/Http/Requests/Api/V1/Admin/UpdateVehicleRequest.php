<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
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
        $vehicleId = $this->route('vehicle')->id ?? null;

        return [
            'route_id'     => ['sometimes', 'required', 'integer', 'exists:routes,id'],
            'plate_number' => ['sometimes', 'required', 'string', 'max:20',
                               Rule::unique('vehicles', 'plate_number')->ignore($vehicleId)],
            'capacity'     => ['sometimes', 'required', 'integer', 'min:1', 'max:500'],
            'status'       => ['sometimes', 'required', 'string', 'in:active,inactive,maintenance'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plate_number.unique' => 'Nomor plat sudah terdaftar di armada lain.',
            'route_id.exists'     => 'Koridor tidak ditemukan.',
            'status.in'           => 'Status harus salah satu dari: active, inactive, maintenance.',
        ];
    }
}

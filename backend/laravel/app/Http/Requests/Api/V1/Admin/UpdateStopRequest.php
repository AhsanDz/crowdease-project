<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStopRequest extends FormRequest
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
        $stop = $this->route('stop');
        $stopId = $stop?->id;

        // Untuk validasi sequence-unique, gunakan route_id dari body (jika ada)
        // atau yang lama (jika tidak diubah).
        $routeIdForUniqueCheck = $this->input('route_id') ?? $stop?->route_id;

        return [
            'route_id'  => ['sometimes', 'required', 'integer', 'exists:routes,id'],
            'name'      => ['sometimes', 'required', 'string', 'max:100'],
            'latitude'  => ['sometimes', 'required', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'required', 'numeric', 'between:-180,180'],
            'sequence'  => [
                'sometimes', 'required', 'integer', 'min:1',
                Rule::unique('stops', 'sequence')
                    ->where('route_id', $routeIdForUniqueCheck)
                    ->ignore($stopId),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'route_id.exists'    => 'Koridor tidak ditemukan.',
            'sequence.unique'    => 'Urutan halte sudah dipakai di koridor ini.',
            'latitude.between'   => 'Latitude harus antara -90 dan 90.',
            'longitude.between'  => 'Longitude harus antara -180 dan 180.',
        ];
    }
}

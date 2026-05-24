<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi pembuatan halte baru.
 *
 * Sequence harus unik PER koridor (boleh sama antar koridor, mis. K1 dan K2
 * boleh sama-sama punya halte sequence 1).
 */
class StoreStopRequest extends FormRequest
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
            'route_id'  => ['required', 'integer', 'exists:routes,id'],
            'name'      => ['required', 'string', 'max:100'],
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'sequence'  => [
                'required', 'integer', 'min:1',
                Rule::unique('stops', 'sequence')->where('route_id', $this->input('route_id')),
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

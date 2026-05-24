<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi pembuatan koridor baru.
 *
 * Auth dicek di route middleware (auth:sanctum), jadi authorize() = true.
 */
class StoreRouteRequest extends FormRequest
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
            'code'      => ['required', 'string', 'max:10', 'unique:routes,code'],
            'name'      => ['required', 'string', 'max:100'],
            'color'     => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.unique'   => 'Kode koridor sudah dipakai.',
            'color.regex'   => 'Format warna harus hex (mis. #E11D2A).',
        ];
    }
}

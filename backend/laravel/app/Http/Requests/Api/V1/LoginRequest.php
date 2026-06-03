<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi payload untuk POST /api/v1/auth/login.
 *
 * device_name bersifat opsional — kalau diisi, dipakai sebagai nama
 * token Sanctum sehingga operator bisa membedakan sesi di banyak
 * perangkat (mis. "Chrome di laptop", "Edge di kantor").
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email'       => ['required', 'email', 'max:255'],
            'password'    => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required'    => 'Field email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Field password wajib diisi.',
            'device_name.max'   => 'Nama perangkat maksimal 120 karakter.',
        ];
    }
}

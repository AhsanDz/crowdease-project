<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi update koridor.
 *
 * Semua field optional (partial update via PUT/PATCH dengan "sometimes"),
 * tetapi bila ada di body harus valid. Aturan unique mengecualikan
 * record yang sedang di-update sendiri.
 */
class UpdateRouteRequest extends FormRequest
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
        $routeId = $this->route('route')->id ?? null;

        return [
            'code'      => ['sometimes', 'required', 'string', 'max:10',
                            Rule::unique('routes', 'code')->ignore($routeId)],
            'name'      => ['sometimes', 'required', 'string', 'max:100'],
            'color'     => ['sometimes', 'required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'Kode koridor sudah dipakai koridor lain.',
            'color.regex' => 'Format warna harus hex (mis. #E11D2A).',
        ];
    }
}

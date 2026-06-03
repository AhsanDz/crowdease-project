<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manajemen API Keys untuk perangkat IoT.
 *
 * Prinsip keamanan kunci:
 *  - Plaintext key HANYA dikembalikan sekali saat create.
 *  - Yang tersimpan di DB adalah SHA-256 hash (key_hash).
 *  - Tidak ada cara untuk retrieve plaintext setelah itu.
 *
 * Format key: "ce_iot_" + 32 hex chars = 39 karakter.
 */
class ApiKeyController extends Controller
{
    /**
     * GET /api/v1/admin/apikeys
     * List semua API keys (preview saja, bukan plaintext).
     */
    public function index(): JsonResponse
    {
        $keys = ApiKey::orderByDesc('id')
            ->get()
            ->map(fn ($k) => $this->format($k));

        return ApiResponse::success($keys);
    }

    /**
     * POST /api/v1/admin/apikeys
     * Buat API key baru. Plaintext key HANYA ada di response ini — tidak pernah disimpan.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        // Format: ce_iot_ + 32 hex chars (16 random bytes)
        $plaintext = 'ce_iot_' . bin2hex(random_bytes(16));
        $hash      = hash('sha256', $plaintext);

        $apiKey = ApiKey::create([
            'user_id'  => auth()->id(),
            'name'     => $request->name,
            'key_hash' => $hash,
        ]);

        return ApiResponse::success([
            'id'         => $apiKey->id,
            'name'       => $apiKey->name,
            'key'        => $plaintext,   // ← SATU-satunya kali plaintext tampil
            'is_active'  => true,
        ], null, 201);
    }

    /**
     * PATCH /api/v1/admin/apikeys/{apiKey}/toggle
     * Aktifkan atau nonaktifkan key tanpa menghapusnya.
     */
    public function toggle(ApiKey $apiKey): JsonResponse
    {
        // Toggle: jika revoked_at null (aktif) → set ke now (nonaktif), dan sebaliknya
        $apiKey->update([
            'revoked_at' => $apiKey->revoked_at === null ? now() : null,
        ]);

        $isActive = $apiKey->revoked_at === null;

        return ApiResponse::success([
            'id'        => $apiKey->id,
            'is_active' => $isActive,
        ]);
    }

    /**
     * DELETE /api/v1/admin/apikeys/{apiKey}
     * Revoke (hapus permanen) API key.
     */
    public function destroy(ApiKey $apiKey): JsonResponse
    {
        $apiKey->delete();

        return ApiResponse::success(null);
    }

    private function format(ApiKey $k): array
    {
        return [
            'id'           => $k->id,
            'name'         => $k->name,
            // Preview: ce_iot_XXXXXX... (tidak mengungkap hash asli)
            'key_preview'  => 'ce_iot_' . substr($k->key_hash, 0, 6) . '••••••',
            'is_active'    => $k->revoked_at === null,
            'last_used_at' => $k->last_used_at?->diffForHumans(),
            'created_at'   => $k->last_used_at?->toDateTimeString() ?? '—',
        ];
    }
}

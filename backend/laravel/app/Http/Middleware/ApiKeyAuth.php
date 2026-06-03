<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware autentikasi untuk perangkat IoT (titik integrasi TI-1).
 *
 * Memvalidasi header X-API-Key terhadap tabel api_keys. Dipakai pada
 * endpoint /api/v1/sensors/* yang menerima data dari IoT simulator.
 *
 * Cara kerja:
 * 1. Ambil nilai header X-API-Key dari request.
 * 2. Hitung hash SHA-256 dari key tersebut.
 * 3. Cari baris api_keys yang key_hash-nya cocok DAN belum dicabut.
 * 4. Jika tidak ada, tolak dengan 401. Jika ada, lanjutkan request.
 *
 * Mengapa SHA-256, bukan bcrypt:
 * - Kolom key_hash bertipe char(64) = panjang persis hex digest SHA-256.
 * - API key perlu DICARI berdasarkan hash-nya (terima key -> hash -> lookup).
 * - SHA-256 bersifat deterministik sehingga lookup via index berjalan O(1).
 * - bcrypt menghasilkan hash berbeda tiap kali (bersalt), tidak bisa di-lookup.
 * - API key di-generate acak dengan entropi tinggi, jadi hash cepat aman.
 *   (bcrypt hanya perlu untuk PASSWORD yang entropinya rendah.)
 */
class ApiKeyAuth
{
    /**
     * Tangani request yang masuk.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $providedKey = $request->header('X-API-Key');

        if (empty($providedKey)) {
            return ApiResponse::error(
                'UNAUTHORIZED',
                'Header X-API-Key wajib disertakan.',
                null,
                401
            );
        }

        $hash = hash('sha256', $providedKey);

        $apiKey = ApiKey::query()
            ->where('key_hash', $hash)
            ->whereNull('revoked_at')
            ->first();

        if ($apiKey === null) {
            return ApiResponse::error(
                'UNAUTHORIZED',
                'API key tidak valid atau sudah dicabut.',
                null,
                401
            );
        }

        // Catat waktu pemakaian terakhir untuk audit.
        $apiKey->last_used_at = now();
        $apiKey->save();

        // Sediakan objek ApiKey ke controller lewat request attributes,
        // sehingga controller bisa tahu key mana yang dipakai bila perlu.
        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }
}

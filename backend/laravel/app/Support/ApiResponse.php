<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Helper untuk menghasilkan response JSON yang konsisten dengan
 * "CrowdEase API Contract v1.0" (lihat docs/API_CONTRACT.md).
 *
 * Dipakai oleh middleware, controller, dan exception handler agar
 * SEMUA response API memakai amplop (envelope) yang sama.
 *
 * Format sukses:
 *   { "success": true, "data": {...}, "meta": {...} }
 *
 * Format gagal:
 *   { "success": false, "error": { "code": "...", "message": "...", "details": {...} } }
 */
class ApiResponse
{
    /**
     * Bangun response sukses.
     *
     * @param  mixed       $data   Payload utama (objek, array, atau null).
     * @param  array|null  $meta   Metadata opsional (mis. info pagination).
     * @param  int         $status Kode status HTTP (default 200).
     */
    public static function success(mixed $data = null, ?array $meta = null, int $status = 200): JsonResponse
    {
        $payload = [
            'success' => true,
            'data'    => $data,
        ];

        if ($meta !== null) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * Bangun response gagal.
     *
     * @param  string      $code    Kode error mesin (mis. VALIDATION_FAILED).
     * @param  string      $message Pesan yang dapat dibaca manusia.
     * @param  array|null  $details Rincian error opsional (mis. error per-field).
     * @param  int         $status  Kode status HTTP (default 400).
     */
    public static function error(string $code, string $message, ?array $details = null, int $status = 400): JsonResponse
    {
        $error = [
            'code'    => $code,
            'message' => $message,
        ];

        if ($details !== null) {
            $error['details'] = $details;
        }

        return response()->json([
            'success' => false,
            'error'   => $error,
        ], $status);
    }
}

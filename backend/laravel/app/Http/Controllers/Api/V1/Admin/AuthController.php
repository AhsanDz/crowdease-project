<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Controller autentikasi operator.
 *
 * Endpoint:
 *   POST /api/v1/auth/login   - tukar email+password dengan bearer token
 *   POST /api/v1/auth/logout  - cabut token yang sedang dipakai
 *   GET  /api/v1/auth/me      - info user yang sedang login
 *
 * Memakai Laravel Sanctum untuk penerbitan dan validasi bearer token.
 * Token disimpan di tabel personal_access_tokens.
 */
class AuthController extends Controller
{
    /**
     * Login operator dan terbitkan bearer token Sanctum.
     *
     * Saat kredensial salah, mengembalikan pesan generik "Email atau
     * password salah" — bukan "email tidak ditemukan" atau "password
     * salah" — untuk mencegah enumerasi user oleh penyerang.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if ($user === null || ! Hash::check($credentials['password'], $user->password)) {
            return ApiResponse::error(
                'INVALID_CREDENTIALS',
                'Email atau password salah.',
                null,
                401
            );
        }

        // Nama token: pakai device_name kalau dikirim, kalau tidak default 'dashboard'.
        // Berguna untuk membedakan token saat operator login dari banyak perangkat.
        $tokenName = $credentials['device_name'] ?? 'dashboard';

        $plainTextToken = $user->createToken($tokenName)->plainTextToken;

        return ApiResponse::success([
            'user'       => new UserResource($user),
            'token'      => $plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Cabut token yang dipakai dalam request ini (logout dari perangkat ini).
     *
     * Token lain milik user yang sama (mis. dari perangkat lain) TIDAK
     * dicabut — itu pola standar logout per-sesi. Kalau ingin logout
     * dari semua perangkat sekaligus, panggil $user->tokens()->delete().
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success([
            'message' => 'Berhasil logout. Token telah dicabut.',
        ]);
    }

    /**
     * Info user yang sedang login (berdasarkan bearer token).
     */
    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()));
    }
}

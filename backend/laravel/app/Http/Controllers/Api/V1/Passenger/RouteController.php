<?php

namespace App\Http\Controllers\Api\V1\Passenger;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\RouteResource;
use App\Http\Resources\Api\V1\StopResource;
use App\Http\Resources\Api\V1\VehicleResource;
use App\Models\Route;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Endpoint koridor untuk aplikasi penumpang (titik integrasi TI-3).
 *
 * Semua endpoint di sini bersifat publik (tanpa auth) dan dibatasi
 * rate limiter 'public' (60 request/menit per IP).
 */
class RouteController extends Controller
{
    /**
     * GET /api/v1/routes
     * Daftar seluruh koridor aktif beserta jumlah halte dan armadanya.
     */
    public function index(): JsonResponse
    {
        $routes = Route::active()
            ->withCount(['stops', 'vehicles'])
            ->orderBy('code')
            ->get();

        return ApiResponse::success(RouteResource::collection($routes));
    }

    /**
     * GET /api/v1/routes/{route}
     * Detail satu koridor.
     */
    public function show(Route $route): JsonResponse
    {
        $route->loadCount(['stops', 'vehicles']);

        return ApiResponse::success(new RouteResource($route));
    }

    /**
     * GET /api/v1/routes/{route}/stops
     * Daftar halte pada koridor, terurut menurut sequence.
     */
    public function stops(Route $route): JsonResponse
    {
        // Relasi $route->stops() sudah orderBy('sequence') dari model.
        $stops = $route->stops;

        return ApiResponse::success(StopResource::collection($stops));
    }

    /**
     * GET /api/v1/routes/{route}/vehicles
     *
     * Daftar armada aktif pada koridor BERSAMA kepadatan terkini.
     * Ini adalah endpoint polling utama yang dipanggil aplikasi
     * penumpang setiap 5 detik untuk memperbarui posisi & warna
     * marker bus pada peta.
     *
     * Field meta.server_time membantu UI menampilkan "data per
     * waktu X" pada aplikasi.
     */
    public function vehicles(Route $route): JsonResponse
    {
        $vehicles = $route->vehicles()
            ->active()
            ->with('latestDensityLog')
            ->orderBy('plate_number')
            ->get();

        return ApiResponse::success(
            VehicleResource::collection($vehicles),
            ['server_time' => now()->toIso8601String()]
        );
    }
}

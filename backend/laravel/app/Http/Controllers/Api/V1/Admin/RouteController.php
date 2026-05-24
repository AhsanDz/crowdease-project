<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreRouteRequest;
use App\Http\Requests\Api\V1\Admin\UpdateRouteRequest;
use App\Http\Resources\Api\V1\RouteResource;
use App\Models\Route;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint manajemen Koridor untuk dasbor operator (Titik Integrasi TI-4).
 *
 * Berbeda dari endpoint passenger di Api\V1\Passenger\RouteController:
 *   - Termasuk koridor non-aktif (admin perlu lihat semua)
 *   - Mendukung pencarian dan pagination
 *   - CRUD lengkap dengan validasi
 *
 * Semua endpoint butuh auth:sanctum + throttle:operator (120/menit).
 */
class RouteController extends Controller
{
    /**
     * GET /api/v1/admin/routes
     *
     * Daftar koridor dengan pencarian opsional (?search=) dan pagination
     * (?page=, ?per_page=, default 20).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Route::query()->withCount(['stops', 'vehicles']);

        if ($request->filled('search')) {
            $term = $request->string('search');
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', "%{$term}%")
                  ->orWhere('name', 'like', "%{$term}%");
            });
        }

        $perPage = (int) $request->integer('per_page', 20);
        $routes  = $query->orderBy('code')->paginate($perPage);

        return ApiResponse::success(
            RouteResource::collection($routes),
            ['pagination' => $this->paginationMeta($routes)]
        );
    }

    /**
     * POST /api/v1/admin/routes
     *
     * Buat koridor baru. is_active default true bila tidak dikirim.
     * Mengembalikan 201 Created.
     */
    public function store(StoreRouteRequest $request): JsonResponse
    {
        $route = Route::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $route->loadCount(['stops', 'vehicles']);

        return ApiResponse::success(new RouteResource($route), null, 201);
    }

    /**
     * GET /api/v1/admin/routes/{route}
     *
     * Detail satu koridor lengkap dengan jumlah halte & armada.
     */
    public function show(Route $route): JsonResponse
    {
        $route->loadCount(['stops', 'vehicles']);

        return ApiResponse::success(new RouteResource($route));
    }

    /**
     * PUT/PATCH /api/v1/admin/routes/{route}
     *
     * Update sebagian/seluruh field koridor.
     */
    public function update(UpdateRouteRequest $request, Route $route): JsonResponse
    {
        $route->update($request->validated());
        $route->loadCount(['stops', 'vehicles']);

        return ApiResponse::success(new RouteResource($route));
    }

    /**
     * DELETE /api/v1/admin/routes/{route}
     *
     * Hard delete dengan safety check: tolak jika masih ada halte/armada terkait.
     * Operator disarankan set is_active=false untuk "menonaktifkan" koridor
     * tanpa kehilangan riwayat data sensor.
     */
    public function destroy(Route $route): JsonResponse
    {
        $stopsCount    = $route->stops()->count();
        $vehiclesCount = $route->vehicles()->count();

        if ($stopsCount > 0 || $vehiclesCount > 0) {
            return ApiResponse::error(
                'HAS_DEPENDENCIES',
                'Tidak bisa menghapus koridor: masih ada data terkait.',
                [
                    'stops_count'    => $stopsCount,
                    'vehicles_count' => $vehiclesCount,
                    'hint'           => 'Hapus halte/armada terkait dulu, atau set is_active=false untuk menonaktifkan koridor.',
                ],
                409
            );
        }

        $route->delete();

        return ApiResponse::success(['deleted_id' => $route->id]);
    }

    /**
     * Bentuk metadata pagination yang konsisten untuk amplop response.
     *
     * @param  \Illuminate\Pagination\LengthAwarePaginator<int, mixed>  $paginator
     * @return array<string, int>
     */
    private function paginationMeta($paginator): array
    {
        return [
            'page'      => $paginator->currentPage(),
            'per_page'  => $paginator->perPage(),
            'total'     => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}

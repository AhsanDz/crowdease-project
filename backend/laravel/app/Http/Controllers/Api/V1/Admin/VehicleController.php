<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreVehicleRequest;
use App\Http\Requests\Api\V1\Admin\UpdateVehicleRequest;
use App\Http\Resources\Api\V1\VehicleResource;
use App\Models\Vehicle;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint manajemen Armada untuk dasbor operator (Titik Integrasi TI-4).
 *
 * Mendukung filter berdasarkan koridor (?route_id=) dan status (?status=)
 * serta pencarian nomor plat (?search=).
 */
class VehicleController extends Controller
{
    /**
     * GET /api/v1/admin/vehicles
     *
     * Daftar armada dengan filter:
     *   - ?route_id=N    : armada di koridor tertentu
     *   - ?status=active : armada dengan status tertentu
     *   - ?search=       : cari berdasarkan nomor plat
     *   - ?per_page=20   : ukuran halaman
     */
    public function index(Request $request): JsonResponse
    {
        $query = Vehicle::query();

        if ($request->filled('route_id')) {
            $query->where('route_id', $request->integer('route_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $query->where('plate_number', 'like', '%' . $request->string('search') . '%');
        }

        $perPage  = (int) $request->integer('per_page', 20);
        $vehicles = $query->orderBy('plate_number')->paginate($perPage);

        return ApiResponse::success(
            VehicleResource::collection($vehicles),
            ['pagination' => $this->paginationMeta($vehicles)]
        );
    }

    /**
     * POST /api/v1/admin/vehicles
     *
     * Buat armada baru. status default 'active' bila tidak dikirim.
     */
    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $vehicle = Vehicle::create([
            ...$request->validated(),
            'status' => $request->string('status', 'active'),
        ]);

        return ApiResponse::success(new VehicleResource($vehicle), null, 201);
    }

    /**
     * GET /api/v1/admin/vehicles/{vehicle}
     */
    public function show(Vehicle $vehicle): JsonResponse
    {
        return ApiResponse::success(new VehicleResource($vehicle));
    }

    /**
     * PUT/PATCH /api/v1/admin/vehicles/{vehicle}
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): JsonResponse
    {
        $vehicle->update($request->validated());

        return ApiResponse::success(new VehicleResource($vehicle));
    }

    /**
     * DELETE /api/v1/admin/vehicles/{vehicle}
     *
     * Hard delete dengan safety check: tolak jika masih ada density_logs/forecasts.
     * Disarankan set status='inactive' untuk menonaktifkan armada tanpa
     * kehilangan riwayat data sensor.
     */
    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $logsCount      = $vehicle->densityLogs()->count();
        $forecastsCount = $vehicle->forecasts()->count();

        if ($logsCount > 0 || $forecastsCount > 0) {
            return ApiResponse::error(
                'HAS_DEPENDENCIES',
                'Tidak bisa menghapus armada: masih ada riwayat sensor.',
                [
                    'density_logs_count' => $logsCount,
                    'forecasts_count'    => $forecastsCount,
                    'hint'               => 'Set status="inactive" untuk menonaktifkan armada tanpa kehilangan riwayat.',
                ],
                409
            );
        }

        $vehicle->delete();

        return ApiResponse::success(['deleted_id' => $vehicle->id]);
    }

    /**
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

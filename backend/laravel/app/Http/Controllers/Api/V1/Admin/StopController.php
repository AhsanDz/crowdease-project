<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreStopRequest;
use App\Http\Requests\Api\V1\Admin\UpdateStopRequest;
use App\Http\Resources\Api\V1\StopResource;
use App\Models\Stop;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint manajemen Halte untuk dasbor operator (Titik Integrasi TI-4).
 *
 * Halte aman di-hard-delete (tidak ada FK dari tabel lain yang mereferensi).
 */
class StopController extends Controller
{
    /**
     * GET /api/v1/admin/stops
     *
     * Filter:
     *   - ?route_id=N : halte di koridor tertentu (urut sequence)
     *   - ?search=    : cari berdasarkan nama halte
     *   - ?per_page=  : ukuran halaman (default 20)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Stop::query();

        if ($request->filled('route_id')) {
            $query->where('route_id', $request->integer('route_id'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->string('search') . '%');
        }

        // Bila ada filter route_id, urutkan sesuai sequence;
        // jika tidak, urutkan berdasarkan koridor lalu sequence.
        if ($request->filled('route_id')) {
            $query->orderBy('sequence');
        } else {
            $query->orderBy('route_id')->orderBy('sequence');
        }

        $perPage = (int) $request->integer('per_page', 20);
        $stops   = $query->paginate($perPage);

        return ApiResponse::success(
            StopResource::collection($stops),
            ['pagination' => $this->paginationMeta($stops)]
        );
    }

    /**
     * POST /api/v1/admin/stops
     */
    public function store(StoreStopRequest $request): JsonResponse
    {
        $stop = Stop::create($request->validated());

        return ApiResponse::success(new StopResource($stop), null, 201);
    }

    /**
     * GET /api/v1/admin/stops/{stop}
     */
    public function show(Stop $stop): JsonResponse
    {
        return ApiResponse::success(new StopResource($stop));
    }

    /**
     * PUT/PATCH /api/v1/admin/stops/{stop}
     */
    public function update(UpdateStopRequest $request, Stop $stop): JsonResponse
    {
        $stop->update($request->validated());

        return ApiResponse::success(new StopResource($stop));
    }

    /**
     * DELETE /api/v1/admin/stops/{stop}
     *
     * Hard delete tanpa safety check — stops tidak direferensikan
     * tabel lain via FK (kecuali untuk display di peta).
     */
    public function destroy(Stop $stop): JsonResponse
    {
        $stop->delete();

        return ApiResponse::success(['deleted_id' => $stop->id]);
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

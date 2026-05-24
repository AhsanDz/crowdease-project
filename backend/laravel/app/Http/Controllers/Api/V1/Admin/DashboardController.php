<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\DensityLog;
use App\Models\Route;
use App\Models\Stop;
use App\Models\Vehicle;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard agregat untuk halaman utama dasbor operator.
 *
 * Mengumpulkan beberapa angka dalam satu request supaya frontend
 * tidak perlu N+1 fetch ke berbagai endpoint hanya untuk menampilkan
 * ringkasan KPI di dashboard.
 */
class DashboardController extends Controller
{
    /**
     * GET /api/v1/admin/dashboard/stats
     *
     * Response payload:
     * {
     *   totals: { routes, active_routes, vehicles, active_vehicles, stops, today_logs, active_webhooks },
     *   occupancy: { avg_percent, level_distribution: { low, med, high, no_data } },
     *   hourly_trend: [{ hour, avg_occupancy, count } × 24],
     *   per_corridor: [{ id, code, name, color, avg_occupancy, vehicles_count } × N]
     * }
     */
    public function stats(): JsonResponse
    {
        // ── Totals ──────────────────────────────────────────────────────
        $totalRoutes    = Route::count();
        $activeRoutes   = Route::active()->count();
        $totalVehicles  = Vehicle::count();
        $activeVehicles = Vehicle::active()->count();
        $totalStops     = Stop::count();
        $todayLogs      = DensityLog::whereDate('recorded_at', today())->count();

        // ── Snapshot kepadatan terkini ──────────────────────────────────
        // Ambil density log terbaru per vehicle (yang aktif) dalam satu query.
        $activeVehiclesWithLatest = Vehicle::active()
            ->with('latestDensityLog')
            ->get();

        $latestDensities = $activeVehiclesWithLatest
            ->pluck('latestDensityLog')
            ->filter();

        $avgPercent = $latestDensities->isEmpty()
            ? 0
            : (int) round($latestDensities->avg(fn ($d) => (float) $d->occupancy_ratio) * 100);

        // Distribusi level
        $levelDist = ['low' => 0, 'med' => 0, 'high' => 0, 'no_data' => 0];
        foreach ($activeVehiclesWithLatest as $v) {
            if (!$v->latestDensityLog) {
                $levelDist['no_data']++;
                continue;
            }
            $ratio = (float) $v->latestDensityLog->occupancy_ratio;
            if ($ratio < 0.6) {
                $levelDist['low']++;
            } elseif ($ratio < 0.85) {
                $levelDist['med']++;
            } else {
                $levelDist['high']++;
            }
        }

        // ── Tren per jam 24 jam terakhir ─────────────────────────────────
        // Group by HOUR(recorded_at) — kompatibel MySQL & MariaDB.
        $hourlyRaw = DB::table('density_logs')
            ->selectRaw('HOUR(recorded_at) as hour, AVG(occupancy_ratio) as avg_occupancy, COUNT(*) as count')
            ->where('recorded_at', '>=', now()->subDay())
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        // Isi semua 24 jam (jam yang tidak ada data jadi 0)
        $hourlyTrend = [];
        for ($h = 0; $h < 24; $h++) {
            $row = $hourlyRaw->get($h);
            $hourlyTrend[] = [
                'hour'          => $h,
                'avg_occupancy' => $row ? round((float) $row->avg_occupancy, 4) : 0,
                'count'         => $row ? (int) $row->count : 0,
            ];
        }

        // ── Per-koridor avg occupancy ───────────────────────────────────
        $perCorridor = Route::active()
            ->withCount(['vehicles' => fn ($q) => $q->where('status', 'active')])
            ->get()
            ->map(function ($route) {
                $vehicles = $route->vehicles()->active()->with('latestDensityLog')->get();
                $ratios = $vehicles
                    ->pluck('latestDensityLog')
                    ->filter()
                    ->map(fn ($d) => (float) $d->occupancy_ratio);

                return [
                    'id'             => $route->id,
                    'code'           => $route->code,
                    'name'           => $route->name,
                    'color'          => $route->color,
                    'avg_occupancy'  => $ratios->isEmpty() ? 0 : round($ratios->avg(), 4),
                    'vehicles_count' => $vehicles->count(),
                ];
            })
            ->values();

        return ApiResponse::success([
            'totals' => [
                'routes'          => $totalRoutes,
                'active_routes'   => $activeRoutes,
                'vehicles'        => $totalVehicles,
                'active_vehicles' => $activeVehicles,
                'stops'           => $totalStops,
                'today_logs'      => $todayLogs,
                // Webhook belum diimplementasi di Fase 3; akan diisi Fase 5
                'active_webhooks' => 0,
            ],
            'occupancy' => [
                'avg_percent'        => $avgPercent,
                'level_distribution' => $levelDist,
            ],
            'hourly_trend' => $hourlyTrend,
            'per_corridor' => $perCorridor,
        ], ['server_time' => now()->toIso8601String()]);
    }
}

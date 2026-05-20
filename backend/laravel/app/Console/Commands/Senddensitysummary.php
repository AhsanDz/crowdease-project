<?php

namespace App\Console\Commands;

use App\Models\Route;
use App\Models\Webhook;
use App\Services\WebhookDispatcher;
use Illuminate\Console\Command;

/**
 * SendDensitySummary
 *
 * Kirim ringkasan kepadatan semua koridor aktif ke Telegram
 * secara terjadwal setiap 5 menit via Laravel Scheduler.
 *
 * Daftarkan di app/Console/Kernel.php:
 *   $schedule->command('crowdease:density-summary')->everyFiveMinutes();
 *
 * Atau di bootstrap/app.php (Laravel 11):
 *   Schedule::command('crowdease:density-summary')->everyFiveMinutes();
 */
class SendDensitySummary extends Command
{
    protected $signature   = 'crowdease:density-summary';
    protected $description = 'Kirim ringkasan kepadatan bus ke Telegram tiap 5 menit';

    public function handle(WebhookDispatcher $dispatcher): int
    {
        $routes = Route::active()
            ->with(['activeVehicles.latestDensityLog'])
            ->get();

        if ($routes->isEmpty()) {
            $this->info('Tidak ada koridor aktif.');
            return self::SUCCESS;
        }

        // Hitung statistik tiap koridor
        $routeSummaries = $routes->map(function (Route $route) {
            $vehicles = $route->activeVehicles;

            $withData = $vehicles->filter(fn ($v) => $v->latestDensityLog !== null);
            $levels   = $withData->countBy(fn ($v) => $v->latestDensityLog->occupancy_level);

            return [
                'route_code'    => $route->code,
                'route_name'    => $route->name,
                'total'         => $vehicles->count(),
                'online'        => $withData->count(),
                'low'           => $levels->get('low', 0),
                'medium'        => $levels->get('medium', 0),
                'high'          => $levels->get('high', 0),
                'overcrowded'   => $levels->get('overcrowded', 0),
                'avg_ratio'     => $withData->isNotEmpty()
                    ? round($withData->avg(fn ($v) => $v->current_occupancy_ratio) * 100) . '%'
                    : '-',
            ];
        });

        $payload = [
            'generated_at'  => now('Asia/Jakarta')->toIso8601String(),
            'routes'        => $routeSummaries->toArray(),
        ];

        // Cari semua webhook aktif yang subscribe ke density.recorded
        // (summary pakai event yang sama agar tidak perlu event baru)
        $webhooks = Webhook::where('is_active', true)
            ->whereJsonContains('events', 'density.recorded')
            ->get();

        $sent = 0;
        foreach ($webhooks as $webhook) {
            // Hanya kirim ke Telegram untuk command ini
            if (! str_contains($webhook->url, 'api.telegram.org')) {
                continue;
            }

            $result = $dispatcher->dispatchNow(
                $webhook,
                'density.summary',  // custom event label untuk log
                $payload
            );

            if ($result['success']) {
                $sent++;
                $this->info("✓ Terkirim ke webhook #{$webhook->id} ({$webhook->name})");
            } else {
                $this->warn("✗ Gagal ke webhook #{$webhook->id}: {$result['response_body']}");
            }
        }

        $this->info("Summary terkirim ke {$sent} webhook Telegram.");
        return self::SUCCESS;
    }
}
<?php

namespace App\Services;

use App\Models\Webhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mengirim HTTP POST ke semua webhook aktif yang subscribe ke event tertentu.
 *
 * Payload disign dengan HMAC-SHA256 → header X-CrowdEase-Signature.
 * Penerima (Slack, Discord, webhook.site, dll.) bisa verifikasi tanda tangan ini.
 *
 * Ini adalah inti dari Titik Integrasi TI-2 (Backend → Layanan Eksternal).
 *
 * Flow:
 *   1. SensorReadingController menyimpan DensityLog
 *   2. DensityLogObserver mendeteksi level ≥ 85% (padat)
 *   3. Observer memanggil WebhookDispatchService::dispatch('density.alert', [...])
 *   4. Service iterasi semua Webhook aktif, kirim HTTP POST + signature
 *   5. External service terima, verifikasi, proses
 */
class WebhookDispatchService
{
    /**
     * Kirim event ke semua webhook aktif yang subscribe.
     * Fire-and-forget: gagal diam-diam (logged saja), tidak throw.
     */
    public static function dispatch(string $event, array $data): void
    {
        $webhooks = Webhook::active()->get();
        if ($webhooks->isEmpty()) return;

        foreach ($webhooks as $webhook) {
            if (!self::subscribes($webhook, $event)) continue;

            try {
                self::sendTo($webhook, $event, $data);
                $webhook->updateQuietly(['last_triggered_at' => now()]);
            } catch (\Throwable $e) {
                Log::warning("Webhook #{$webhook->id} delivery failed: {$e->getMessage()}", [
                    'webhook_id' => $webhook->id,
                    'event'      => $event,
                    'url'        => $webhook->url,
                ]);
            }
        }
    }

    /**
     * Kirim ke satu webhook spesifik — dipakai endpoint /test.
     * Melempar exception kalau gagal (supaya controller bisa return error).
     */
    public static function dispatchTo(Webhook $webhook, string $event, array $data): void
    {
        self::sendTo($webhook, $event, $data);
        $webhook->updateQuietly(['last_triggered_at' => now()]);
    }

    // ── Private helpers ────────────────────────────────────────────────

    private static function subscribes(Webhook $webhook, string $event): bool
    {
        $events = $webhook->events ?? ['density.alert'];
        return in_array('*', $events) || in_array($event, $events);
    }

    /**
     * Cek apakah URL adalah Discord webhook.
     */
    private static function isDiscord(string $url): bool
    {
        return str_contains($url, 'discord.com/api/webhooks/');
    }

    private static function sendTo(Webhook $webhook, string $event, array $data): void
    {
        $body = json_encode([
            'event'     => $event,
            'timestamp' => now()->toIso8601String(),
            'data'      => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        // Tanda tangan HMAC-SHA256 — format: "sha256=<hex>"
        $signature = 'sha256=' . hash_hmac('sha256', $body, $webhook->secret);

        // Discord membutuhkan format payload khusus (content/embeds)
        if (self::isDiscord($webhook->url)) {
            self::sendToDiscord($webhook, $event, $data, $signature);
            return;
        }

        // Generic webhook — kirim payload standar + signature header
        Http::withHeaders([
            'Content-Type'           => 'application/json',
            'X-CrowdEase-Event'      => $event,
            'X-CrowdEase-Signature'  => $signature,
            'User-Agent'             => 'CrowdEase-Webhook/1.0',
        ])
        ->timeout(5)
        ->post($webhook->url, json_decode($body, true));
    }

    /**
     * Format dan kirim payload khusus Discord (rich embed).
     *
     * Discord webhook hanya menerima payload dengan field `content` (teks biasa)
     * atau `embeds` (rich embed). Payload JSON generik akan ditolak/diabaikan.
     *
     * @see https://discord.com/developers/docs/resources/webhook#execute-webhook
     */
    private static function sendToDiscord(Webhook $webhook, string $event, array $data, string $signature): void
    {
        $payload = match ($event) {
            'webhook.test' => self::discordTestPayload($data),
            'density.critical' => self::discordDensityPayload($data, critical: true),
            'density.alert' => self::discordDensityPayload($data, critical: false),
            default => self::discordGenericPayload($event, $data),
        };

        Http::withHeaders([
            'X-CrowdEase-Signature' => $signature,
        ])
        ->timeout(5)
        ->post($webhook->url, $payload);
    }

    /**
     * Embed untuk test ping.
     */
    private static function discordTestPayload(array $data): array
    {
        return [
            'embeds' => [[
                'title'       => '🔔 CrowdEase — Test Webhook',
                'description' => $data['message'] ?? 'Test ping berhasil diterima.',
                'color'       => 0x3B82F6, // biru
                'fields'      => [
                    ['name' => 'Webhook',    'value' => $data['webhook'] ?? '-', 'inline' => true],
                    ['name' => 'Webhook ID', 'value' => (string) ($data['webhook_id'] ?? '-'), 'inline' => true],
                ],
                'footer'    => ['text' => 'CrowdEase Webhook System'],
                'timestamp' => now()->toIso8601String(),
            ]],
        ];
    }

    /**
     * Embed untuk density alert / critical.
     */
    private static function discordDensityPayload(array $data, bool $critical): array
    {
        $pct   = $data['occupancy_pct'] ?? '?';
        $level = $critical ? '🔴 KRITIS' : '🟠 PADAT';
        $color = $critical ? 0xDC2626 : 0xD97706; // merah / kuning
        $emoji = $critical ? '🚨' : '⚠️';

        $plate = $data['plate_number'] ?? '-';
        $route = trim(($data['route_code'] ?? '') . ' ' . ($data['route_name'] ?? ''));

        return [
            'content' => $critical
                ? "🚨 **KEPADATAN KRITIS** — Bus **{$plate}** mencapai **{$pct}%** kapasitas!"
                : null,
            'embeds' => [[
                'title'       => "{$emoji} Kepadatan {$level} — {$pct}%",
                'description' => "Bus **{$plate}** pada rute **{$route}** telah mencapai kepadatan **{$pct}%**.",
                'color'       => $color,
                'fields'      => [
                    ['name' => '🚌 Kendaraan',  'value' => $plate, 'inline' => true],
                    ['name' => '🛤️ Rute',       'value' => $route ?: '-', 'inline' => true],
                    ['name' => '📊 Kepadatan',   'value' => "{$pct}%", 'inline' => true],
                    ['name' => '👥 Penumpang',   'value' => ($data['passenger_count'] ?? '?') . '/' . ($data['capacity'] ?? '?'), 'inline' => true],
                    ['name' => '📈 Level',       'value' => $data['level'] ?? ($critical ? 'kritis' : 'padat'), 'inline' => true],
                    ['name' => '🕐 Waktu',       'value' => $data['recorded_at'] ?? now()->toIso8601String(), 'inline' => true],
                ],
                'footer'    => ['text' => 'CrowdEase Density Alert System'],
                'timestamp' => now()->toIso8601String(),
            ]],
        ];
    }

    /**
     * Fallback embed untuk event yang tidak dikenal.
     */
    private static function discordGenericPayload(string $event, array $data): array
    {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = [
                'name'   => str_replace('_', ' ', ucfirst($key)),
                'value'  => is_array($value) ? json_encode($value) : (string) $value,
                'inline' => true,
            ];
        }

        return [
            'embeds' => [[
                'title'       => "📡 CrowdEase — {$event}",
                'color'       => 0x6B7280,
                'fields'      => array_slice($fields, 0, 25), // Discord max 25 fields
                'footer'      => ['text' => 'CrowdEase Webhook System'],
                'timestamp'   => now()->toIso8601String(),
            ]],
        ];
    }
}


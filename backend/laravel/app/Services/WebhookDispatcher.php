<?php

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Jobs\DeliverWebhook;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * WebhookDispatcher
 *
 * Mengurus pengiriman event outbound ke semua webhook yang terdaftar.
 * Mendukung target: Telegram Bot API, Discord Webhook, Generic JSON POST.
 */
class WebhookDispatcher
{
    public const VALID_EVENTS = [
        'density.recorded',
        'density.high_threshold_crossed',
        'density.low_threshold_recovered',
        'vehicle.created',
        'vehicle.updated',
    ];

    /**
     * Dispatch event ke semua webhook aktif yang subscribe ke event ini.
     */
    public function dispatch(string $eventName, array $data): void
    {
        $webhooks = Webhook::where('is_active', true)
            ->whereJsonContains('events', $eventName)
            ->get();

        foreach ($webhooks as $webhook) {
            $delivery = $this->createDelivery($webhook, $eventName, $data);
            DeliverWebhook::dispatch($delivery->id);
        }
    }

    /**
     * Kirim synchronous — untuk endpoint test ping dan scheduled summary.
     */
    public function dispatchNow(Webhook $webhook, string $eventName, array $data): array
    {
        $delivery = $this->createDelivery($webhook, $eventName, $data);
        return $this->send($delivery);
    }

    /**
     * Kirim ke URL webhook. Dipanggil dari DeliverWebhook Job.
     */
    public function send(WebhookDelivery $delivery): array
    {
        $delivery->loadMissing('webhook');
        $webhook = $delivery->webhook;

        if (! $webhook instanceof Webhook) {
            return [
                'success'       => false,
                'status_code'   => 0,
                'response_body' => 'Webhook record not found.',
            ];
        }

        $payload     = $delivery->payload;
        $payloadJson = json_encode($payload);
        $timestamp   = time();
        $deliveryId  = $delivery->delivery_id;

        $signature = $this->generateSignature($payloadJson, $webhook->secret, $timestamp);

        $headers = [
            'Content-Type'             => 'application/json',
            'User-Agent'               => 'CrowdEase-Webhook/1.0',
            'X-CrowdEase-Event'        => $payload['event'],
            'X-CrowdEase-Delivery-Id'  => $deliveryId,
            'X-CrowdEase-Signature'    => $signature,
            'X-CrowdEase-Timestamp'    => (string) $timestamp,
        ];

        try {
            $body = $this->formatPayload($webhook->url, $payload, $payloadJson);

            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->post($webhook->url, json_decode($body, true));

            $statusCode   = $response->status();
            $responseBody = substr($response->body(), 0, 1000);
            $success      = $response->successful();

            $delivery->update([
                'status'        => $success ? 'delivered' : 'failed',
                'response_code' => $statusCode,
                'response_body' => $responseBody,
                'delivered_at'  => $success ? Carbon::now() : null,
                'attempt'       => $delivery->attempt + 1,
            ]);

            if ($success) {
                $webhook->recordDelivery(true);
            } else {
                $webhook->recordDelivery(false);
            }

            return [
                'success'       => $success,
                'status_code'   => $statusCode,
                'response_body' => $responseBody,
            ];
        } catch (\Throwable $e) {
            $delivery->update([
                'status'        => 'failed',
                'response_code' => null,
                'response_body' => $e->getMessage(),
                'attempt'       => $delivery->attempt + 1,
            ]);

            return [
                'success'       => false,
                'status_code'   => 0,
                'response_body' => $e->getMessage(),
            ];
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Payload formatters
    // ──────────────────────────────────────────────────────────────────────

    private function formatPayload(string $url, array $payload, string $defaultJson): string
    {
        if (str_contains($url, 'api.telegram.org')) {
            return json_encode($this->formatForTelegram($payload));
        }

        if (str_contains($url, 'discord.com/api/webhooks')) {
            return json_encode($this->formatForDiscord($payload));
        }

        return $defaultJson;
    }

    /**
     * Format pesan Telegram berdasarkan jenis event.
     *
     * density.recorded             → ringkasan rutin
     * density.high_threshold_crossed → alert merah
     * density.low_threshold_recovered → notif hijau
     */
    private function formatForTelegram(array $payload): array
    {
        $event   = $payload['event'] ?? 'unknown';
        $density = $payload['data']['density'] ?? [];
        $vehicle = $payload['data']['vehicle'] ?? [];
        $forecasts = $payload['data']['forecasts'] ?? [];

        $level    = $density['occupancy_level'] ?? 'unknown';
        $count    = $density['passenger_count'] ?? 0;
        $capacity = $density['capacity'] ?? 0;
        $ratio    = isset($density['occupancy_ratio'])
            ? round($density['occupancy_ratio'] * 100) . '%'
            : '-';

        $plate    = $vehicle['plate_number'] ?? '-';
        $route    = $vehicle['route_code'] ?? '-';
        $routeName = $vehicle['route_name'] ?? '-';
        $time     = $density['recorded_at'] ?? '-';

        // Summary terjadwal punya struktur payload berbeda
        if ($event === 'density.summary') {
            return $this->formatSummaryForTelegram($payload);
        }

        // Emoji & header berdasarkan event
        [$headerEmoji, $headerText] = match ($event) {
            'density.high_threshold_crossed'  => ['🚨', '*ALERT — Kepadatan Tinggi*'],
            'density.low_threshold_recovered' => ['✅', '*Kepadatan Kembali Normal*'],
            default                           => ['📊', '*Update Kepadatan Bus*'],
        };

        // Emoji level
        $levelEmoji = match ($level) {
            'low'         => '🟢',
            'medium'      => '🟡',
            'high'        => '🔴',
            'overcrowded' => '⛔',
            default       => '⚪',
        };

        $lines = [
            "{$headerEmoji} {$headerText}",
            '',
            "🚌 *Bus:* `{$plate}`",
            "🛣 *Koridor:* {$route} — {$routeName}",
            "👥 *Penumpang:* {$count} / {$capacity} ({$ratio})",
            "📶 *Status:* {$levelEmoji} " . strtoupper($level),
            "🕐 *Waktu:* {$time}",
        ];

        // Tambahkan forecast jika ada
        if (! empty($forecasts)) {
            $lines[] = '';
            $lines[] = '🔮 *Prediksi ke depan:*';
            foreach ($forecasts as $f) {
                $fLevel = $f['predicted_occupancy_level'] ?? '-';
                $fEmoji = match ($fLevel) {
                    'low'         => '🟢',
                    'medium'      => '🟡',
                    'high'        => '🔴',
                    'overcrowded' => '⛔',
                    default       => '⚪',
                };
                $lines[] = "  {$fEmoji} +{$f['minutes_ahead']} menit → " . strtoupper($fLevel);
            }
        }

        $lines[] = '';
        $lines[] = '—';
        $lines[] = '_CrowdEase · TIS TI-D Kelompok 7_';

        return [
            'text'       => implode("\n", $lines),
            'parse_mode' => 'Markdown',
        ];
    }

    /**
     * Format summary terjadwal untuk Telegram.
     * Dipanggil dari SendDensitySummary command tiap 5 menit.
     */
    private function formatSummaryForTelegram(array $payload): array
    {
        $routes      = $payload['data']['routes'] ?? [];
        $generatedAt = $payload['data']['generated_at'] ?? now()->toIso8601String();

        $lines = [
            '🕐 *Ringkasan Kepadatan Bus*',
            '_Update tiap 5 menit_',
            '',
        ];

        foreach ($routes as $r) {
            $alerts = '';
            if ($r['overcrowded'] > 0) $alerts .= " ⛔{$r['overcrowded']}";
            if ($r['high'] > 0)        $alerts .= " 🔴{$r['high']}";
            if ($r['medium'] > 0)      $alerts .= " 🟡{$r['medium']}";
            if ($r['low'] > 0)         $alerts .= " 🟢{$r['low']}";

            $lines[] = "*{$r['route_code']}* — {$r['route_name']}";
            $lines[] = "  Armada: {$r['online']}/{$r['total']} online · Avg: {$r['avg_ratio']}";
            $lines[] = "  {$alerts}";
            $lines[] = '';
        }

        $lines[] = "⏱ {$generatedAt}";
        $lines[] = '_CrowdEase · TIS TI-D Kelompok 7_';

        return [
            'text'       => implode("\n", $lines),
            'parse_mode' => 'Markdown',
        ];
    }

    /**
     * Format Discord embed berdasarkan jenis event.
     */
    private function formatForDiscord(array $payload): array
    {
        $event   = $payload['event'] ?? 'unknown';
        $density = $payload['data']['density'] ?? [];
        $vehicle = $payload['data']['vehicle'] ?? [];
        $forecasts = $payload['data']['forecasts'] ?? [];

        $level = $density['occupancy_level'] ?? 'unknown';
        $color = match ($level) {
            'high'        => 0xFF4444,
            'medium'      => 0xFFAA00,
            'low'         => 0x22CC66,
            'overcrowded' => 0x880000,
            default       => 0xAAAAAA,
        };

        $title = match ($event) {
            'density.high_threshold_crossed'  => '🚨 Alert — Kepadatan Tinggi',
            'density.low_threshold_recovered' => '✅ Kepadatan Kembali Normal',
            default                           => '📊 Update Kepadatan Bus',
        };

        $ratio = isset($density['occupancy_ratio'])
            ? round($density['occupancy_ratio'] * 100) . '%'
            : '-';

        $fields = [
            ['name' => 'Bus',        'value' => "`{$vehicle['plate_number']}`", 'inline' => true],
            ['name' => 'Koridor',    'value' => $vehicle['route_code'] . ' — ' . $vehicle['route_name'], 'inline' => true],
            ['name' => 'Penumpang',  'value' => "{$density['passenger_count']} / {$density['capacity']} ({$ratio})", 'inline' => true],
            ['name' => 'Status',     'value' => strtoupper($level), 'inline' => true],
            ['name' => 'Waktu',      'value' => $density['recorded_at'] ?? '-', 'inline' => false],
        ];

        if (! empty($forecasts)) {
            $forecastText = implode(' │ ', array_map(
                fn ($f) => "+{$f['minutes_ahead']}m: " . strtoupper($f['predicted_occupancy_level']),
                $forecasts
            ));
            $fields[] = ['name' => 'Prediksi', 'value' => $forecastText, 'inline' => false];
        }

        return [
            'username' => 'CrowdEase Bot',
            'embeds'   => [[
                'title'     => $title,
                'color'     => $color,
                'fields'    => $fields,
                'footer'    => ['text' => 'CrowdEase · TIS TI-D Kelompok 7'],
                'timestamp' => Carbon::now()->toIso8601String(),
            ]],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────

    private function createDelivery(Webhook $webhook, string $eventName, array $data): WebhookDelivery
    {
        $payload = [
            'event'        => $eventName,
            'delivered_at' => Carbon::now('Asia/Jakarta')->toIso8601String(),
            'data'         => $data,
        ];

        return WebhookDelivery::create([
            'webhook_id'  => $webhook->id,
            'delivery_id' => Str::uuid()->toString(),
            'event'       => $eventName,
            'payload'     => $payload,
            'status'      => 'pending',
            'attempt'     => 0,
        ]);
    }

    private function generateSignature(string $payloadJson, string $secret, int $timestamp): string
    {
        $signedPayload = $timestamp . '.' . $payloadJson;
        return 'sha256=' . hash_hmac('sha256', $signedPayload, $secret);
    }
}
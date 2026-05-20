<?php

namespace Database\Seeders;

use App\Models\Webhook;
use Illuminate\Database\Seeder;

/**
 * TelegramWebhookSeeder
 *
 * Daftarkan webhook Telegram ke DB.
 * Jalankan: php artisan db:seed --class=TelegramWebhookSeeder
 *
 * Sebelum seeder ini, isi dua env variable berikut di .env:
 *
 *   TELEGRAM_BOT_TOKEN=123456789:ABCdef...
 *   TELEGRAM_CHAT_ID=-1001234567890
 *
 * Cara dapat CHAT_ID:
 *   1. Kirim pesan ke bot kamu
 *   2. Buka https://api.telegram.org/bot<TOKEN>/getUpdates
 *   3. Cari field "chat": {"id": ...}
 *   4. Jika pakai group/channel, id-nya negatif (misal -1001234567890)
 */
class TelegramWebhookSeeder extends Seeder
{
    public function run(): void
    {
        $token  = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');

        if (! $token || ! $chatId) {
            $this->command->warn(
                'TELEGRAM_BOT_TOKEN atau TELEGRAM_CHAT_ID belum diisi di .env. Seeder dilewati.'
            );
            return;
        }

        // URL format Telegram Bot API sendMessage dengan chat_id sebagai query param
        $url = "https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chatId}";

        // Webhook untuk alert real-time (high threshold & recovery)
        Webhook::updateOrCreate(
            ['name' => 'Telegram Alert'],
            [
                'url'       => $url,
                'events'    => [
                    'density.high_threshold_crossed',
                    'density.low_threshold_recovered',
                ],
                'is_active' => true,
            ]
        );

        // Webhook terpisah untuk summary berkala tiap 5 menit
        Webhook::updateOrCreate(
            ['name' => 'Telegram Summary'],
            [
                'url'       => $url,
                'events'    => [
                    'density.recorded',
                ],
                'is_active' => true,
            ]
        );

        $this->command->info('✓ Webhook Telegram berhasil didaftarkan.');
        $this->command->info("  URL: https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chatId}");
        $this->command->warn('  Simpan secret key dari tabel webhooks untuk verifikasi HMAC.');
    }
}
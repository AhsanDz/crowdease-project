<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder API key untuk IoT Simulator.
 *
 * Membuat satu API key dengan nilai plaintext TETAP supaya mudah
 * dipakai berulang kali tanpa harus generate ulang lewat tinker.
 *
 * PERINGATAN: key tetap di bawah ini HANYA untuk development lokal.
 * Untuk lingkungan nyata, key harus di-generate acak per perangkat
 * lewat dasbor operator dan tidak pernah ditulis di kode.
 */
class ApiKeySeeder extends Seeder
{
    /**
     * API key plaintext untuk dev lokal. Yang disimpan di database
     * hanyalah hash SHA-256-nya (kolom key_hash).
     */
    private const DEV_PLAIN_KEY = 'ce_iot_devkey_3f8a1c9e7b2d4056a8c1e9f7b3d5028a';

    public function run(): void
    {
        $operator = User::first();

        if ($operator === null) {
            $this->command->warn('Tidak ada user. Jalankan UserSeeder lebih dulu. ApiKeySeeder dilewati.');
            return;
        }

        ApiKey::firstOrCreate(
            ['key_hash' => hash('sha256', self::DEV_PLAIN_KEY)],
            [
                'user_id' => $operator->id,
                'name'    => 'IoT Simulator (Dev Lokal)',
            ]
        );

        $this->command->newLine();
        $this->command->info('==================================================================');
        $this->command->info(' API KEY untuk IoT Simulator (DEV LOKAL):');
        $this->command->info('');
        $this->command->info('   ' . self::DEV_PLAIN_KEY);
        $this->command->info('');
        $this->command->info(' Salin nilai di atas ke file iot-simulator/.env :');
        $this->command->info('   CROWDEASE_API_KEY=' . self::DEV_PLAIN_KEY);
        $this->command->info('==================================================================');
        $this->command->newLine();
    }
}

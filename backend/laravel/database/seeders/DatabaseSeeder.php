<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder utama CrowdEase.
 *
 * Memanggil seluruh seeder dalam urutan yang menghormati foreign key:
 *   1. UserSeeder    - akun operator
 *   2. RouteSeeder   - koridor
 *   3. StopSeeder    - halte (FK ke routes)
 *   4. VehicleSeeder - armada (FK ke routes)
 *   5. ApiKeySeeder  - API key IoT (FK ke users)
 *
 * Catatan: tabel density_logs dan forecasts sengaja TIDAK di-seed —
 * keduanya berisi data runtime yang dihasilkan oleh IoT Simulator
 * saat sistem berjalan.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            RouteSeeder::class,
            StopSeeder::class,
            VehicleSeeder::class,
            ApiKeySeeder::class,
        ]);

        $this->command->info('Seeding selesai. Data master siap dipakai.');
    }
}

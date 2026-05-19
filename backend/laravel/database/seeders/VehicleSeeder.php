<?php

namespace Database\Seeders;

use App\Models\Route;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

/**
 * Seeder armada (vehicle) bus.
 *
 * PENTING: tujuh armada di bawah disusun agar ID-nya menjadi 1-7,
 * SELARAS dengan konstanta DEFAULT_VEHICLES di iot-simulator/simulator.py.
 * Jangan mengubah urutan atau jumlahnya tanpa juga menyesuaikan simulator.
 *
 * route_id dicari berdasarkan kode koridor, bukan di-hardcode, agar
 * tetap benar walau ID koridor berbeda.
 */
class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $vehicles = [
            ['plate' => 'B 7001 TRN', 'route' => 'K1',  'capacity' => 60],
            ['plate' => 'B 7002 TRN', 'route' => 'K1',  'capacity' => 60],
            ['plate' => 'B 7011 TRN', 'route' => 'K2',  'capacity' => 60],
            ['plate' => 'B 7012 TRN', 'route' => 'K2',  'capacity' => 80],
            ['plate' => 'B 7021 TRN', 'route' => 'K3',  'capacity' => 60],
            ['plate' => 'B 7031 TRN', 'route' => 'K9',  'capacity' => 60],
            ['plate' => 'B 7041 TRN', 'route' => 'K13', 'capacity' => 80],
        ];

        $count = 0;

        foreach ($vehicles as $vehicle) {
            $route = Route::where('code', $vehicle['route'])->first();

            if ($route === null) {
                $this->command->warn(
                    "Koridor {$vehicle['route']} tidak ditemukan, " .
                    "armada {$vehicle['plate']} dilewati."
                );
                continue;
            }

            Vehicle::firstOrCreate(
                ['plate_number' => $vehicle['plate']],
                [
                    'route_id' => $route->id,
                    'capacity' => $vehicle['capacity'],
                    'status'   => 'active',
                ]
            );
            $count++;
        }

        $this->command->info("Armada siap: {$count} kendaraan (ID 1-{$count}).");
    }
}

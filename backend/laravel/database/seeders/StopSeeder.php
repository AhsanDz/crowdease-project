<?php

namespace Database\Seeders;

use App\Models\Route;
use App\Models\Stop;
use Illuminate\Database\Seeder;

/**
 * Seeder halte (stop) untuk tiap koridor.
 *
 * Empat halte per koridor dengan koordinat di sekitar Jakarta.
 * Dipakai aplikasi penumpang untuk menampilkan marker di peta.
 * Koordinat bersifat representatif untuk keperluan demo.
 */
class StopSeeder extends Seeder
{
    public function run(): void
    {
        $stopsByRoute = [
            'K1' => [
                ['name' => 'Halte Blok M',       'lat' => -6.244000, 'lng' => 106.799000],
                ['name' => 'Halte Masjid Agung', 'lat' => -6.238000, 'lng' => 106.798000],
                ['name' => 'Halte Bundaran HI',  'lat' => -6.195000, 'lng' => 106.823000],
                ['name' => 'Halte Kota',         'lat' => -6.137000, 'lng' => 106.813000],
            ],
            'K2' => [
                ['name' => 'Halte Pulogadung',   'lat' => -6.188000, 'lng' => 106.900000],
                ['name' => 'Halte Bermis',       'lat' => -6.190000, 'lng' => 106.880000],
                ['name' => 'Halte Senen',        'lat' => -6.176000, 'lng' => 106.842000],
                ['name' => 'Halte Monas',        'lat' => -6.175000, 'lng' => 106.827000],
            ],
            'K3' => [
                ['name' => 'Halte Kalideres',       'lat' => -6.156000, 'lng' => 106.703000],
                ['name' => 'Halte Jembatan Gantung','lat' => -6.162000, 'lng' => 106.740000],
                ['name' => 'Halte Grogol',          'lat' => -6.168000, 'lng' => 106.790000],
                ['name' => 'Halte Pasar Baru',      'lat' => -6.164000, 'lng' => 106.833000],
            ],
            'K9' => [
                ['name' => 'Halte Pinang Ranti', 'lat' => -6.292000, 'lng' => 106.887000],
                ['name' => 'Halte Cawang',       'lat' => -6.242000, 'lng' => 106.866000],
                ['name' => 'Halte Semanggi',     'lat' => -6.220000, 'lng' => 106.814000],
                ['name' => 'Halte Pluit',        'lat' => -6.128000, 'lng' => 106.791000],
            ],
            'K13' => [
                ['name' => 'Halte Puri Beta',    'lat' => -6.233000, 'lng' => 106.732000],
                ['name' => 'Halte Adam Malik',   'lat' => -6.228000, 'lng' => 106.760000],
                ['name' => 'Halte Mayestik',     'lat' => -6.244000, 'lng' => 106.796000],
                ['name' => 'Halte Tendean',      'lat' => -6.240000, 'lng' => 106.826000],
            ],
        ];

        $total = 0;

        foreach ($stopsByRoute as $routeCode => $stops) {
            $route = Route::where('code', $routeCode)->first();

            if ($route === null) {
                $this->command->warn("Koridor {$routeCode} tidak ditemukan, halte dilewati.");
                continue;
            }

            foreach ($stops as $index => $stop) {
                Stop::firstOrCreate(
                    [
                        'route_id' => $route->id,
                        'sequence' => $index + 1,
                    ],
                    [
                        'name'      => $stop['name'],
                        'latitude'  => $stop['lat'],
                        'longitude' => $stop['lng'],
                    ]
                );
                $total++;
            }
        }

        $this->command->info("Halte siap: {$total} halte.");
    }
}

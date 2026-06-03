<?php

namespace Database\Seeders;

use App\Models\Route;
use Illuminate\Database\Seeder;

/**
 * Seeder koridor (route) TransJakarta.
 *
 * Lima koridor — kode-kodenya (K1, K2, K3, K9, K13) harus selaras
 * dengan koridor yang dirujuk oleh VehicleSeeder.
 */
class RouteSeeder extends Seeder
{
    public function run(): void
    {
        $routes = [
            ['code' => 'K1',  'name' => 'Blok M - Kota',           'color' => '#E2231A'],
            ['code' => 'K2',  'name' => 'Pulogadung - Monas',      'color' => '#1B7A3D'],
            ['code' => 'K3',  'name' => 'Kalideres - Pasar Baru',  'color' => '#F5A623'],
            ['code' => 'K9',  'name' => 'Pinang Ranti - Pluit',    'color' => '#0072BC'],
            ['code' => 'K13', 'name' => 'Ciledug - Tendean',       'color' => '#7B2D8E'],
        ];

        foreach ($routes as $route) {
            Route::firstOrCreate(
                ['code' => $route['code']],
                [
                    'name'      => $route['name'],
                    'color'     => $route['color'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Koridor siap: ' . count($routes) . ' koridor.');
    }
}

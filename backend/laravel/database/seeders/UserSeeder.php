<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder akun operator CrowdEase.
 *
 * Membuat satu akun operator default untuk login ke dasbor.
 * Password di-hash otomatis oleh cast 'hashed' pada model User.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'operator@crowdease.test'],
            [
                'name'     => 'Operator CrowdEase',
                'password' => 'secret123', // otomatis di-hash oleh cast 'hashed'
            ]
        );

        $this->command->info('Akun operator siap: operator@crowdease.test / secret123');
    }
}

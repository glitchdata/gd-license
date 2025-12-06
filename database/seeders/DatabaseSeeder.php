<?php

namespace Database\Seeders;

use App\Models\License;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => bcrypt('password'),
        ]);

        License::query()->insert([
            [
                'name' => 'Analytics Pro',
                'product_code' => 'LIC-ANL-01',
                'seats_total' => 25,
                'seats_used' => 18,
                'expires_at' => now()->addMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Security Suite',
                'product_code' => 'LIC-SEC-99',
                'seats_total' => 50,
                'seats_used' => 42,
                'expires_at' => now()->addYear(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Data Lake Access',
                'product_code' => 'LIC-DLK-12',
                'seats_total' => 10,
                'seats_used' => 4,
                'expires_at' => now()->addMonths(3),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

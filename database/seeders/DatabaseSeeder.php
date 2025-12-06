<?php

namespace Database\Seeders;

use App\Models\License;
use App\Models\Product;
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
        $admin = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        Product::insertOrIgnore([
            [
                'name' => 'Analytics Pro',
                'product_code' => 'LIC-ANL-01',
                'vendor' => 'Glitchdata',
                'category' => 'Analytics',
                'description' => 'Dashboards and forecasting toolkit for business analysts.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Security Suite',
                'product_code' => 'LIC-SEC-99',
                'vendor' => 'Glitchdata',
                'category' => 'Security',
                'description' => 'Threat monitoring agents and policy management tools.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Data Lake Access',
                'product_code' => 'LIC-DLK-12',
                'vendor' => 'Glitchdata',
                'category' => 'Data Platform',
                'description' => 'Self-service access tier into curated data lake zones.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $productMap = Product::whereIn('product_code', ['LIC-ANL-01', 'LIC-SEC-99', 'LIC-DLK-12'])
            ->get()
            ->keyBy('product_code');

        License::query()->insert([
            [
                'product_id' => $productMap['LIC-ANL-01']->id,
                'user_id' => $admin->id,
                'seats_total' => 25,
                'seats_used' => 18,
                'expires_at' => now()->addMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $productMap['LIC-SEC-99']->id,
                'user_id' => null,
                'seats_total' => 50,
                'seats_used' => 42,
                'expires_at' => now()->addYear(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $productMap['LIC-DLK-12']->id,
                'user_id' => null,
                'seats_total' => 10,
                'seats_used' => 4,
                'expires_at' => now()->addMonths(3),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

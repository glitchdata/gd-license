<?php

namespace Database\Factories;

use App\Models\License;
use Illuminate\Database\Eloquent\Factories\Factory;

class LicenseFactory extends Factory
{
    protected $model = License::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company.' Suite',
            'product_code' => strtoupper($this->faker->lexify('LIC-????')),
            'seats_total' => $this->faker->numberBetween(5, 200),
            'seats_used' => $this->faker->numberBetween(0, 150),
            'expires_at' => $this->faker->dateTimeBetween('+3 months', '+2 years'),
        ];
    }
}

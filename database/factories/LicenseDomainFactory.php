<?php

namespace Database\Factories;

use App\Models\License;
use App\Models\LicenseDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

class LicenseDomainFactory extends Factory
{
    protected $model = LicenseDomain::class;

    public function definition(): array
    {
        return [
            'license_id' => License::factory(),
            'domain' => $this->faker->domainName,
        ];
    }
}

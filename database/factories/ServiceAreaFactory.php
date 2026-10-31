<?php

namespace Database\Factories;

use App\Models\ServiceArea;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ServiceArea> */
class ServiceAreaFactory extends Factory
{
    protected $model = ServiceArea::class;

    public function definition(): array
    {
        return ['city' => 'Medan', 'district' => 'Medan ' . fake()->unique()->word(), 'is_active' => true];
    }
}

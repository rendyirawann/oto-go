<?php

namespace Database\Factories;

use App\Models\VehicleBrand;
use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VehicleBrand> */
class VehicleBrandFactory extends Factory
{
    protected $model = VehicleBrand::class;

    public function definition(): array
    {
        return [
            'vehicle_type_id' => VehicleType::factory(),
            'name' => fake()->unique()->company(),
        ];
    }
}

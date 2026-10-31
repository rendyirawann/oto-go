<?php

namespace Database\Factories;

use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VehicleModel> */
class VehicleModelFactory extends Factory
{
    protected $model = VehicleModel::class;

    public function definition(): array
    {
        return [
            'vehicle_brand_id' => VehicleBrand::factory(),
            'name' => fake()->unique()->word(),
            'default_category_id' => null,
        ];
    }
}

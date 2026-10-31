<?php

namespace Database\Factories;

use App\Models\VehicleCategory;
use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VehicleCategory> */
class VehicleCategoryFactory extends Factory
{
    protected $model = VehicleCategory::class;

    public function definition(): array
    {
        return [
            'vehicle_type_id' => VehicleType::factory(),
            'code' => 'cat_' . fake()->unique()->lexify('??????'),
            'name' => fake()->words(2, true),
            'sort' => fake()->numberBetween(1, 20),
        ];
    }
}

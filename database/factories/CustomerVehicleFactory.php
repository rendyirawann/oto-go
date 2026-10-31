<?php

namespace Database\Factories;

use App\Models\CustomerVehicle;
use App\Models\VehicleCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CustomerVehicle> */
class CustomerVehicleFactory extends Factory
{
    protected $model = CustomerVehicle::class;

    public function definition(): array
    {
        return [
            'vehicle_category_id' => VehicleCategory::factory(),
            'vehicle_model_id' => null,
            'plate_number' => 'BK ' . fake()->unique()->numberBetween(1000, 9999) . ' ' . strtoupper(fake()->lexify('??')),
            'year' => fake()->numberBetween(2010, 2026),
            'color' => fake()->randomElement(['Hitam', 'Putih', 'Merah', 'Abu-abu']),
            'nickname' => null,
        ];
    }
}

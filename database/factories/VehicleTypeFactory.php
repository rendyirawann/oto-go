<?php

namespace Database\Factories;

use App\Models\VehicleType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VehicleType> */
class VehicleTypeFactory extends Factory
{
    protected $model = VehicleType::class;

    public function definition(): array
    {
        $code = fake()->unique()->randomElement(['motor', 'mobil']);

        return ['code' => $code, 'name' => ucfirst($code), 'sort' => $code === 'motor' ? 1 : 2];
    }
}

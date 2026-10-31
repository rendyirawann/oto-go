<?php

namespace Database\Factories;

use App\Models\PriceTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PriceTemplate> */
class PriceTemplateFactory extends Factory
{
    protected $model = PriceTemplate::class;

    public function definition(): array
    {
        return ['name' => 'Template ' . fake()->unique()->word(), 'city' => 'Medan', 'disclaimer' => 'Estimasi.', 'is_active' => true];
    }
}

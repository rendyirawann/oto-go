<?php

namespace Database\Factories;

use App\Models\PostCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PostCategory> */
class PostCategoryFactory extends Factory
{
    protected $model = PostCategory::class;

    public function definition(): array
    {
        $name = Str::title($this->faker->unique()->words(2, true));

        return ['name' => $name, 'slug' => Str::slug($name)];
    }
}

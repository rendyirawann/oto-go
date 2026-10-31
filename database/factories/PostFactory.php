<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Post> */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = rtrim($this->faker->unique()->sentence(6), '.');
        $content = '<h2>Pendahuluan</h2><p>' . implode('</p><p>', $this->faker->paragraphs(4)) . '</p>'
            . '<h2>Langkah praktis</h2><ul><li>Periksa rutin.</li><li>Catat jadwal servis.</li></ul>';

        return [
            'post_category_id' => PostCategory::factory(),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::lower(Str::random(4)),
            'excerpt' => $this->faker->sentence(18),
            'content' => $content,
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDays($this->faker->numberBetween(1, 30)),
            'reading_minutes' => Post::readingMinutes($content),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => Post::STATUS_DRAFT, 'published_at' => null]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => ['status' => Post::STATUS_PUBLISHED, 'published_at' => now()->addDays(3)]);
    }
}

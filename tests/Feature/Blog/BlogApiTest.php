<?php

namespace Tests\Feature\Blog;

use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_posts_index_returns_paginated_summaries(): void
    {
        Post::factory()->count(3)->create();
        Post::factory()->draft()->create();

        $this->getJson('/api/v1/posts?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonStructure([
                'data' => [['id', 'slug', 'title', 'excerpt', 'cover_url', 'category' => ['name', 'slug'], 'reading_minutes', 'published_at']],
                'links',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_posts_index_supports_search_and_category(): void
    {
        $motor = PostCategory::factory()->create(['slug' => 'perawatan-motor']);
        Post::factory()->for($motor, 'category')->create(['title' => 'Ganti oli matic']);
        Post::factory()->create(['title' => 'Cuci salju']);

        $this->getJson('/api/v1/posts?q=oli')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Ganti oli matic');
        $this->getJson('/api/v1/posts?category=perawatan-motor')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_posts_index_validates_per_page(): void
    {
        $this->getJson('/api/v1/posts?per_page=500')->assertStatus(422)->assertJsonValidationErrors('per_page');
    }

    public function test_post_detail_shape(): void
    {
        $category = PostCategory::factory()->create();
        $post = Post::factory()->for($category, 'category')->create(['content' => '<h2>Judul</h2><p>Isi</p>']);
        Post::factory()->for($category, 'category')->count(2)->create();

        $this->getJson('/api/v1/posts/' . $post->slug)
            ->assertOk()
            ->assertJsonPath('data.slug', $post->slug)
            ->assertJsonPath('data.author_name', 'Tim OtoGo')
            ->assertJsonCount(2, 'data.related')
            ->assertJsonStructure(['data' => ['id', 'slug', 'title', 'content_html', 'author_name', 'related', 'cover_url', 'published_at']]);

        $this->assertStringContainsString('id="judul"', $this->getJson('/api/v1/posts/' . $post->slug)->json('data.content_html'));
    }

    public function test_unpublished_post_returns_json_404(): void
    {
        $draft = Post::factory()->draft()->create();

        $this->getJson('/api/v1/posts/' . $draft->slug)->assertNotFound()->assertJsonStructure(['message']);
        $this->getJson('/api/v1/posts/tidak-ada')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_categories_endpoint_counts_published_posts(): void
    {
        $category = PostCategory::factory()->create(['name' => 'Doorsmeer', 'slug' => 'doorsmeer']);
        Post::factory()->for($category, 'category')->count(2)->create();
        Post::factory()->for($category, 'category')->draft()->create();
        PostCategory::factory()->create(['slug' => 'kosong']);

        $this->getJson('/api/v1/post-categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'doorsmeer')
            ->assertJsonPath('data.0.posts_count', 2);
    }
}

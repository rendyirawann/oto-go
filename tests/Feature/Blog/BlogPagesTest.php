<?php

namespace Tests\Feature\Blog;

use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_index_lists_published_posts(): void
    {
        $published = Post::factory()->create(['title' => 'Merawat Aki Mobil']);
        Post::factory()->draft()->create(['title' => 'Draf Tersembunyi']);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee($published->title)
            ->assertDontSee('Draf Tersembunyi');
    }

    public function test_blog_search_filters_by_title(): void
    {
        Post::factory()->create(['title' => 'Kapan Ganti Oli Matic']);
        Post::factory()->create(['title' => 'Tanda Kampas Rem Habis']);

        $this->get(route('blog.index', ['q' => 'oli']))
            ->assertOk()
            ->assertSee('Kapan Ganti Oli Matic')
            ->assertDontSee('Tanda Kampas Rem Habis')
            ->assertSee('noindex, follow', false);
    }

    public function test_blog_category_filter_and_route(): void
    {
        $motor = PostCategory::factory()->create(['name' => 'Perawatan Motor', 'slug' => 'perawatan-motor']);
        $mobil = PostCategory::factory()->create(['name' => 'Perawatan Mobil', 'slug' => 'perawatan-mobil']);
        Post::factory()->for($motor, 'category')->create(['title' => 'Servis CVT']);
        Post::factory()->for($mobil, 'category')->create(['title' => 'Servis AC Mobil']);

        $this->get(route('blog.index', ['kategori' => 'perawatan-motor']))
            ->assertOk()->assertSee('Servis CVT')->assertDontSee('Servis AC Mobil');

        $this->get(route('blog.category', 'perawatan-mobil'))
            ->assertOk()->assertSee('Servis AC Mobil')->assertDontSee('Servis CVT');
    }

    public function test_article_page_renders_with_toc_and_json_ld(): void
    {
        $post = Post::factory()->create([
            'content' => '<h2>Bagian Pertama</h2><p>Isi.</p><h2>Bagian Kedua</h2><p>Isi lagi.</p>',
        ]);

        $this->get(route('blog.show', $post->slug))
            ->assertOk()
            ->assertSee($post->title)
            ->assertSee('id="bagian-pertama"', false)
            ->assertSee('href="#bagian-kedua"', false)
            ->assertSee('"@type":"Article"', false)
            ->assertSee('<meta property="og:type" content="article" />', false);
    }

    public function test_unpublished_or_missing_articles_return_404(): void
    {
        $draft = Post::factory()->draft()->create();
        $scheduled = Post::factory()->scheduled()->create();

        $this->get(route('blog.show', $draft->slug))->assertNotFound();
        $this->get(route('blog.show', $scheduled->slug))->assertNotFound();
        $this->get('/blog/tidak-ada-artikel-ini')->assertNotFound();
    }

    public function test_blog_index_shows_empty_state(): void
    {
        $this->get(route('blog.index', ['q' => 'tidakadaapapun']))
            ->assertOk()
            ->assertSee('Belum ada artikel yang cocok.');
    }
}

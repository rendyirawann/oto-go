<?php

namespace Tests\Feature\Frontend;

use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_renders_every_key_section(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('id="layanan"', false)
            ->assertSee('id="cara"', false)
            ->assertSee('id="harga"', false)
            ->assertSee('id="usaha"', false)
            ->assertSee('id="faq"', false)
            ->assertSee('estimator-data', false)
            ->assertSee('Medan Tuntungan')
            ->assertSee('Core Technology Indonesia')
            ->assertSee('application/ld+json', false)
            ->assertSee(route('login'), false);
    }

    public function test_estimator_uses_the_medan_price_template(): void
    {
        $template = require database_path('data/medan_price_template.php');
        $mpvWash = number_format($template['items'][0]['prices']['car_mpv'], 0, ',', '.');

        $this->get('/')->assertOk()->assertSee('Rp ' . $mpvWash);
    }

    public function test_landing_shows_latest_published_posts_only(): void
    {
        $category = PostCategory::factory()->create();
        Post::factory()->for($category, 'category')->create(['title' => 'Artikel Terbit Satu']);
        Post::factory()->draft()->create(['title' => 'Artikel Draf Rahasia']);
        Post::factory()->scheduled()->create(['title' => 'Artikel Terjadwal Nanti']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Artikel Terbit Satu')
            ->assertDontSee('Artikel Draf Rahasia')
            ->assertDontSee('Artikel Terjadwal Nanti');
    }

    public function test_landing_cache_is_refreshed_when_a_post_is_saved(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Artikel Paling Baru');

        Post::factory()->create(['title' => 'Artikel Paling Baru']);

        $this->get('/')->assertOk()->assertSee('Artikel Paling Baru');
    }

    public function test_landing_has_no_inline_event_handlers(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=\s*["\']/i', $html);
    }

    public function test_sitemap_and_robots_are_served(): void
    {
        $post = Post::factory()->create();

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('blog.show', $post->slug), false);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee('Sitemap: ' . route('sitemap'));
    }
}

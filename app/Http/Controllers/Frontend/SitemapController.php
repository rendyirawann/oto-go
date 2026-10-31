<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function sitemap(): Response
    {
        $posts = Post::query()
            ->published()
            ->select(['slug', 'updated_at', 'published_at'])
            ->orderByDesc('published_at')
            ->limit(5000)
            ->get();

        $latest = $posts->max('updated_at') ?? now();

        $xml = view('frontend.sitemap', ['posts' => $posts, 'latest' => $latest])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    /** Generated so the Sitemap line always matches the current host. */
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /api/',
            '',
            'Sitemap: ' . route('sitemap'),
            '',
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}

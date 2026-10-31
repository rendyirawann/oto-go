<?php

namespace App\Actions\Blog;

use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Read side of the blog, shared by the website and the API so both run the
 * same (index-friendly, column-limited) queries.
 */
class PublishedPosts
{
    private const TTL = 3600;

    public function paginate(?string $search = null, ?string $categorySlug = null, int $perPage = 9): LengthAwarePaginator
    {
        $perPage = max(1, min(50, $perPage));

        return Post::query()
            ->published()
            ->select(Post::SUMMARY_COLUMNS)
            ->with('category:id,name,slug')
            ->search($search)
            ->when($categorySlug, fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $categorySlug)))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(string $slug): Post
    {
        return Post::query()
            ->published()
            ->with(['category:id,name,slug', 'author:id,name'])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /** Latest posts for the landing page (cached, flushed on save). */
    public function latest(int $limit = 3): Collection
    {
        $posts = $this->remember(Post::CACHE_LATEST, fn () => Post::query()
            ->published()
            ->select(Post::SUMMARY_COLUMNS)
            ->with('category:id,name,slug')
            ->orderByDesc('published_at')
            ->limit(6)
            ->get());

        return $posts->take($limit)->values();
    }

    /** Categories that have at least one published post, with counts. */
    public function categories(): Collection
    {
        return $this->remember(Post::CACHE_CATEGORIES, fn () => PostCategory::query()
            ->select(['id', 'name', 'slug'])
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('name')
            ->get()
            ->filter(fn ($c) => $c->posts_count > 0)
            ->values());
    }

    /** Cache with a hard fallback: an unavailable cache store never breaks a page. */
    private function remember(string $key, \Closure $callback): Collection
    {
        try {
            return Cache::remember($key, self::TTL, $callback);
        } catch (\Throwable) {
            return $callback();
        }
    }
}

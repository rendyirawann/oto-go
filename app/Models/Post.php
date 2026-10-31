<?php

namespace App\Models;

use App\Support\HtmlSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Post extends Model
{
    /** @use HasFactory<\Database\Factories\PostFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Draf',
        self::STATUS_PUBLISHED => 'Terbit',
    ];

    /** Cache keys used by the landing page and blog sidebar. */
    public const CACHE_LATEST = 'blog.latest.v1';
    public const CACHE_CATEGORIES = 'blog.categories.v1';

    /** Columns needed to render a card / API summary (no heavy content). */
    public const SUMMARY_COLUMNS = [
        'id', 'post_category_id', 'author_id', 'title', 'slug', 'excerpt',
        'cover_path', 'status', 'published_at', 'reading_minutes', 'updated_at',
    ];

    protected $fillable = [
        'post_category_id',
        'author_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'cover_path',
        'status',
        'published_at',
        'reading_minutes',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'reading_minutes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::flushCache());
        static::deleted(fn () => self::flushCache());
    }

    public static function flushCache(): void
    {
        try {
            Cache::forget(self::CACHE_LATEST);
            Cache::forget(self::CACHE_CATEGORIES);
        } catch (\Throwable) {
            // A cache outage must never block saving content.
        }
    }

    // ------------------------------------------------------------------
    // Relations & scopes
    // ------------------------------------------------------------------

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Visible on the public site: published and not scheduled in the future. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /** Case-insensitive search on title and excerpt. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        // Wildcards typed by the visitor only widen the match; bindings keep it safe.
        $like = '%' . mb_strtolower(Str::limit($term, 80, '')) . '%';

        return $query->where(function (Builder $q) use ($like) {
            $q->whereRaw('LOWER(title) LIKE ?', [$like])
                ->orWhereRaw('LOWER(excerpt) LIKE ?', [$like]);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ------------------------------------------------------------------
    // Presentation helpers
    // ------------------------------------------------------------------

    /** Absolute URL of the cover, or the brand OG image as a fallback. */
    public function getCoverUrlAttribute(): string
    {
        $path = (string) $this->cover_path;

        if ($path === '') {
            return asset('assets/media/branding/og-image.png');
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, 'assets/')) {
            return asset($path);
        }

        return Storage::disk('public')->url($path);
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    /**
     * Content with id anchors on every h2, plus the table of contents.
     *
     * @return array{html:string,toc:array<int,array{id:string,title:string}>}
     */
    public function renderedContent(): array
    {
        return HtmlSanitizer::withHeadingAnchors((string) $this->content);
    }

    /** Up to $limit other published posts, same category first. */
    public function related(int $limit = 3): Collection
    {
        $base = self::query()
            ->published()
            ->select(self::SUMMARY_COLUMNS)
            ->with('category:id,name,slug')
            ->whereKeyNot($this->getKey());

        $sameCategory = $this->post_category_id
            ? (clone $base)->where('post_category_id', $this->post_category_id)->latest('published_at')->limit($limit)->get()
            : collect();

        if ($sameCategory->count() >= $limit) {
            return $sameCategory;
        }

        $fill = (clone $base)
            ->whereNotIn('id', $sameCategory->pluck('id'))
            ->latest('published_at')
            ->limit($limit - $sameCategory->count())
            ->get();

        return $sameCategory->concat($fill);
    }

    /** Words / 200 wpm, at least one minute. */
    public static function readingMinutes(string $html): int
    {
        $words = str_word_count(strip_tags($html));

        return max(1, (int) ceil($words / 200));
    }

    /** Excerpt fallback derived from the content. */
    public static function excerptFrom(string $html, int $limit = 180): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');

        return Str::limit($text, $limit);
    }
}

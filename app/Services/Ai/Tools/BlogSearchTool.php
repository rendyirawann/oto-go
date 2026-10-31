<?php

namespace App\Services\Ai\Tools;

use App\Services\Ai\AgentContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Searches published OtoGo blog articles so answers can point to them.
 *
 * Written defensively against the posts schema (owned by the blog module):
 * it only uses columns that actually exist and returns nothing when the
 * table is missing.
 */
class BlogSearchTool implements AiTool
{
    public function name(): string
    {
        return 'blog_search';
    }

    public function availableFor(AgentContext $context): bool
    {
        return $this->hasPosts();
    }

    public function definition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name(),
                'description' => 'Cari artikel di blog OtoGo (tips perawatan kendaraan, panduan) untuk direkomendasikan.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Topik artikel, misal "ganti oli matic".'],
                    ],
                    'required' => ['query'],
                ],
            ],
        ];
    }

    public function run(array $arguments, AgentContext $context): array
    {
        $query = Str::lower(trim((string) ($arguments['query'] ?? '')));

        if (! $this->hasPosts()) {
            return ['content' => ['artikel' => [], 'info' => 'Blog belum tersedia.']];
        }

        $columns = Schema::getColumnListing('posts');
        $has = fn (string $c) => in_array($c, $columns, true);

        if (! $has('title') || ! $has('slug')) {
            return ['content' => ['artikel' => []]];
        }

        $select = array_values(array_filter(['title', 'slug', $has('excerpt') ? 'excerpt' : null]));
        $builder = DB::table('posts')->select($select);

        if ($has('status')) {
            $builder->where('status', 'published');
        }

        if ($has('published_at')) {
            $builder->whereNotNull('published_at')->where('published_at', '<=', now());
        }

        if ($has('deleted_at')) {
            $builder->whereNull('deleted_at');
        }

        $words = array_slice(array_filter(preg_split('/\s+/', $query) ?: [], fn ($w) => mb_strlen($w) >= 3), 0, 5);

        if ($words !== []) {
            $builder->where(function ($q) use ($words, $has) {
                foreach ($words as $word) {
                    $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $word) . '%';
                    $q->orWhereRaw('LOWER(title) LIKE ?', [$like]);
                    if ($has('excerpt')) {
                        $q->orWhereRaw('LOWER(excerpt) LIKE ?', [$like]);
                    }
                }
            });
        }

        if ($has('published_at')) {
            $builder->orderByDesc('published_at');
        }

        $rows = $builder->limit(5)->get();

        $articles = $rows->map(fn ($row) => [
            'title' => (string) $row->title,
            'url' => url('/blog/' . $row->slug),
            'ringkasan' => Str::limit((string) ($row->excerpt ?? ''), 220),
        ])->all();

        return [
            'content' => ['artikel' => $articles],
            'sources' => array_map(fn (array $a) => ['title' => $a['title'], 'url' => $a['url']], $articles),
        ];
    }

    private ?bool $hasPosts = null;

    private function hasPosts(): bool
    {
        if ($this->hasPosts !== null) {
            return $this->hasPosts;
        }

        try {
            return $this->hasPosts = Schema::hasTable('posts');
        } catch (\Throwable) {
            return $this->hasPosts = false;
        }
    }
}

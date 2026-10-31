<?php

namespace App\Actions\Blog;

use App\Models\Post;
use App\Models\PostCategory;
use App\Support\HtmlSanitizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Creates or updates a blog post.
 *
 * Content is sanitized here, once, so every renderer can trust it. Covers
 * are written under public/assets/media/blog/uploads so they are served
 * directly by the web server without depending on the storage symlink.
 */
class SavePost
{
    public const UPLOAD_DIR = 'assets/media/blog/uploads';

    /**
     * @param  array<string,mixed>  $data  validated input
     */
    public function handle(array $data, ?Post $post = null, ?UploadedFile $cover = null, ?string $authorId = null): Post
    {
        $post ??= new Post();

        return DB::transaction(function () use ($data, $post, $cover, $authorId) {
            $content = HtmlSanitizer::clean($data['content'] ?? '');

            $categoryId = $data['post_category_id'] ?? null;
            $newCategory = trim((string) ($data['new_category'] ?? ''));

            if ($newCategory !== '') {
                $categoryId = PostCategory::firstOrCreate(
                    ['slug' => Str::slug($newCategory)],
                    ['name' => Str::limit($newCategory, 80, '')]
                )->id;
            }

            $status = $data['status'] ?? Post::STATUS_DRAFT;
            $publishedAt = ! empty($data['published_at'])
                ? Carbon::parse($data['published_at'], config('app.timezone'))
                : null;

            if ($status === Post::STATUS_PUBLISHED && $publishedAt === null) {
                $publishedAt = $post->published_at ?? now();
            }

            $post->fill([
                'post_category_id' => $categoryId ?: null,
                'title' => trim((string) $data['title']),
                'slug' => $this->uniqueSlug($data['slug'] ?? null, (string) $data['title'], $post),
                'excerpt' => trim((string) ($data['excerpt'] ?? '')) ?: Post::excerptFrom($content),
                'content' => $content,
                'status' => $status,
                'published_at' => $publishedAt,
                'reading_minutes' => Post::readingMinutes($content),
                'meta_description' => trim((string) ($data['meta_description'] ?? '')) ?: null,
            ]);

            if (! $post->exists && $authorId !== null) {
                $post->author_id = $authorId;
            }

            if ($cover !== null) {
                $old = $post->cover_path;
                $post->cover_path = $this->storeCover($cover);
                $this->deleteUploadedCover($old);
            } elseif (! empty($data['remove_cover'])) {
                $this->deleteUploadedCover($post->cover_path);
                $post->cover_path = null;
            }

            $post->save();

            return $post;
        });
    }

    private function uniqueSlug(?string $requested, string $title, Post $post): string
    {
        $base = Str::slug(trim((string) $requested) !== '' ? $requested : $title) ?: 'artikel';
        $base = Str::limit($base, 180, '');
        $slug = $base;
        $n = 2;

        while (Post::query()->where('slug', $slug)->when($post->exists, fn ($q) => $q->whereKeyNot($post->getKey()))->exists()) {
            $slug = $base . '-' . $n++;
        }

        return $slug;
    }

    private function storeCover(UploadedFile $file): string
    {
        $dir = public_path(self::UPLOAD_DIR);
        File::ensureDirectoryExists($dir, 0755);

        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'jpg';
        $name = now()->format('Ymd') . '-' . Str::lower(Str::random(16)) . '.' . $extension;

        $file->move($dir, $name);

        return self::UPLOAD_DIR . '/' . $name;
    }

    /** Only files we uploaded are ever deleted - seeded covers stay. */
    private function deleteUploadedCover(?string $path): void
    {
        if ($path && Str::startsWith($path, self::UPLOAD_DIR . '/')) {
            File::delete(public_path($path));
        }
    }
}

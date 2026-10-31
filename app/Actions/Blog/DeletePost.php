<?php

namespace App\Actions\Blog;

use App\Models\Post;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DeletePost
{
    public function handle(Post $post): void
    {
        $cover = $post->cover_path;

        $post->delete();

        if ($cover && Str::startsWith($cover, SavePost::UPLOAD_DIR . '/')) {
            File::delete(public_path($cover));
        }
    }
}

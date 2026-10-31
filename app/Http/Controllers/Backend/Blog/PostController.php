<?php

namespace App\Http\Controllers\Backend\Blog;

use App\Actions\Blog\DeletePost;
use App\Actions\Blog\SavePost;
use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\PostRequest;
use App\Models\Post;
use App\Models\PostCategory;
use App\Support\ActivityRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Post::class);

        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $status = array_key_exists((string) $status, Post::STATUSES) ? $status : null;

        $posts = Post::query()
            ->select(['id', 'post_category_id', 'author_id', 'title', 'slug', 'status', 'published_at', 'reading_minutes', 'cover_path', 'updated_at'])
            ->with(['category:id,name', 'author:id,name'])
            ->search($search)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByRaw('CASE WHEN published_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $counts = Post::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('backend.blog.index', [
            'posts' => $posts,
            'search' => $search,
            'status' => $status,
            'counts' => $counts,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Post::class);

        return view('backend.blog.form', [
            'post' => new Post(['status' => Post::STATUS_DRAFT]),
            'categories' => PostCategory::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(PostRequest $request, SavePost $save): RedirectResponse
    {
        $post = $save->handle($request->validated(), null, $request->file('cover'), $request->user()?->id);

        ActivityRecorder::created('blog', 'Membuat artikel "' . $post->title . '"', $this->snapshot($post), $post);

        return redirect()->route('admin.posts.edit', $post)->with('success', 'Artikel berhasil disimpan.');
    }

    public function edit(Post $post): View
    {
        Gate::authorize('update', $post);

        return view('backend.blog.form', [
            'post' => $post,
            'categories' => PostCategory::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(PostRequest $request, Post $post, SavePost $save): RedirectResponse
    {
        $old = $this->snapshot($post);
        $post = $save->handle($request->validated(), $post, $request->file('cover'));

        ActivityRecorder::updated('blog', 'Memperbarui artikel "' . $post->title . '"', $old, $this->snapshot($post), $post);

        return redirect()->route('admin.posts.edit', $post)->with('success', 'Perubahan artikel tersimpan.');
    }

    public function destroy(Post $post, DeletePost $delete): RedirectResponse
    {
        Gate::authorize('delete', $post);

        $old = $this->snapshot($post);
        $title = $post->title;
        $delete->handle($post);

        ActivityRecorder::deleted('blog', 'Menghapus artikel "' . $title . '"', $old);

        return redirect()->route('admin.posts.index')->with('success', 'Artikel dihapus.');
    }

    /** @return array<string,mixed> */
    private function snapshot(Post $post): array
    {
        return Arr::only($post->toArray(), [
            'id', 'title', 'slug', 'status', 'published_at', 'post_category_id', 'excerpt', 'cover_path', 'meta_description',
        ]);
    }
}

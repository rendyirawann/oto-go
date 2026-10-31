<?php

namespace App\Http\Controllers\Frontend;

use App\Actions\Blog\PublishedPosts;
use App\Http\Controllers\Controller;
use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(private readonly PublishedPosts $posts)
    {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $categorySlug = trim((string) $request->query('kategori', '')) ?: null;

        return $this->listing($search, $categorySlug);
    }

    public function category(PostCategory $category): View
    {
        return $this->listing('', $category->slug, $category);
    }

    public function show(string $slug): View
    {
        $post = $this->posts->find($slug);
        $rendered = $post->renderedContent();

        return view('frontend.blog.show', [
            'post' => $post,
            'contentHtml' => $rendered['html'],
            'toc' => $rendered['toc'],
            'related' => $post->related(3),
        ]);
    }

    private function listing(string $search, ?string $categorySlug, ?PostCategory $category = null): View
    {
        $categories = $this->posts->categories();
        $category ??= $categorySlug ? $categories->firstWhere('slug', $categorySlug) : null;

        return view('frontend.blog.index', [
            'posts' => $this->posts->paginate($search, $categorySlug, 9),
            'categories' => $categories,
            'activeCategory' => $category,
            'search' => $search,
        ]);
    }
}

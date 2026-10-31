<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Blog\PublishedPosts;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostCategoryResource;
use App\Http\Resources\PostDetailResource;
use App\Http\Resources\PostSummaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PostSummary',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'slug', type: 'string', example: 'kapan-harus-ganti-oli-motor-matic'),
        new OA\Property(property: 'title', type: 'string', example: 'Kapan Harus Ganti Oli Motor Matic?'),
        new OA\Property(property: 'excerpt', type: 'string', nullable: true),
        new OA\Property(property: 'cover_url', type: 'string', format: 'uri'),
        new OA\Property(property: 'category', nullable: true, properties: [
            new OA\Property(property: 'name', type: 'string', example: 'Perawatan Motor'),
            new OA\Property(property: 'slug', type: 'string', example: 'perawatan-motor'),
        ], type: 'object'),
        new OA\Property(property: 'reading_minutes', type: 'integer', example: 5),
        new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'PostDetail',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/PostSummary'),
        new OA\Schema(properties: [
            new OA\Property(property: 'content_html', type: 'string', description: 'HTML yang sudah disanitasi; h2 memiliki atribut id.'),
            new OA\Property(property: 'author_name', type: 'string', example: 'Tim OtoGo'),
            new OA\Property(property: 'related', type: 'array', items: new OA\Items(ref: '#/components/schemas/PostSummary')),
        ], type: 'object'),
    ]
)]
#[OA\Schema(
    schema: 'PostCategory',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Perawatan Motor'),
        new OA\Property(property: 'slug', type: 'string', example: 'perawatan-motor'),
        new OA\Property(property: 'posts_count', type: 'integer', example: 3),
    ],
    type: 'object'
)]
class BlogController extends Controller
{
    public function __construct(private readonly PublishedPosts $posts)
    {
    }

    #[OA\Get(
        path: '/api/v1/posts',
        operationId: 'blogPostsIndex',
        summary: 'Daftar artikel blog yang sudah terbit',
        tags: ['Blog'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', maximum: 50, minimum: 1, default: 10)),
            new OA\Parameter(name: 'q', in: 'query', required: false, description: 'Cari di judul dan ringkasan', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category', in: 'query', required: false, description: 'Slug kategori', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Daftar artikel (paginasi)', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PostSummary')),
                new OA\Property(property: 'links', type: 'object'),
                new OA\Property(property: 'meta', properties: [
                    new OA\Property(property: 'current_page', type: 'integer'),
                    new OA\Property(property: 'last_page', type: 'integer'),
                    new OA\Property(property: 'per_page', type: 'integer'),
                    new OA\Property(property: 'total', type: 'integer'),
                ], type: 'object'),
            ])),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'q' => ['nullable', 'string', 'max:80'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $posts = $this->posts->paginate(
            $validated['q'] ?? null,
            $validated['category'] ?? null,
            (int) ($validated['per_page'] ?? 10),
        );

        return PostSummaryResource::collection($posts);
    }

    #[OA\Get(
        path: '/api/v1/posts/{slug}',
        operationId: 'blogPostsShow',
        summary: 'Detail artikel blog',
        tags: ['Blog'],
        parameters: [new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Detail artikel', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/PostDetail'),
            ])),
            new OA\Response(response: 404, description: 'Artikel tidak ditemukan atau belum terbit', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'message', type: 'string'),
            ])),
        ]
    )]
    public function show(string $slug): PostDetailResource
    {
        return new PostDetailResource($this->posts->find($slug));
    }

    #[OA\Get(
        path: '/api/v1/post-categories',
        operationId: 'blogCategoriesIndex',
        summary: 'Kategori blog yang memiliki artikel terbit',
        tags: ['Blog'],
        responses: [
            new OA\Response(response: 200, description: 'Daftar kategori', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PostCategory')),
            ])),
        ]
    )]
    public function categories(): AnonymousResourceCollection
    {
        return PostCategoryResource::collection($this->posts->categories());
    }
}

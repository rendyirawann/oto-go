<?php

namespace App\Http\Requests\Blog;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $post = $this->route('post');

        return $post instanceof Post
            ? (bool) $this->user()?->can('update', $post)
            : (bool) $this->user()?->can('create', Post::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->filled('slug') ? strtolower(trim((string) $this->input('slug'))) : null,
            'remove_cover' => $this->boolean('remove_cover'),
        ]);
    }

    public function rules(): array
    {
        $post = $this->route('post');

        return [
            'title' => ['required', 'string', 'min:5', 'max:180'],
            'slug' => [
                'nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('posts', 'slug')->ignore($post instanceof Post ? $post->id : null),
            ],
            'post_category_id' => ['nullable', 'integer', 'exists:post_categories,id'],
            'new_category' => ['nullable', 'string', 'max:80'],
            'excerpt' => ['nullable', 'string', 'max:400'],
            'content' => ['required', 'string', 'min:20', 'max:200000'],
            'status' => ['required', Rule::in(array_keys(Post::STATUSES))],
            'published_at' => ['nullable', 'date'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=600,min_height=300,max_width=4000,max_height=4000'],
            'remove_cover' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'judul',
            'slug' => 'slug',
            'post_category_id' => 'kategori',
            'new_category' => 'kategori baru',
            'excerpt' => 'ringkasan',
            'content' => 'isi artikel',
            'status' => 'status',
            'published_at' => 'tanggal terbit',
            'meta_description' => 'deskripsi meta',
            'cover' => 'gambar sampul',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'Slug hanya boleh huruf kecil, angka, dan tanda hubung.',
            'cover.dimensions' => 'Gambar sampul minimal 600×300 piksel.',
        ];
    }
}

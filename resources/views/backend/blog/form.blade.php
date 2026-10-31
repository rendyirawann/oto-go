@extends('backend.layout.app')

@php
    $editing = $post->exists;
    $publishedValue = old('published_at', $post->published_at?->timezone(config('app.timezone'))->format('Y-m-d\TH:i'));
@endphp

@section('title', $editing ? 'Ubah artikel' : 'Artikel baru')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-6">
        <div class="min-w-0">
            <a href="{{ route('admin.posts.index') }}" class="text-muted text-hover-primary fs-7"><i class="ki-outline ki-arrow-left fs-6"></i> Kembali ke daftar</a>
            <h1 class="fs-2 fw-bold text-gray-900 mb-0 mt-2">{{ $editing ? 'Ubah artikel' : 'Artikel baru' }}</h1>
        </div>
        @if ($editing && $post->is_published)
            <a href="{{ route('blog.show', $post->slug) }}" class="btn btn-sm btn-light" target="_blank" rel="noopener">
                <i class="ki-outline ki-exit-up fs-5"></i> Lihat di blog
            </a>
        @endif
    </div>

    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start p-5 mb-6" role="alert">
            <i class="ki-outline ki-shield-cross fs-2hx text-danger me-4"></i>
            <div>
                <h4 class="mb-2 text-danger">Periksa kembali isian Anda</h4>
                <ul class="mb-0 ps-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data"
        action="{{ $editing ? route('admin.posts.update', $post) : route('admin.posts.store') }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="row g-6">
            <div class="col-xl-8">
                <div class="card card-flush">
                    <div class="card-body pt-8">
                        <div class="mb-7">
                            <label for="title" class="form-label required">Judul</label>
                            <input type="text" id="title" name="title" maxlength="180" required
                                class="form-control form-control-lg @error('title') is-invalid @enderror"
                                value="{{ old('title', $post->title) }}" />
                        </div>

                        <div class="mb-7">
                            <label for="slug" class="form-label">Slug</label>
                            <div class="input-group">
                                <span class="input-group-text text-muted fs-7">/blog/</span>
                                <input type="text" id="slug" name="slug" maxlength="200" pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                                    class="form-control @error('slug') is-invalid @enderror"
                                    value="{{ old('slug', $post->slug) }}" placeholder="dibuat otomatis dari judul" />
                            </div>
                        </div>

                        <div class="mb-7">
                            <label for="excerpt" class="form-label">Ringkasan</label>
                            <textarea id="excerpt" name="excerpt" rows="2" maxlength="400"
                                class="form-control @error('excerpt') is-invalid @enderror"
                                placeholder="1–2 kalimat untuk kartu artikel dan mesin pencari">{{ old('excerpt', $post->excerpt) }}</textarea>
                        </div>

                        <div>
                            <label for="content" class="form-label required">Isi artikel (HTML)</label>
                            <textarea id="content" name="content" rows="22" required
                                class="form-control font-monospace fs-7 @error('content') is-invalid @enderror">{{ old('content', $post->content) }}</textarea>
                            <div class="form-text">
                                Tag yang diizinkan: <code>p h2 h3 h4 ul ol li strong em a blockquote code pre figure figcaption img table hr br</code>.
                                Tag lain, skrip, dan atribut event dibuang otomatis saat disimpan. Setiap <code>&lt;h2&gt;</code> menjadi daftar isi.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card card-flush mb-6">
                    <div class="card-header"><h3 class="card-title fs-5">Publikasi</h3></div>
                    <div class="card-body pt-0">
                        <div class="mb-6">
                            <label for="status" class="form-label required">Status</label>
                            <select id="status" name="status" class="form-select">
                                @foreach (\App\Models\Post::STATUSES as $key => $label)
                                    <option value="{{ $key }}" @selected(old('status', $post->status) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label for="published_at" class="form-label">Tanggal terbit</label>
                            <input type="datetime-local" id="published_at" name="published_at" class="form-control" value="{{ $publishedValue }}" />
                            <div class="form-text">Kosongkan untuk terbit sekarang. Tanggal di masa depan = terjadwal.</div>
                        </div>
                    </div>
                </div>

                <div class="card card-flush mb-6">
                    <div class="card-header"><h3 class="card-title fs-5">Kategori</h3></div>
                    <div class="card-body pt-0">
                        <select name="post_category_id" class="form-select mb-4" aria-label="Kategori">
                            <option value="">Tanpa kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('post_category_id', $post->post_category_id) === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="new_category" maxlength="80" class="form-control" value="{{ old('new_category') }}"
                            placeholder="…atau ketik kategori baru" aria-label="Kategori baru" />
                    </div>
                </div>

                <div class="card card-flush mb-6">
                    <div class="card-header"><h3 class="card-title fs-5">Gambar sampul</h3></div>
                    <div class="card-body pt-0">
                        <img src="{{ $post->cover_url }}" alt="" class="w-100 rounded border mb-4" style="aspect-ratio: 1200 / 630; object-fit: cover;" />
                        <input type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="form-control @error('cover') is-invalid @enderror" />
                        <div class="form-text">JPG/PNG/WebP, maks 2 MB, minimal 600×300 (disarankan 1200×630).</div>
                        @if ($editing && $post->cover_path)
                            <label class="form-check form-check-sm mt-4">
                                <input class="form-check-input" type="checkbox" name="remove_cover" value="1" />
                                <span class="form-check-label">Hapus sampul</span>
                            </label>
                        @endif
                    </div>
                </div>

                <div class="card card-flush mb-6">
                    <div class="card-header"><h3 class="card-title fs-5">SEO</h3></div>
                    <div class="card-body pt-0">
                        <label for="meta_description" class="form-label">Deskripsi meta</label>
                        <textarea id="meta_description" name="meta_description" rows="3" maxlength="300" class="form-control">{{ old('meta_description', $post->meta_description) }}</textarea>
                        <div class="form-text">Kosongkan untuk memakai ringkasan.</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="ki-outline ki-check fs-4"></i> {{ $editing ? 'Simpan perubahan' : 'Simpan artikel' }}
                </button>
            </div>
        </div>
    </form>
@endsection

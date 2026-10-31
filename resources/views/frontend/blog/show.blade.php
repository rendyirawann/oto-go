@extends('frontend.layout')

@php
    $description = $post->meta_description ?: $post->excerpt;
    $articleLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $post->title,
        'description' => $description,
        'image' => [$post->cover_url],
        'datePublished' => $post->published_at?->toIso8601String(),
        'dateModified' => $post->updated_at?->toIso8601String(),
        'inLanguage' => 'id-ID',
        'mainEntityOfPage' => route('blog.show', $post->slug),
        'author' => ['@type' => 'Organization', 'name' => $post->author?->name ?: 'Tim OtoGo'],
        'publisher' => [
            '@type' => 'Organization',
            'name' => $brand['name'],
            'logo' => ['@type' => 'ImageObject', 'url' => asset('assets/media/branding/icon-512.png')],
        ],
    ];
    $breadcrumbLd = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => array_values(array_filter([
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Blog', 'item' => route('blog.index')],
            $post->category ? ['@type' => 'ListItem', 'position' => 2, 'name' => $post->category->name, 'item' => route('blog.category', $post->category->slug)] : null,
            ['@type' => 'ListItem', 'position' => $post->category ? 3 : 2, 'name' => $post->title],
        ])),
    ];
@endphp

@section('title', $post->title)
@section('meta_description', (string) $description)
@section('meta_image', $post->cover_url)
@section('og_type', 'article')
@section('canonical', route('blog.show', $post->slug))

@push('head')
    @if ($post->published_at)
        <meta property="article:published_time" content="{{ $post->published_at->toIso8601String() }}" />
    @endif
    <meta property="article:modified_time" content="{{ $post->updated_at?->toIso8601String() }}" />
    @if ($post->category)
        <meta property="article:section" content="{{ $post->category->name }}" />
    @endif
    <script type="application/ld+json">{!! json_encode($articleLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    <script type="application/ld+json">{!! json_encode($breadcrumbLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
    <article>
        <header class="page-head" style="padding-bottom: 0;">
            <div class="wrap">
                <nav class="crumbs" aria-label="Breadcrumb">
                    <a href="{{ route('blog.index') }}">Blog</a>
                    @if ($post->category)
                        <span aria-hidden="true">/</span>
                        <a href="{{ route('blog.category', $post->category->slug) }}">{{ $post->category->name }}</a>
                    @endif
                </nav>
                <div class="article-head">
                    <h1 class="display" data-reveal>{{ $post->title }}</h1>
                    @if ($post->excerpt)
                        <p class="lede" data-reveal style="--d: 80ms">{{ $post->excerpt }}</p>
                    @endif
                    <div class="article-meta" data-reveal style="--d: 140ms">
                        <span>Oleh <strong>{{ $post->author?->name ?: 'Tim OtoGo' }}</strong></span>
                        @if ($post->published_at)
                            <time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->translatedFormat('j F Y') }}</time>
                        @endif
                        <span>{{ $post->reading_minutes }} menit baca</span>
                    </div>
                </div>
                <figure class="article-cover" data-reveal="fade">
                    <img src="{{ $post->cover_url }}" alt="" width="1200" height="630" fetchpriority="high" decoding="async" />
                </figure>
            </div>
        </header>

        <div class="wrap">
            <div class="article-layout">
                <div class="prose">
                    {!! $contentHtml !!}
                </div>

                <aside class="toc" aria-label="Daftar isi">
                    @if (count($toc) > 1)
                        <h2>Daftar isi</h2>
                        <ol data-toc>
                            @foreach ($toc as $item)
                                <li><a href="#{{ $item['id'] }}">{{ $item['title'] }}</a></li>
                            @endforeach
                        </ol>
                    @endif
                    <div class="toc__share">
                        <button type="button" class="btn btn--ghost btn--sm" data-copy-link="{{ route('blog.show', $post->slug) }}">
                            <span>Salin tautan</span>
                        </button>
                    </div>
                </aside>
            </div>
        </div>
    </article>

    <section class="section">
        <div class="wrap">
            <div class="app-cta" data-reveal>
                <div>
                    <p class="eyebrow">Estimasi harga</p>
                    <h2 class="display h-md">Mau tahu kisaran biayanya untuk kendaraanmu?</h2>
                    <p class="lede">Cek estimasi jasa per kategori kendaraan di Medan, dari cuci salju sampai servis AC.</p>
                    <div class="hero__ctas" style="margin-top: 22px;">
                        <a class="btn btn--primary" href="{{ route('home') }}#harga">
                            Lihat estimasi
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                        </a>
                    </div>
                </div>
                <div class="app-cta__mark" aria-hidden="true">
                    <img src="{{ asset('assets/media/branding/logo-mark.svg') }}" alt="" width="240" height="240" loading="lazy" />
                </div>
            </div>

            @if ($related->isNotEmpty())
                <div class="section__head" style="margin-top: clamp(64px, 8vw, 110px);">
                    <div>
                        <p class="eyebrow">Baca juga</p>
                        <h2 class="display h-md">Artikel terkait</h2>
                    </div>
                </div>
                <div class="post-grid">
                    @foreach ($related as $i => $item)
                        @include('frontend.partials.post-card', ['post' => $item, 'delay' => $i * 80])
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection

@extends('frontend.layout')

@php
    $isFiltered = $search !== '' || $activeCategory;
    $pageTitle = $activeCategory ? 'Blog: ' . $activeCategory->name : 'Blog';
    $pageDescription = $activeCategory
        ? 'Artikel ' . $activeCategory->name . ' dari OtoGo: tips perawatan kendaraan dan estimasi biaya di Kota Medan.'
        : 'Blog OtoGo: tips perawatan motor dan mobil, doorsmeer, dan estimasi biaya servis di Kota Medan.';
@endphp

@section('title', $pageTitle)
@section('meta_description', $pageDescription)
@if ($search !== '' || request()->integer('page') > 1)
    @section('robots', 'noindex, follow')
@endif

@section('content')
    <section class="page-head">
        <div class="wrap">
            <p class="eyebrow" data-reveal>Blog OtoGo</p>
            <h1 class="display h-xl" data-reveal style="--d: 60ms">
                @if ($activeCategory)
                    {{ $activeCategory->name }}.
                @else
                    Catatan perawatan kendaraan.
                @endif
            </h1>
            <p class="lede" data-reveal style="--d: 120ms">Tips praktis dari sudut pandang bengkel dan doorsmeer: kapan harus servis, tanda-tanda yang jangan diabaikan, dan kisaran biayanya di Medan.</p>

            <div class="blog-tools" data-reveal style="--d: 160ms">
                <form class="search" method="GET" action="{{ route('blog.index') }}" role="search">
                    <label class="sr-only" for="blog-q">Cari artikel</label>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="6.5" /><path d="m20 20-4.2-4.2" /></svg>
                    <input id="blog-q" type="search" name="q" value="{{ $search }}" maxlength="80" placeholder="Cari: oli, aki, kampas rem…" autocomplete="off" />
                    @if ($activeCategory)
                        <input type="hidden" name="kategori" value="{{ $activeCategory->slug }}" />
                    @endif
                </form>

                @if ($categories->isNotEmpty())
                    <nav class="chips" aria-label="Kategori">
                        <a class="chip {{ ! $activeCategory ? 'is-active' : '' }}" href="{{ route('blog.index', array_filter(['q' => $search ?: null])) }}">Semua</a>
                        @foreach ($categories as $cat)
                            <a class="chip {{ $activeCategory && $activeCategory->slug === $cat->slug ? 'is-active' : '' }}"
                                href="{{ route('blog.index', array_filter(['kategori' => $cat->slug, 'q' => $search ?: null])) }}">
                                {{ $cat->name }} <small>{{ $cat->posts_count }}</small>
                            </a>
                        @endforeach
                    </nav>
                @endif
            </div>
        </div>
    </section>

    <section class="section" style="padding-top: 0;">
        <div class="wrap">
            @if ($posts->isEmpty())
                <div class="empty">
                    <strong>Belum ada artikel yang cocok.</strong>
                    @if ($search !== '')
                        Coba kata kunci lain, atau <a href="{{ route('blog.index') }}">lihat semua artikel</a>.
                    @else
                        Artikel baru akan muncul di sini.
                    @endif
                </div>
            @else
                @if ($search !== '')
                    <p class="muted" style="margin: 0 0 24px;">{{ $posts->total() }} artikel untuk “{{ $search }}”</p>
                @endif

                <div class="post-grid">
                    @foreach ($posts as $i => $post)
                        @include('frontend.partials.post-card', [
                            'post' => $post,
                            'feature' => $i === 0 && ! $isFiltered && $posts->onFirstPage(),
                            'delay' => ($i % 3) * 80,
                        ])
                    @endforeach
                </div>

                {{ $posts->links('frontend.partials.pagination') }}
            @endif
        </div>
    </section>
@endsection

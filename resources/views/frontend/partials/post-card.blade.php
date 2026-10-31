{{-- $post (summary columns + category), optional $feature (bool), $delay (ms) --}}
@php($feature = $feature ?? false)
<a class="post-card {{ $feature ? 'post-card--feature' : '' }}" href="{{ route('blog.show', $post->slug) }}"
    data-reveal style="--d: {{ (int) ($delay ?? 0) }}ms">
    <div class="post-card__cover">
        <img src="{{ $post->cover_url }}" alt="" width="1200" height="630" loading="lazy" decoding="async" />
    </div>
    <div>
        <div class="post-card__meta">
            @if ($post->category)
                <span class="tag">{{ $post->category->name }}</span>
            @endif
            @if ($post->published_at)
                <time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->translatedFormat('j F Y') }}</time>
            @endif
            <span>· {{ $post->reading_minutes }} menit baca</span>
        </div>
        <h3>{{ $post->title }}</h3>
        @if ($post->excerpt)
            <p>{{ $post->excerpt }}</p>
        @endif
    </div>
</a>

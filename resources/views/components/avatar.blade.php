@props([
    // `false` is the "not supplied" sentinel: passing :user="$maybeNull"
    // explicitly must render the anonymous placeholder, not the viewer.
    'user' => false,
    'size' => 36,
    'fontSize' => null,
])

@php
    $user = $user === false ? auth()->user() : $user;
    $url = $user?->avatar_url;
    $initials = $user?->initials ?? '?';
    $label = $user?->name ?? 'Sistem';
    $fontSize = $fontSize ?? max(10, (int) round($size * 0.38));
@endphp

{{--
    The initials sit underneath a real <img>. If the image 404s the <img>
    removes itself and the initials show through, so a broken avatar can
    never leave an empty box or a browser error glyph.
--}}
<span {{ $attributes->merge(['class' => 'app-avatar']) }}
      style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ $fontSize }}px;"
      title="{{ $label }}">
    <span aria-hidden="true">{{ $initials }}</span>
    @if ($url)
        <img src="{{ $url }}" alt="{{ $label }}" loading="lazy" onerror="this.remove();" />
    @endif
</span>

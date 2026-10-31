{{--
    SEO head for the public site. Pages override with sections:
      title, meta_description, meta_image, og_type, canonical
    and may push extra tags (JSON-LD, article:*) to the "head" stack.
--}}
@php
    $pageTitle = trim($__env->yieldContent('title'));
    $fullTitle = $pageTitle !== '' ? $pageTitle . ' — ' . $brand['name'] : $brand['name'] . ' — ' . $brand['tagline'];
    $metaDescription = trim($__env->yieldContent('meta_description')) ?: $brand['description'];
    $metaImage = trim($__env->yieldContent('meta_image')) ?: $brand['og_image_url'];
    $ogType = trim($__env->yieldContent('og_type')) ?: 'website';
    $canonical = trim($__env->yieldContent('canonical')) ?: url()->current();
    $robots = trim($__env->yieldContent('robots')) ?: 'index, follow, max-image-preview:large';
@endphp

<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
<meta name="theme-color" content="#f5f3ee" media="(prefers-color-scheme: light)" />
<meta name="theme-color" content="#0c0e11" media="(prefers-color-scheme: dark)" />
<meta name="color-scheme" content="light dark" />

<title>{{ $fullTitle }}</title>

<meta name="description" content="{{ $metaDescription }}" />
<meta name="author" content="{{ $brand['author'] }}" />
<meta name="application-name" content="{{ $brand['name'] }}" />
<meta name="robots" content="{{ $robots }}" />
<link rel="canonical" href="{{ $canonical }}" />

@if ($brand['google_verification'])
    <meta name="google-site-verification" content="{{ $brand['google_verification'] }}" />
@endif

<meta property="og:type" content="{{ $ogType }}" />
<meta property="og:locale" content="id_ID" />
<meta property="og:site_name" content="{{ $brand['name'] }}" />
<meta property="og:title" content="{{ $fullTitle }}" />
<meta property="og:description" content="{{ $metaDescription }}" />
<meta property="og:url" content="{{ $canonical }}" />
<meta property="og:image" content="{{ $metaImage }}" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="630" />
<meta property="og:image:alt" content="{{ $pageTitle !== '' ? $pageTitle : $brand['name'] }}" />

<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $fullTitle }}" />
<meta name="twitter:description" content="{{ $metaDescription }}" />
<meta name="twitter:image" content="{{ $metaImage }}" />
@if ($brand['twitter'])
    <meta name="twitter:site" content="{{ \Illuminate\Support\Str::start($brand['twitter'], '@') }}" />
@endif

<link rel="icon" href="{{ $brand['favicon_url'] }}" sizes="any" />
<link rel="icon" type="image/svg+xml" href="{{ $brand['favicon_svg_url'] ?? asset('assets/media/branding/favicon.svg') }}" />
<link rel="icon" type="image/png" sizes="32x32" href="{{ $brand['favicon_png_url'] }}" />
<link rel="apple-touch-icon" sizes="180x180" href="{{ $brand['apple_icon_url'] }}" />
<link rel="manifest" href="{{ $brand['manifest_url'] }}" />
<link rel="alternate" type="application/xml" title="Sitemap" href="{{ route('sitemap') }}" />

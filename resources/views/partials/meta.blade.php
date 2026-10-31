{{--
    Shared SEO / social / favicon head block.

    Every value comes from Settings (App\Support\Brand), so changing the
    application name, description, logo or favicon in the admin panel updates
    every page at once.

    Per-page overrides:
      @section('title', 'Users')
      @section('meta_description', '...')
--}}
@php
    $pageTitle = trim($__env->yieldContent('title'));
    $fullTitle = $pageTitle !== '' ? $pageTitle . ' — ' . $brand['name'] : $brand['name'] . ' — ' . $brand['tagline'];
    $metaDescription = trim($__env->yieldContent('meta_description')) ?: $brand['description'];
    $canonical = url()->current();
@endphp

<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
<meta name="theme-color" content="{{ $brand['theme_color'] }}" />

<title>{{ $fullTitle }}</title>

<meta name="description" content="{{ $metaDescription }}" />
<meta name="keywords" content="{{ $brand['keywords'] }}" />
<meta name="author" content="{{ $brand['author'] }}" />
<meta name="application-name" content="{{ $brand['name'] }}" />
<meta name="apple-mobile-web-app-title" content="{{ $brand['short_name'] }}" />
<meta name="robots" content="{{ $brand['robots'] }}" />
<meta name="referrer" content="strict-origin-when-cross-origin" />
<link rel="canonical" href="{{ $canonical }}" />

@if ($brand['google_verification'])
    <meta name="google-site-verification" content="{{ $brand['google_verification'] }}" />
@endif

{{-- Open Graph --}}
<meta property="og:type" content="website" />
<meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) === 'en' ? 'id_ID' : str_replace('-', '_', app()->getLocale()) }}" />
<meta property="og:site_name" content="{{ $brand['name'] }}" />
<meta property="og:title" content="{{ $fullTitle }}" />
<meta property="og:description" content="{{ $metaDescription }}" />
<meta property="og:url" content="{{ $canonical }}" />
<meta property="og:image" content="{{ $brand['og_image_url'] }}" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="630" />
<meta property="og:image:alt" content="{{ $brand['name'] }}" />

{{-- Twitter / X --}}
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $fullTitle }}" />
<meta name="twitter:description" content="{{ $metaDescription }}" />
<meta name="twitter:image" content="{{ $brand['og_image_url'] }}" />
@if ($brand['twitter'])
    <meta name="twitter:site" content="{{ Str::start($brand['twitter'], '@') }}" />
@endif

{{-- Favicon set --}}
<link rel="icon" href="{{ $brand['favicon_url'] }}" sizes="any" />
<link rel="icon" type="image/png" sizes="32x32" href="{{ $brand['favicon_png_url'] }}" />
<link rel="apple-touch-icon" sizes="180x180" href="{{ $brand['apple_icon_url'] }}" />
<link rel="manifest" href="{{ $brand['manifest_url'] }}" />

<meta name="csrf-token" content="{{ csrf_token() }}" />

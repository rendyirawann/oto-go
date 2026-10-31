<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.theme-boot')

    <script>
        // Marks when the entry preloader became visible, so app-auth.js can
        // hold it for exactly 1.2s no matter how fast the page loaded.
        window.__authPreloadStart = Date.now();

        // Click-jacking backstop for browsers that ignore X-Frame-Options.
        if (window.top !== window.self) {
            window.top.location.replace(window.self.location.href);
        }
    </script>

    @include('partials.meta')

    <link rel="preload" href="{{ asset('assets/fonts/plus-jakarta-sans-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin />
    <link href="{{ asset('assets/fonts/otogo-fonts.css') }}?v={{ @filemtime(public_path('assets/fonts/otogo-fonts.css')) }}" rel="stylesheet" type="text/css" />

    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app-auth.css') }}?v={{ @filemtime(public_path('assets/css/app-auth.css')) }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/otogo-tokens.css') }}?v={{ @filemtime(public_path('assets/css/otogo-tokens.css')) }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/otogo-auth.css') }}?v={{ @filemtime(public_path('assets/css/otogo-auth.css')) }}" rel="stylesheet" type="text/css" />

    <style>
        :root {
            --bs-font-sans-serif: 'Plus Jakarta Sans', system-ui, sans-serif;
        }
    </style>

    @stack('stylesheets')
</head>

<body class="auth-body">

    {{-- Entry preloader: rendered server-side so it is on screen from the
         very first paint. app-auth.js fades it out after 1.2s; the CSS
         carries the same timing as a no-JS failsafe. --}}
    <div id="auth-preloader" role="status" aria-live="polite">
        <div class="auth-preloader__mark">
            <span class="auth-preloader__ring" aria-hidden="true"></span>
            <span class="auth-preloader__ring auth-preloader__ring--alt" aria-hidden="true"></span>
            <img src="{{ $brand['logo_url'] }}" alt="" width="56" height="56" />
        </div>
        <div class="auth-preloader__label">{{ $brand['name'] }}</div>
        <div class="auth-preloader__bar" aria-hidden="true"><span></span></div>
        <span class="visually-hidden">Memuat aplikasi…</span>
    </div>

    <div class="auth-shell">
        <div class="auth-bg" aria-hidden="true"></div>

        {{-- "Lane flow" canvas drawn locally by app-auth.js (no CDN). Ground
             colours match --auth-splash; the accent is OtoGo orange. --}}
        <div id="auth-vanta" aria-hidden="true"
            data-color-light="#ff5a1f" data-bg-light="#f5f3ee"
            data-color-dark="#ff6a33" data-bg-dark="#0c0e11"></div>

        <!--begin::Story panel-->
        <section class="auth-story">
            <a href="{{ url('/') }}" class="auth-story__brand text-decoration-none">
                <img src="{{ $brand['logo_url'] }}" alt="" class="auth-story__logo" width="52" height="52" />
                <span>
                    <span class="auth-story__name d-block">{{ $brand['name'] }}</span>
                    <span class="auth-story__tagline">{{ $brand['tagline'] }}</span>
                </span>
            </a>

            <div>
                <h1 class="auth-story__headline">Portal pengelola OtoGo.</h1>
                <p class="auth-story__lead">Ruang kerja tim untuk menjalankan platform bengkel, doorsmeer, dan sparepart di Kota Medan.</p>

                <ul class="auth-story__points">
                    <li>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                        Pengguna, peran, dan hak akses per tim
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                        Konten blog dan asisten AI untuk operasional
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                        Jejak audit untuk setiap perubahan data
                    </li>
                </ul>

                <div class="auth-story__plate" aria-hidden="true">BK 1 OTO <small>MEDAN</small></div>
            </div>

            <p class="auth-story__foot mb-0">
                &copy; {{ now()->year }} {{ $brand['owner'] }} &middot; {{ $brand['name'] }}
            </p>
        </section>
        <!--end::Story panel-->

        <!--begin::Form panel-->
        <main class="auth-panel">
            @yield('content')
        </main>
        <!--end::Form panel-->
    </div>

    <!--begin::Sign-in progress overlay-->
    <div id="auth-progress" role="status" aria-live="polite" aria-hidden="true">
        <div class="auth-progress__ring" aria-hidden="true"></div>
        <p class="auth-progress__step" data-progress-step>Memverifikasi kredensial</p>
        <div class="auth-progress__dots" data-progress-dots aria-hidden="true">
            <i></i><i></i><i></i>
        </div>
    </div>
    <!--end::Sign-in progress overlay-->

    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/app-auth.js') }}"></script>
    @stack('scripts')

    @include('partials._rt')
</body>

</html>

<!DOCTYPE html>
<html lang="id" class="no-js" data-theme="light">

<head>
    {{-- Theme before first paint: saved choice, else the OS preference. --}}
    <script>
        (function () {
            var t = 'light';
            try {
                var saved = localStorage.getItem('otogo:site-theme');
                if (saved === 'light' || saved === 'dark') { t = saved; }
                else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) { t = 'dark'; }
            } catch (e) { /* storage disabled */ }
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>

    @include('frontend.partials.meta')

    {{-- Self-hosted fonts: no third-party request, nothing for a firewall to block. --}}
    <link rel="preload" href="{{ asset('assets/fonts/bricolage-grotesque-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin />
    <link rel="stylesheet" href="{{ asset('assets/fonts/otogo-fonts.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/otogo-tokens.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/frontend/site.css') }}?v={{ @filemtime(public_path('assets/frontend/site.css')) }}" />

    @stack('head')
</head>

<body class="site">
    <a class="skip-link" href="#konten">Langsung ke konten</a>

    @include('frontend.partials.nav')

    <main id="konten">
        @yield('content')
    </main>

    @include('frontend.partials.footer')

    <script src="{{ asset('assets/frontend/site.js') }}?v={{ @filemtime(public_path('assets/frontend/site.js')) }}" defer></script>
    @stack('scripts')
</body>

</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.theme-boot')

    @include('partials.meta')

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $brand['font']) }}:wght@400;600;700;800&display=swap" />

    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app-auth.css') }}" rel="stylesheet" type="text/css" />

    <style>
        :root {
            --auth-bg-light: url('{{ asset('assets/media/auth/bg11.jpg') }}');
            --auth-bg-dark: url('{{ asset('assets/media/auth/bg11-dark.jpg') }}');
        }

        body {
            font-family: '{{ $brand['font'] }}', system-ui, sans-serif;
        }
    </style>
</head>

<body class="auth-body">
    <div class="auth-shell" style="grid-template-columns: 1fr;">
        <div class="auth-bg" aria-hidden="true"></div>

        <main class="auth-panel">
            <div class="auth-card text-center">
                <img src="{{ $brand['logo_url'] }}" alt="" width="64" height="64"
                    style="border-radius: 18px; margin: 0 auto 22px; display: block;" />

                <h1 class="auth-card__title">Sedang Pemeliharaan</h1>
                <p class="auth-card__subtitle mb-0">
                    {{ $brand['name'] }} sedang dalam proses pemeliharaan sistem.
                    Silakan kembali beberapa saat lagi.
                </p>

                <div class="auth-separator">Terima kasih atas kesabaran Anda</div>

                <a href="{{ url()->current() }}" class="auth-submit d-inline-block text-decoration-none">
                    Muat ulang halaman
                </a>

                <p class="auth-foot mb-0">
                    &copy; {{ now()->year }} {{ $brand['owner'] }} &middot; {{ $brand['name'] }}
                </p>
            </div>
        </main>
    </div>

    @include('partials._rt')
</body>

</html>

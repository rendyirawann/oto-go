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

        .error-code {
            font-size: clamp(3.5rem, 12vw, 6rem);
            font-weight: 800;
            line-height: 1;
            letter-spacing: -.04em;
            margin: 0 0 8px;
            background: linear-gradient(135deg, #818cf8, #e879f9);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
    </style>
</head>

<body class="auth-body">
    <div class="auth-shell" style="grid-template-columns: 1fr;">
        <div class="auth-bg" aria-hidden="true"></div>

        <main class="auth-panel">
            <div class="auth-card text-center">
                <img src="{{ $brand['logo_url'] }}" alt="" width="56" height="56"
                    style="border-radius: 16px; margin: 0 auto 20px; display: block;" />

                <p class="error-code">@yield('code')</p>
                <h1 class="auth-card__title">@yield('heading')</h1>
                <p class="auth-card__subtitle mb-0">@yield('message')</p>

                <div class="auth-separator">{{ $brand['name'] }}</div>

                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}"
                    class="auth-submit d-inline-block text-decoration-none">Kembali</a>

                <p class="auth-foot mb-0">
                    &copy; {{ now()->year }} {{ $brand['owner'] }}
                </p>
            </div>
        </main>
    </div>

    @include('partials._rt')
</body>

</html>

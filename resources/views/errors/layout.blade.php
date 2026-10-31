<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.theme-boot')

    @include('partials.meta')

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=JetBrains+Mono:wght@500;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" />

    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/app-auth.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/otogo-tokens.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/otogo-auth.css') }}" rel="stylesheet" type="text/css" />

    <style>
        .error-code {
            display: inline-flex;
            align-items: center;
            gap: .4em;
            margin: 0 0 14px;
            padding: .18em .55em;
            border-radius: 12px;
            background: #111418;
            color: #f5f3ee;
            box-shadow: inset 0 0 0 2px #111418, inset 0 0 0 3.5px rgba(245, 243, 238, .7);
            font-family: var(--og-font-mono);
            font-size: clamp(2rem, 7vw, 3rem);
            font-weight: 700;
            letter-spacing: .12em;
            line-height: 1.1;
        }

        .error-code::after {
            content: "BK";
            font-size: .32em;
            letter-spacing: .1em;
            opacity: .6;
        }

        .error-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .error-actions .auth-submit {
            width: auto;
            padding-inline: 22px;
        }

        .error-actions .error-secondary {
            display: inline-flex;
            align-items: center;
            padding: 0 22px;
            border-radius: 12px;
            border: 1px solid var(--auth-line);
            color: var(--auth-ink);
            text-decoration: none;
            font-weight: 600;
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

                <div class="error-actions">
                    <a href="{{ url('/') }}" class="auth-submit d-inline-block text-decoration-none">Ke beranda</a>
                    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}"
                        class="error-secondary">Kembali</a>
                </div>

                <p class="auth-foot mb-0">
                    &copy; {{ now()->year }} {{ $brand['owner'] }}
                </p>
            </div>
        </main>
    </div>

    @include('partials._rt')
</body>

</html>

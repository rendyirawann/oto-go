@extends('auth.app')

@section('title', 'Masuk')
@section('meta_description', 'Halaman masuk ke panel administrasi ' . $brand['name'] . '.')

@section('content')
    @php
        $socialProviders = [
            'google' => [
                'enabled' => ($appSettings['social_google_enabled'] ?? '0') === '1',
                'label' => 'Google',
                'driver' => 'google',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>',
            ],
            'facebook' => [
                'enabled' => ($appSettings['social_facebook_enabled'] ?? '0') === '1',
                'label' => 'Facebook',
                'driver' => 'facebook',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" fill="#1877F2"/></svg>',
            ],
            'github' => [
                'enabled' => ($appSettings['social_github_enabled'] ?? '0') === '1',
                'label' => 'GitHub',
                'driver' => 'github',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23a11.5 11.5 0 013-.405c1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12z" fill="#f0f6fc"/></svg>',
            ],
            'linkedin' => [
                'enabled' => ($appSettings['social_linkedin_enabled'] ?? '0') === '1',
                'label' => 'LinkedIn',
                'driver' => 'linkedin-openid',
                'icon' => '<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 11.001-4.125 2.062 2.062 0 01-.001 4.125zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" fill="#0A66C2"/></svg>',
            ],
        ];

        $enabledProviders = array_filter($socialProviders, fn ($p) => $p['enabled']);
        $firstError = $errors->first();
    @endphp

    <div class="auth-card">

        {{-- Brand block — only shown when the story panel is hidden (mobile). --}}
        <div class="auth-card__brand">
            <img src="{{ $brand['logo_url'] }}" alt="" width="56" height="56" />
            <div>
                <div class="auth-card__title mb-0">{{ $brand['name'] }}</div>
                <div class="auth-card__subtitle mb-0">{{ $brand['tagline'] }}</div>
            </div>
        </div>

        <h1 class="auth-card__title">Selamat datang kembali</h1>
        <p class="auth-card__subtitle">Masuk untuk melanjutkan ke dashboard.</p>

        {{-- Accounts previously used on THIS device. Only the identifier is
             kept (in localStorage) — never a password. Populated and rendered
             by app-auth.js; hidden while empty. --}}
        <div class="auth-accounts" id="auth-accounts" hidden>
            <div class="auth-accounts__label">
                <span>Masuk sebagai</span>
                <button type="button" class="auth-accounts__clear" data-accounts-clear>Hapus semua</button>
            </div>
            <ul class="auth-accounts__list" data-accounts-list></ul>
        </div>

        {{-- Errors from a non-JS (full page) submit land here; the fetch flow
             reuses the same box. --}}
        <div class="auth-alert" id="auth-alert" role="alert" @if (! $firstError) hidden @endif>
            <i class="ki-outline ki-shield-cross fs-4 mt-1" aria-hidden="true"></i>
            <span data-alert-text>{{ $firstError }}</span>
        </div>

        <form method="POST" action="{{ route('login') }}" id="auth-signin-form" novalidate
            data-redirect="{{ route('dashboard') }}">
            @csrf

            <div class="auth-field">
                <label class="auth-label" for="auth-identifier">Email / No. WhatsApp / Username</label>
                <div class="auth-input-wrap">
                    <input type="text" name="email" id="auth-identifier" class="auth-input"
                        value="{{ old('email') }}" placeholder="nama@perusahaan.com"
                        autocomplete="username" autocapitalize="none" spellcheck="false" required
                        @if ($errors->has('email')) aria-invalid="true" aria-describedby="auth-identifier-error" @endif />
                </div>
                @error('email')
                    <span class="auth-error" id="auth-identifier-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="auth-field">
                <label class="auth-label" for="auth-password">Password</label>
                <div class="auth-input-wrap">
                    <input type="password" name="password" id="auth-password"
                        class="auth-input auth-input--password" placeholder="••••••••"
                        autocomplete="current-password" required
                        @if ($errors->has('password')) aria-invalid="true" aria-describedby="auth-password-error" @endif />

                    <button type="button" class="auth-toggle" data-auth-toggle-password="auth-password"
                        aria-label="Tampilkan password" aria-pressed="false">
                        <i class="ki-outline ki-eye fs-4" data-eye aria-hidden="true"></i>
                        <i class="ki-outline ki-eye-slash fs-4" data-eye-off style="display:none" aria-hidden="true"></i>
                    </button>
                </div>
                @error('password')
                    <span class="auth-error" id="auth-password-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="auth-options">
                <label class="auth-check" for="auth-remember">
                    <input type="checkbox" name="remember" id="auth-remember" value="1"
                        @checked(old('remember')) />
                    <span>Ingat saya</span>
                </label>
                <span class="auth-hint">Tetap masuk di perangkat ini</span>
            </div>

            <button type="submit" class="auth-submit" data-auth-submit data-label-idle="Masuk">
                <span class="auth-submit__spinner" aria-hidden="true"></span>
                <span data-label>Masuk</span>
            </button>
        </form>

        @if ($enabledProviders !== [])
            <div class="auth-separator">atau masuk dengan</div>

            <div class="auth-social">
                @foreach ($enabledProviders as $provider)
                    <a href="{{ route('social.redirect', $provider['driver']) }}" rel="nofollow">
                        {!! $provider['icon'] !!}
                        <span>{{ $provider['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endif

        <p class="auth-foot">
            Butuh akses? Hubungi administrator sistem Anda.
        </p>
    </div>
@endsection

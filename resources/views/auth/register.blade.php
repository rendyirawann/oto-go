@extends('auth.app')

@section('title', 'Daftar')

@section('content')
    <div class="auth-card">
        <div class="auth-card__brand">
            <img src="{{ $brand['logo_url'] }}" alt="" width="56" height="56" />
        </div>

        <h1 class="auth-card__title">Buat akun</h1>
        <p class="auth-card__subtitle">Lengkapi data berikut untuk mendaftar.</p>

        @if ($errors->any())
            <div class="auth-alert" role="alert">
                <i class="ki-outline ki-shield-cross fs-4 mt-1" aria-hidden="true"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="auth-field">
                <label class="auth-label" for="reg-name">Nama lengkap</label>
                <div class="auth-input-wrap">
                    <input type="text" name="name" id="reg-name" class="auth-input" value="{{ old('name') }}"
                        autocomplete="name" required autofocus />
                </div>
            </div>

            <div class="auth-field">
                <label class="auth-label" for="reg-email">Alamat email</label>
                <div class="auth-input-wrap">
                    <input type="email" name="email" id="reg-email" class="auth-input" value="{{ old('email') }}"
                        autocomplete="email" required />
                </div>
            </div>

            <div class="auth-field">
                <label class="auth-label" for="reg-password">Kata sandi</label>
                <div class="auth-input-wrap">
                    <input type="password" name="password" id="reg-password"
                        class="auth-input auth-input--password" autocomplete="new-password" required />
                    <button type="button" class="auth-toggle" data-auth-toggle-password="reg-password"
                        aria-label="Tampilkan password" aria-pressed="false">
                        <i class="ki-outline ki-eye fs-4" data-eye aria-hidden="true"></i>
                        <i class="ki-outline ki-eye-slash fs-4" data-eye-off style="display:none" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="auth-field">
                <label class="auth-label" for="reg-password-confirm">Ulangi kata sandi</label>
                <div class="auth-input-wrap">
                    <input type="password" name="password_confirmation" id="reg-password-confirm"
                        class="auth-input auth-input--password" autocomplete="new-password" required />
                </div>
            </div>

            <button type="submit" class="auth-submit">Daftar</button>
        </form>

        <p class="auth-foot">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="text-decoration-none" style="color: #a5b4fc;">Masuk di sini</a>
        </p>
    </div>
@endsection

@extends('auth.app')

@section('title', 'Reset Password')

@section('content')
    <div class="auth-card">
        <div class="auth-card__brand">
            <img src="{{ $brand['logo_url'] }}" alt="" width="56" height="56" />
        </div>

        <h1 class="auth-card__title">Atur ulang kata sandi</h1>
        <p class="auth-card__subtitle">Buat kata sandi baru untuk akun Anda.</p>

        @if ($errors->any())
            <div class="auth-alert" role="alert">
                <i class="ki-outline ki-shield-cross fs-4 mt-1" aria-hidden="true"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('password.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}" />

            <div class="auth-field">
                <label class="auth-label" for="reset-email">Alamat email</label>
                <div class="auth-input-wrap">
                    <input type="email" name="email" id="reset-email" class="auth-input"
                        value="{{ old('email', $request->email) }}" autocomplete="email" required readonly />
                </div>
            </div>

            <div class="auth-field">
                <label class="auth-label" for="reset-password">Kata sandi baru</label>
                <div class="auth-input-wrap">
                    <input type="password" name="password" id="reset-password"
                        class="auth-input auth-input--password" placeholder="••••••••"
                        autocomplete="new-password" required />
                    <button type="button" class="auth-toggle" data-auth-toggle-password="reset-password"
                        aria-label="Tampilkan password" aria-pressed="false">
                        <i class="ki-outline ki-eye fs-4" data-eye aria-hidden="true"></i>
                        <i class="ki-outline ki-eye-slash fs-4" data-eye-off style="display:none" aria-hidden="true"></i>
                    </button>
                </div>
                <span class="auth-error" style="color: rgba(248,250,252,.5);">
                    Minimal 8 karakter, mengandung huruf dan angka.
                </span>
            </div>

            <div class="auth-field">
                <label class="auth-label" for="reset-password-confirm">Ulangi kata sandi</label>
                <div class="auth-input-wrap">
                    <input type="password" name="password_confirmation" id="reset-password-confirm"
                        class="auth-input auth-input--password" placeholder="••••••••"
                        autocomplete="new-password" required />
                    <button type="button" class="auth-toggle" data-auth-toggle-password="reset-password-confirm"
                        aria-label="Tampilkan password" aria-pressed="false">
                        <i class="ki-outline ki-eye fs-4" data-eye aria-hidden="true"></i>
                        <i class="ki-outline ki-eye-slash fs-4" data-eye-off style="display:none" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="auth-submit">Simpan kata sandi baru</button>
        </form>

        <p class="auth-foot">
            <a href="{{ route('login') }}" class="text-decoration-none" style="color: #a5b4fc;">Kembali ke halaman masuk</a>
        </p>
    </div>
@endsection

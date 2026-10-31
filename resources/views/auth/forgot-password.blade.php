@extends('auth.app')

@section('title', 'Lupa Password')

@section('content')
    <div class="auth-card">
        <div class="auth-card__brand">
            <img src="{{ $brand['logo_url'] }}" alt="" width="56" height="56" />
        </div>

        <h1 class="auth-card__title">Lupa kata sandi?</h1>
        <p class="auth-card__subtitle">
            Masukkan email akun Anda, kami akan mengirimkan tautan untuk mengatur ulang kata sandi.
        </p>

        @if (session('status'))
            <div class="auth-alert" role="status"
                style="background: rgba(34,197,94,.14); border-color: rgba(74,222,128,.38); color:#bbf7d0;">
                <i class="ki-outline ki-shield-tick fs-4 mt-1" aria-hidden="true"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="auth-alert" role="alert">
                <i class="ki-outline ki-shield-cross fs-4 mt-1" aria-hidden="true"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="auth-field">
                <label class="auth-label" for="reset-email">Alamat email</label>
                <div class="auth-input-wrap">
                    <input type="email" name="email" id="reset-email" class="auth-input"
                        value="{{ old('email') }}" placeholder="nama@perusahaan.com"
                        autocomplete="email" required
                        @if ($errors->has('email')) aria-invalid="true" @endif />
                </div>
            </div>

            <button type="submit" class="auth-submit">Kirim tautan reset</button>
        </form>

        <p class="auth-foot">
            <a href="{{ route('login') }}" class="text-decoration-none" style="color: #a5b4fc;">Kembali ke halaman masuk</a>
        </p>
    </div>
@endsection

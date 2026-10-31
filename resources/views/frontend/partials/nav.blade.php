@php
    $onHome = request()->routeIs('home');
    $anchor = fn (string $id) => $onHome ? '#' . $id : route('home') . '#' . $id;
@endphp
<header class="nav" data-nav>
    <div class="wrap nav__bar">
        <a class="nav__logo" href="{{ route('home') }}" aria-label="{{ $brand['name'] }} — beranda">
            <img class="logo-light" src="{{ asset('assets/media/branding/logo-full.svg') }}" alt="{{ $brand['name'] }}" width="117" height="34" />
            <img class="logo-dark" src="{{ asset('assets/media/branding/logo-full-light.svg') }}" alt="" width="117" height="34" />
        </a>

        <nav aria-label="Navigasi utama">
            <ul class="nav__links" id="nav-links">
                <li><a href="{{ $anchor('layanan') }}">Layanan</a></li>
                <li><a href="{{ $anchor('harga') }}">Harga</a></li>
                <li><a href="{{ $anchor('usaha') }}">Untuk Usaha</a></li>
                <li><a href="{{ route('blog.index') }}" @if (request()->routeIs('blog.*')) aria-current="page" @endif>Blog</a></li>
            </ul>
        </nav>

        <div class="nav__actions">
            <button type="button" class="icon-btn theme-toggle" data-theme-toggle aria-label="Ganti tema terang/gelap">
                <svg class="i-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4.2" /><path d="M12 2.5v2.2M12 19.3v2.2M2.5 12h2.2M19.3 12h2.2M5.3 5.3l1.6 1.6M17.1 17.1l1.6 1.6M5.3 18.7l1.6-1.6M17.1 6.9l1.6-1.6" /></svg>
                <svg class="i-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 14.2A8 8 0 1 1 9.8 4a6.4 6.4 0 0 0 10.2 10.2Z" /></svg>
            </button>
            <a class="btn btn--primary btn--sm nav__portal" href="{{ route('login') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3.5" y="4" width="17" height="16" rx="3" /><path d="M8 9h8M8 13h5" /></svg>
                <span class="nav__portal-label">Portal Mitra</span>
            </a>
            <button type="button" class="icon-btn nav__menu-btn" data-nav-toggle aria-controls="nav-links" aria-expanded="false" aria-label="Buka menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10" /></svg>
            </button>
        </div>
    </div>
</header>

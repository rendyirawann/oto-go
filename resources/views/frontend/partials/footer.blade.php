@php
    $home = request()->routeIs('home') ? '' : route('home');
@endphp
<footer class="footer">
    <div class="wrap">
        <div class="footer__grid">
            <div>
                <img src="{{ asset('assets/media/branding/logo-full-light.svg') }}" alt="{{ $brand['name'] }}" width="117" height="34" loading="lazy" />
                <p>Bengkel, doorsmeer, dan toko sparepart di Kota Medan dalam satu aplikasi. Booking, antar jemput, dan layanan di rumah.</p>
            </div>
            <div>
                <h2>Layanan</h2>
                <ul>
                    <li><a href="{{ $home }}#layanan">Bengkel</a></li>
                    <li><a href="{{ $home }}#layanan">Doorsmeer</a></li>
                    <li><a href="{{ $home }}#layanan">Sparepart</a></li>
                    <li><a href="{{ $home }}#harga">Estimasi harga</a></li>
                </ul>
            </div>
            <div>
                <h2>OtoGo</h2>
                <ul>
                    <li><a href="{{ route('blog.index') }}">Blog</a></li>
                    <li><a href="{{ $home }}#usaha">Untuk usaha</a></li>
                    <li><a href="{{ route('login') }}">Portal Mitra</a></li>
                    <li><a href="{{ url('/api/documentation') }}">Dokumentasi API</a></li>
                </ul>
            </div>
            <div>
                <h2>Kontak</h2>
                <ul>
                    <li><a href="mailto:halo@otogo.id">halo@otogo.id</a></li>
                    <li>Kota Medan, Sumatera Utara</li>
                </ul>
            </div>
        </div>

        <div class="lane" aria-hidden="true"></div>

        <div class="footer__bottom">
            <span>&copy; {{ now()->year }} Core Technology Indonesia. Seluruh hak dilindungi.</span>
            <span class="mono">Dibuat di Medan · WIB</span>
        </div>
    </div>
</footer>

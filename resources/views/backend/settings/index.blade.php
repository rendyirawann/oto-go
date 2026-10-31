@extends('backend.layout.app')

@section('title', 'Pengaturan')

@section('content')
    @php
        $value = fn (string $key, string $default = '') => old($key, $settings[$key] ?? $default);
        $enabled = fn (string $key) => ($settings[$key] ?? '0') === '1';
        $robotsOptions = \App\Http\Controllers\Backend\Settings\SettingController::ROBOTS;
    @endphp

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-6">
        <div class="min-w-0">
            <h1 class="fs-2 fw-bold text-gray-900 mb-1">Pengaturan Aplikasi</h1>
            <p class="text-muted fs-7 mb-0">Identitas, SEO, tampilan, dan integrasi login.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-start p-5 mb-6" role="alert">
            <i class="ki-outline ki-shield-cross fs-2hx text-danger me-4"></i>
            <div>
                <h4 class="mb-2 text-danger">Periksa kembali isian Anda</h4>
                <ul class="mb-0 ps-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" id="settings-form">
        @csrf

        <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-6 fw-semibold mb-7 flex-nowrap overflow-auto"
            role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link text-active-primary pb-4 active text-nowrap" data-bs-toggle="tab" href="#tab-identity"
                    role="tab" aria-selected="true">
                    <i class="ki-outline ki-abstract-26 fs-5 me-2"></i>Identitas
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link text-active-primary pb-4 text-nowrap" data-bs-toggle="tab" href="#tab-seo" role="tab"
                    aria-selected="false">
                    <i class="ki-outline ki-search-list fs-5 me-2"></i>SEO &amp; Meta
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link text-active-primary pb-4 text-nowrap" data-bs-toggle="tab" href="#tab-appearance"
                    role="tab" aria-selected="false">
                    <i class="ki-outline ki-design-1 fs-5 me-2"></i>Tampilan
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link text-active-primary pb-4 text-nowrap" data-bs-toggle="tab" href="#tab-social" role="tab"
                    aria-selected="false">
                    <i class="ki-outline ki-fingerprint-scanning fs-5 me-2"></i>Social Login
                </a>
            </li>
        </ul>

        <div class="tab-content">

            {{-- ============================ IDENTITAS ============================ --}}
            <div class="tab-pane fade show active" id="tab-identity" role="tabpanel">
                <div class="row g-5">

                    <div class="col-12 col-xl-6">
                        <div class="card card-flush shadow-sm h-100">
                            <div class="card-header pt-6 border-0">
                                <h3 class="card-title fw-bold fs-5">Nama &amp; Deskripsi</h3>
                            </div>
                            <div class="card-body pt-2">
                                <div class="mb-6">
                                    <label class="form-label fw-semibold" for="site_name">Nama Aplikasi <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-solid" id="site_name"
                                        name="site_name" value="{{ $value('site_name', 'StarterTemp') }}" required
                                        maxlength="60" />
                                    <div class="form-text">Dipakai di judul halaman, navbar, footer, dan meta tag.</div>
                                </div>

                                <div class="mb-6">
                                    <label class="form-label fw-semibold" for="site_short_name">Nama Pendek</label>
                                    <input type="text" class="form-control form-control-solid" id="site_short_name"
                                        name="site_short_name" value="{{ $value('site_short_name') }}" maxlength="20" />
                                    <div class="form-text">Untuk ikon home screen (PWA). Maksimal 20 karakter.</div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label fw-semibold" for="site_tagline">Tagline</label>
                                    <input type="text" class="form-control form-control-solid" id="site_tagline"
                                        name="site_tagline" value="{{ $value('site_tagline') }}" maxlength="80" />
                                    <div class="form-text">Tampil kecil di bawah nama aplikasi pada navbar.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="card card-flush shadow-sm h-100">
                            <div class="card-header pt-6 border-0 flex-wrap gap-2">
                                <h3 class="card-title fw-bold fs-5">Logo &amp; Favicon</h3>
                                <div class="card-toolbar">
                                    <button type="button" class="btn btn-sm btn-light-primary" id="regen-branding">
                                        <i class="ki-outline ki-magic-wand fs-5 me-1"></i>Generate dari nama
                                    </button>
                                </div>
                            </div>
                            <div class="card-body pt-2">
                                <div class="d-flex align-items-center gap-4 mb-6 p-4 bg-light rounded">
                                    <img src="{{ $brand['logo_url'] }}" alt="Logo saat ini" id="logo-preview"
                                        style="width: 64px; height: 64px; object-fit: contain; border-radius: 14px;" />
                                    <div class="min-w-0">
                                        <div class="fw-bold fs-6 text-gray-900 text-truncate">{{ $brand['name'] }}</div>
                                        <div class="fs-8 text-muted">Pratinjau logo aktif</div>
                                    </div>
                                    <img src="{{ $brand['favicon_png_url'] }}" alt="Favicon saat ini"
                                        style="width: 32px; height: 32px; object-fit: contain;" class="ms-auto" />
                                </div>

                                <div class="mb-5">
                                    <label class="form-label fw-semibold" for="site_logo">Unggah Logo</label>
                                    <input type="file" class="form-control form-control-solid" id="site_logo"
                                        name="site_logo" accept=".png,.jpg,.jpeg,.svg,.webp" />
                                    <div class="form-text">PNG, JPG, SVG atau WebP. Maks 2 MB.</div>
                                </div>

                                <div class="mb-5">
                                    <label class="form-label fw-semibold" for="site_favicon">Unggah Favicon</label>
                                    <input type="file" class="form-control form-control-solid" id="site_favicon"
                                        name="site_favicon" accept=".png,.ico,.svg" />
                                    <div class="form-text">ICO, PNG atau SVG persegi. Maks 512 KB.</div>
                                </div>

                                <div>
                                    <label class="form-label fw-semibold" for="site_og_image">Unggah OG Image</label>
                                    <input type="file" class="form-control form-control-solid" id="site_og_image"
                                        name="site_og_image" accept=".png,.jpg,.jpeg,.webp" />
                                    <div class="form-text">Gambar pratinjau saat link dibagikan. Ideal 1200 × 630 px.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================== SEO =============================== --}}
            <div class="tab-pane fade" id="tab-seo" role="tabpanel">
                <div class="row g-5">

                    <div class="col-12 col-xl-7">
                        <div class="card card-flush shadow-sm h-100">
                            <div class="card-header pt-6 border-0">
                                <h3 class="card-title fw-bold fs-5">Meta Tag</h3>
                            </div>
                            <div class="card-body pt-2">
                                <div class="mb-6">
                                    <label class="form-label fw-semibold" for="site_description">Meta Description</label>
                                    <textarea class="form-control form-control-solid" id="site_description" name="site_description"
                                        rows="3" maxlength="300"
                                        data-counter="site_description">{{ $value('site_description') }}</textarea>
                                    <div class="form-text">
                                        Ideal 120–160 karakter.
                                        <span class="fw-semibold" data-counter-for="site_description">0</span>/300
                                    </div>
                                </div>

                                <div class="mb-6">
                                    <label class="form-label fw-semibold" for="site_keywords">Meta Keywords</label>
                                    <input type="text" class="form-control form-control-solid" id="site_keywords"
                                        name="site_keywords" value="{{ $value('site_keywords') }}" maxlength="255" />
                                    <div class="form-text">Pisahkan dengan koma.</div>
                                </div>

                                <div class="row g-5">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold" for="site_author">Author</label>
                                        <input type="text" class="form-control form-control-solid" id="site_author"
                                            name="site_author" value="{{ $value('site_author') }}" maxlength="80" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold" for="seo_robots">Robots</label>
                                        <select class="form-select form-select-solid" id="seo_robots" name="seo_robots">
                                            @foreach ($robotsOptions as $option)
                                                <option value="{{ $option }}"
                                                    @selected($value('seo_robots', 'noindex, nofollow') === $option)>
                                                    {{ $option }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">Panel internal sebaiknya <code>noindex, nofollow</code>.</div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold" for="seo_twitter_handle">Twitter / X Handle</label>
                                        <input type="text" class="form-control form-control-solid" id="seo_twitter_handle"
                                            name="seo_twitter_handle" value="{{ $value('seo_twitter_handle') }}"
                                            placeholder="@namaakun" maxlength="40" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold" for="seo_google_verification">Google Site Verification</label>
                                        <input type="text" class="form-control form-control-solid"
                                            id="seo_google_verification" name="seo_google_verification"
                                            value="{{ $value('seo_google_verification') }}" maxlength="120" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-5">
                        <div class="card card-flush shadow-sm h-100">
                            <div class="card-header pt-6 border-0">
                                <h3 class="card-title fw-bold fs-5">Pratinjau Hasil Pencarian</h3>
                            </div>
                            <div class="card-body pt-2">
                                <div class="border border-gray-200 rounded p-4 mb-6">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <img src="{{ $brand['favicon_png_url'] }}" alt="" width="16" height="16" />
                                        <span class="fs-8 text-muted text-truncate">{{ url('/') }}</span>
                                    </div>
                                    <div class="fs-5 fw-semibold text-primary mb-1" data-preview="title">
                                        {{ $brand['name'] }}
                                    </div>
                                    <div class="fs-7 text-gray-600" data-preview="description">
                                        {{ $brand['description'] }}
                                    </div>
                                </div>

                                <div class="fs-8 text-uppercase text-muted fw-semibold mb-2">Pratinjau Social Share</div>
                                <img src="{{ $brand['og_image_url'] }}" alt="Open Graph preview"
                                    class="w-100 rounded border border-gray-200" style="aspect-ratio: 1200/630; object-fit: cover;" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================ TAMPILAN ============================ --}}
            <div class="tab-pane fade" id="tab-appearance" role="tabpanel">
                <div class="row g-5">

                    <div class="col-12 col-xl-6">
                        <div class="card card-flush shadow-sm h-100">
                            <div class="card-header pt-6 border-0">
                                <h3 class="card-title fw-bold fs-5">Tipografi &amp; Warna</h3>
                            </div>
                            <div class="card-body pt-2">
                                <div class="mb-6">
                                    <label class="form-label fw-semibold" for="site_font">Font Global</label>
                                    <select class="form-select form-select-solid" id="site_font" name="site_font">
                                        @foreach ($fonts as $font)
                                            <option value="{{ $font }}" @selected($value('site_font', 'Plus Jakarta Sans') === $font)>
                                                {{ $font }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-6">
                                    <label class="form-label fw-semibold" for="site_theme_color">Theme Color</label>
                                    <div class="d-flex align-items-center gap-3">
                                        <input type="color" class="form-control form-control-color"
                                            id="site_theme_color_picker" value="{{ $value('site_theme_color', '#4f46e5') }}"
                                            aria-label="Pilih theme color" />
                                        <input type="text" class="form-control form-control-solid" id="site_theme_color"
                                            name="site_theme_color" value="{{ $value('site_theme_color', '#4f46e5') }}"
                                            pattern="^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$" />
                                    </div>
                                    <div class="form-text">Warna bar browser di perangkat mobile.</div>
                                </div>

                                <div>
                                    <label class="form-label fw-semibold">Pratinjau Font</label>
                                    <div class="border rounded p-4 bg-light" id="font-preview">
                                        <div class="fs-3 fw-bold mb-1">The quick brown fox jumps over the lazy dog</div>
                                        <div class="fs-7 text-muted">ABCDEFGHIJKLM abcdefghijklm 0123456789</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-xl-6">
                        <div class="card card-flush shadow-sm h-100">
                            <div class="card-header pt-6 border-0">
                                <h3 class="card-title fw-bold fs-5">Akses &amp; Footer</h3>
                            </div>
                            <div class="card-body pt-2">
                                <div class="d-flex flex-stack gap-3 mb-6">
                                    <div>
                                        <span class="fs-6 fw-bold text-gray-900 d-block">Mode Maintenance</span>
                                        <span class="fs-8 text-muted">Menutup akses untuk semua role kecuali Superadmin.</span>
                                    </div>
                                    <div class="form-check form-switch form-check-custom form-check-solid">
                                        <input class="form-check-input h-25px w-45px" type="checkbox" role="switch"
                                            name="maintenance_mode" id="maintenance_mode"
                                            @checked($enabled('maintenance_mode')) />
                                        <label class="visually-hidden" for="maintenance_mode">Mode maintenance</label>
                                    </div>
                                </div>

                                <div class="d-flex flex-stack gap-3 mb-6">
                                    <div>
                                        <span class="fs-6 fw-bold text-gray-900 d-block">Pendaftaran Mandiri</span>
                                        <span class="fs-8 text-muted">Jika mati, akun hanya bisa dibuat oleh administrator.</span>
                                    </div>
                                    <div class="form-check form-switch form-check-custom form-check-solid">
                                        <input class="form-check-input h-25px w-45px" type="checkbox" role="switch"
                                            name="allow_registration" id="allow_registration"
                                            @checked($enabled('allow_registration')) />
                                        <label class="visually-hidden" for="allow_registration">Izinkan pendaftaran mandiri</label>
                                    </div>
                                </div>

                                <div class="separator my-6"></div>

                                <div class="mb-5">
                                    <label class="form-label fw-semibold" for="footer_owner">Pemilik Hak Cipta</label>
                                    <input type="text" class="form-control form-control-solid" id="footer_owner"
                                        name="footer_owner" value="{{ $value('footer_owner') }}" maxlength="80" />
                                    <div class="form-text">Tahun copyright terisi otomatis mengikuti tahun berjalan.</div>
                                </div>

                                <div class="mb-5">
                                    <label class="form-label fw-semibold" for="footer_github">URL GitHub</label>
                                    <input type="url" class="form-control form-control-solid" id="footer_github"
                                        name="footer_github" value="{{ $value('footer_github') }}"
                                        placeholder="https://github.com/username" />
                                </div>

                                <div>
                                    <label class="form-label fw-semibold" for="footer_linkedin">URL LinkedIn</label>
                                    <input type="url" class="form-control form-control-solid" id="footer_linkedin"
                                        name="footer_linkedin" value="{{ $value('footer_linkedin') }}"
                                        placeholder="https://linkedin.com/in/username" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========================== SOCIAL LOGIN ========================== --}}
            <div class="tab-pane fade" id="tab-social" role="tabpanel">
                <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-5 mb-6">
                    <i class="ki-outline ki-information-5 fs-2hx text-primary me-4"></i>
                    <div class="fw-semibold">
                        <h4 class="text-gray-900 fw-bold mb-1">Konfigurasi OAuth</h4>
                        <div class="fs-7 text-gray-700">
                            Kosongkan kolom Client Secret jika tidak ingin mengubah nilai yang tersimpan.
                            Provider yang tidak aktif tidak akan muncul di halaman login.
                        </div>
                    </div>
                </div>

                <div class="row g-5">
                    @foreach (['google' => 'Google', 'facebook' => 'Facebook', 'github' => 'GitHub', 'linkedin' => 'LinkedIn'] as $key => $label)
                        <div class="col-12 col-xl-6">
                            <div class="card card-flush shadow-sm h-100">
                                <div class="card-header pt-6 border-0 flex-wrap gap-2">
                                    <h3 class="card-title fw-bold fs-5">{{ $label }}</h3>
                                    <div class="card-toolbar">
                                        <div class="form-check form-switch form-check-custom form-check-solid">
                                            <input class="form-check-input h-25px w-45px" type="checkbox" role="switch"
                                                name="social_{{ $key }}_enabled" id="social_{{ $key }}_enabled"
                                                @checked($enabled("social_{$key}_enabled")) />
                                            <label class="visually-hidden" for="social_{{ $key }}_enabled">Aktifkan {{ $label }}</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body pt-2">
                                    <div class="mb-5">
                                        <label class="form-label fw-semibold" for="social_{{ $key }}_client_id">Client ID</label>
                                        <input type="text" class="form-control form-control-solid"
                                            id="social_{{ $key }}_client_id" name="social_{{ $key }}_client_id"
                                            value="{{ $value("social_{$key}_client_id") }}" autocomplete="off" />
                                    </div>
                                    <div>
                                        <label class="form-label fw-semibold" for="social_{{ $key }}_client_secret">Client Secret</label>
                                        <input type="password" class="form-control form-control-solid"
                                            id="social_{{ $key }}_client_secret" name="social_{{ $key }}_client_secret"
                                            placeholder="{{ ($settings["social_{$key}_client_secret"] ?? '') !== '' ? 'Tersimpan — kosongkan untuk mempertahankan' : 'Belum diatur' }}"
                                            autocomplete="new-password" />
                                    </div>
                                    <div class="form-text mt-4">
                                        Callback URL:
                                        <code class="text-break">{{ route('social.callback', $key === 'linkedin' ? 'linkedin-openid' : $key) }}</code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-3 mt-8">
            <a href="{{ route('dashboard') }}" class="btn btn-light">Batal</a>
            <button type="submit" class="btn btn-primary">
                <i class="ki-outline ki-check fs-5 me-1"></i>Simpan Pengaturan
            </button>
        </div>
    </form>

    {{-- Kept separate so regenerating artwork does not submit the settings form. --}}
    <form method="POST" action="{{ route('settings.branding') }}" id="branding-form" class="d-none">
        @csrf
    </form>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // --- Live font preview ---
                var fontSelect = document.getElementById('site_font');
                var fontPreview = document.getElementById('font-preview');

                function loadFont(name) {
                    var id = 'font-preview-link';
                    var link = document.getElementById(id) || document.createElement('link');
                    link.id = id;
                    link.rel = 'stylesheet';
                    link.href = 'https://fonts.googleapis.com/css2?family=' +
                        encodeURIComponent(name).replace(/%20/g, '+') + ':wght@400;700&display=swap';
                    document.head.appendChild(link);
                    fontPreview.style.fontFamily = "'" + name + "', sans-serif";
                }

                fontSelect.addEventListener('change', function () {
                    loadFont(this.value);
                });
                loadFont(fontSelect.value);

                // --- Theme colour picker <-> text field ---
                var picker = document.getElementById('site_theme_color_picker');
                var hex = document.getElementById('site_theme_color');
                picker.addEventListener('input', function () { hex.value = this.value; });
                hex.addEventListener('input', function () {
                    if (/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(this.value)) {
                        picker.value = this.value;
                    }
                });

                // --- Logo preview from the chosen file ---
                var logoInput = document.getElementById('site_logo');
                var logoPreview = document.getElementById('logo-preview');
                logoInput.addEventListener('change', function () {
                    var file = this.files && this.files[0];
                    if (file) {
                        logoPreview.src = URL.createObjectURL(file);
                    }
                });

                // --- SERP preview ---
                var nameInput = document.getElementById('site_name');
                var descInput = document.getElementById('site_description');
                var titlePreview = document.querySelector('[data-preview="title"]');
                var descPreview = document.querySelector('[data-preview="description"]');

                nameInput.addEventListener('input', function () {
                    titlePreview.textContent = this.value || 'Nama Aplikasi';
                });

                // --- Character counter ---
                var counter = document.querySelector('[data-counter-for="site_description"]');
                function updateCounter() {
                    counter.textContent = descInput.value.length;
                    descPreview.textContent = descInput.value || 'Deskripsi aplikasi Anda akan tampil di sini.';
                }
                descInput.addEventListener('input', updateCounter);
                updateCounter();

                // --- Regenerate branding ---
                document.getElementById('regen-branding').addEventListener('click', function () {
                    var run = function () { document.getElementById('branding-form').submit(); };

                    if (!window.Swal) {
                        if (window.confirm('Buat ulang logo, favicon dan OG image dari nama aplikasi? Perubahan nama yang belum disimpan tidak ikut terpakai.')) {
                            run();
                        }
                        return;
                    }

                    Swal.fire({
                        title: 'Generate ulang branding?',
                        text: 'Logo, favicon dan OG image akan dibuat ulang dari nama aplikasi yang tersimpan. Simpan dulu perubahan nama bila ada.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, generate',
                        cancelButtonText: 'Batal',
                        customClass: { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-light' },
                        buttonsStyling: false
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            run();
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection

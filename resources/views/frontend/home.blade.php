@extends('frontend.layout')

@section('meta_description', 'OtoGo: booking bengkel, doorsmeer, dan sparepart di Kota Medan. Datang ke outlet, minta antar jemput, atau dikerjakan di rumah. Cek estimasi harga sebelum berangkat.')

@php
    $rp = fn (int $n) => 'Rp ' . number_format($n, 0, ',', '.');

    // Ticket mock numbers come from the real template, not made up.
    $svc = collect($template['items'] ?? [])->firstWhere('service', 'Servis berkala ringan mobil');
    $svcPrice = (int) ($svc['prices']['car_mpv'] ?? 200000);
    $pickupCar = collect($template['logistics'] ?? [])->first(fn ($l) => $l['mode'] === 'pickup_delivery' && $l['vehicle_type'] === 'mobil');
    $distance = 4.2;
    $fee = $pickupCar
        ? (int) $pickupCar['base_fee'] + (int) max(0, ceil($distance - $pickupCar['included_km'])) * (int) $pickupCar['per_km_fee']
        : 50000;

    $logistics = collect($template['logistics'] ?? [])->groupBy('mode');
    $feeLine = function ($mode, $type) use ($logistics, $rp) {
        $row = collect($logistics->get($mode, []))->firstWhere('vehicle_type', $type);
        if (! $row) {
            return '-';
        }
        return $rp((int) $row['base_fee']) . ' (' . $row['included_km'] . ' km pertama) + ' . $rp((int) $row['per_km_fee']) . '/km · maks ' . $row['max_radius_km'] . ' km';
    };

    $defaultRows = $estimator['prices'][$estimator['default']] ?? [];
    $motorCats = array_values(array_filter($estimator['categories'], fn ($c) => $c['type'] === 'motor'));
    $carCats = array_values(array_filter($estimator['categories'], fn ($c) => $c['type'] === 'mobil'));
    $shortName = fn (string $name) => trim(preg_replace('/\s*\(.*\)$/u', '', $name));
@endphp

@php
    $jsonLd = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => url('/') . '#organization',
                    'name' => $brand['name'],
                    'url' => url('/'),
                    'logo' => asset('assets/media/branding/icon-512.png'),
                    'email' => 'halo@otogo.id',
                    'areaServed' => ['@type' => 'City', 'name' => 'Medan'],
                    'parentOrganization' => ['@type' => 'Organization', 'name' => 'Core Technology Indonesia'],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => url('/') . '#website',
                    'url' => url('/'),
                    'name' => $brand['name'],
                    'inLanguage' => 'id-ID',
                    'publisher' => ['@id' => url('/') . '#organization'],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => route('blog.index') . '?q={search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
    ];
@endphp

@push('head')
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')

    {{-- ============================================================ HERO --}}
    <section class="hero">
        <div class="wrap hero__grid">
            <div>
                <p class="eyebrow" data-reveal>Mulai di Kota Medan · 2026</p>
                <h1 class="display hero__title" data-reveal style="--d: 80ms">
                    <span class="sr-only">Pesan servis, cuci, dan sparepart kendaraan tanpa antre.</span>
                    <span aria-hidden="true">
                        <span class="line">Pesan</span>
                        <span class="line">
                            <span class="rotator" data-rotator>
                                <span>servis motor</span>
                                <span>cuci salju</span>
                                <span>ganti aki</span>
                                <span>spooring</span>
                                <span>servis AC</span>
                                <span>ganti oli</span>
                            </span>
                        </span>
                        <span class="line">tanpa antre.</span>
                    </span>
                </h1>
                <p class="lede" data-reveal style="--d: 160ms">
                    OtoGo menghubungkan pemilik kendaraan di Medan dengan bengkel, doorsmeer, dan toko sparepart.
                    Pilih jadwal, bayar online, atau minta kendaraan dijemput &mdash; dari satu aplikasi.
                </p>
                <div class="hero__ctas" data-reveal style="--d: 240ms">
                    <a class="btn btn--primary" href="#harga">
                        Cek estimasi harga
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                    </a>
                    <a class="btn btn--ghost" href="#usaha">Daftarkan usahamu</a>
                </div>
                <ul class="facts" data-reveal style="--d: 320ms">
                    <li><strong data-count="{{ count($areas) }}">{{ count($areas) }}</strong> kecamatan</li>
                    <li><strong data-count="{{ $categoryCount }}">{{ $categoryCount }}</strong> kategori kendaraan</li>
                    <li><strong data-count="3">3</strong> cara layanan</li>
                </ul>
            </div>

            <div class="ticket-stack" data-reveal style="--d: 200ms">
                <article class="ticket" aria-label="Contoh tampilan pesanan">
                    <div class="ticket__top">
                        <span class="ticket__label">Contoh pesanan</span>
                        <span class="status-pill"><i aria-hidden="true"></i>Dijemput</span>
                    </div>

                    <div class="ticket__plate">
                        <span class="plate" aria-label="Pelat nomor BK 1488 AFD"><b>BK 1488 AFD</b><small>10 · 29</small></span>
                        <span class="ticket__vehicle"><strong>MPV</strong>Avanza, Xenia, Ertiga, dll.</span>
                    </div>

                    <div class="route" aria-hidden="true">
                        <svg viewBox="0 0 260 70" fill="none">
                            <path class="r-base" d="M 18 34 C 90 4, 170 64, 242 30" stroke-width="6" stroke-linecap="round" />
                            <path class="r-path" d="M 18 34 C 90 4, 170 64, 242 30" stroke-width="2.5" stroke-linecap="round" />
                            <circle class="r-node r-node--orange" cx="18" cy="34" r="7" stroke-width="2" />
                            <circle class="r-node" cx="242" cy="30" r="7" stroke-width="2" />
                            <circle class="r-car" r="5" fill="#111418" />
                            <text x="6" y="62">RUMAH</text>
                            <text x="214" y="62">OUTLET</text>
                        </svg>
                    </div>

                    <dl class="ticket__rows">
                        <div><dt>Layanan</dt><dd>Servis berkala ringan</dd></div>
                        <div><dt>Cara</dt><dd>Antar jemput · {{ str_replace('.', ',', (string) $distance) }} km</dd></div>
                        <div><dt>Jadwal</dt><dd>Sabtu, 09.00 WIB</dd></div>
                        <div><dt>Jasa</dt><dd class="mono">{{ $rp($svcPrice) }}</dd></div>
                        <div><dt>Antar jemput</dt><dd class="mono">{{ $rp($fee) }}</dd></div>
                    </dl>
                    <div class="ticket__total">
                        <span>Total estimasi</span>
                        <strong>{{ $rp($svcPrice + $fee) }}</strong>
                    </div>
                    <p class="ticket__note">QRIS · Virtual Account · e-wallet</p>
                </article>
            </div>
        </div>
    </section>

    {{-- ========================================================= MARQUEE --}}
    <div class="marquee" aria-label="Wilayah layanan awal">
        <div class="marquee__track">
            <ul class="marquee__list">
                @foreach ($areas as $area)
                    <li>{{ $area }}</li>
                @endforeach
            </ul>
            <ul class="marquee__list" aria-hidden="true">
                @foreach ($areas as $area)
                    <li>{{ $area }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- ========================================================= MODULES --}}
    <section class="section" id="layanan">
        <div class="wrap">
            <div class="section__head">
                <div>
                    <p class="eyebrow" data-reveal>Layanan</p>
                    <h2 class="display h-lg" data-reveal style="--d: 60ms">Tiga kebutuhan kendaraan, satu tempat.</h2>
                </div>
                <p class="lede" data-reveal style="--d: 120ms">Setiap usaha di OtoGo membuka modul yang memang mereka layani. Harga mengikuti kategori kendaraanmu, jadi matic dan SUV tidak dipukul rata.</p>
            </div>

            <div class="cards-3">
                <article class="card" data-reveal>
                    <div class="card__top">
                        <span class="card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.2l-5.8 5.8a1.8 1.8 0 0 0 2.5 2.5l5.8-5.8a4 4 0 0 0 5.2-5.4l-2.4 2.4-2.2-.6-.6-2.2 2.9-1.9Z" /></svg>
                        </span>
                        <span class="card__num">01</span>
                    </div>
                    <h3>Bengkel</h3>
                    <p>Servis berkala, rem, kaki-kaki, kelistrikan, sampai AC mobil.</p>
                    <ul class="ticks">
                        <li>Mekanik ditugaskan, status bisa dipantau</li>
                        <li>Pekerjaan tambahan wajib kamu setujui dulu</li>
                        <li>Garansi servis tercatat di riwayat kendaraan</li>
                    </ul>
                </article>

                <article class="card" data-reveal style="--d: 90ms">
                    <div class="card__top">
                        <span class="card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round"><circle cx="8.5" cy="14.5" r="4.5" /><circle cx="15.8" cy="8.6" r="3.4" /><circle cx="17.4" cy="16.4" r="2.4" /><path d="M5 5.5h3M6.5 4v3" /></svg>
                        </span>
                        <span class="card__num">02</span>
                    </div>
                    <h3>Doorsmeer</h3>
                    <p>Cuci salju, vakum interior, cuci kolong, wax, sampai detailing.</p>
                    <ul class="ticks">
                        <li>Antrean dan slot booking yang jelas</li>
                        <li>Add-on bisa ditambah saat memesan</li>
                        <li>Cuci di rumah bila air &amp; listrik tersedia</li>
                    </ul>
                </article>

                <article class="card" data-reveal style="--d: 180ms">
                    <div class="card__top">
                        <span class="card__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linejoin="round"><path d="M12 3.2 19.6 7.6v8.8L12 20.8l-7.6-4.4V7.6Z" /><circle cx="12" cy="12" r="3.1" /></svg>
                        </span>
                        <span class="card__num">03</span>
                    </div>
                    <h3>Sparepart</h3>
                    <p>Oli, aki, kampas rem, filter &mdash; dicek cocok atau tidak dengan kendaraanmu.</p>
                    <ul class="ticks">
                        <li>Stok per outlet, tidak menebak-nebak</li>
                        <li>Ambil di toko atau diantar kurir toko</li>
                        <li>Beli sekaligus pasang di bengkel</li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    {{-- =========================================================== MODES --}}
    <section class="section section--paper2" id="cara">
        <div class="wrap">
            <div class="section__head">
                <div>
                    <p class="eyebrow" data-reveal>Cara layanan</p>
                    <h2 class="display h-lg" data-reveal style="--d: 60ms">Kamu yang pilih caranya.</h2>
                </div>
                <p class="lede" data-reveal style="--d: 120ms">Tiap outlet menentukan cara layanan yang mereka buka. Jarak dihitung dari titik alamatmu, dan alamat di luar radius layanan langsung ditolak sebelum kamu bayar.</p>
            </div>

            <div class="modes">
                <div class="modes__visual" data-reveal="fade">
                    <div class="map" data-map data-mode="on_site">
                        <svg viewBox="0 0 500 400" fill="none" aria-hidden="true">
                            @for ($x = 40; $x < 500; $x += 40)
                                <line class="grid-line" x1="{{ $x }}" y1="0" x2="{{ $x }}" y2="400" />
                            @endfor
                            @for ($y = 40; $y < 400; $y += 40)
                                <line class="grid-line" x1="0" y1="{{ $y }}" x2="500" y2="{{ $y }}" />
                            @endfor

                            <path class="street" d="M 20 200 H 480" />
                            <path class="street" d="M 150 30 V 330" />
                            <path class="street" d="M 370 30 V 330" />
                            <path class="street" d="M 200 90 H 480" />
                            <path class="street" d="M 20 250 H 120" />
                            <path class="street-dash" d="M 20 200 H 480" />
                            <path class="street-dash" d="M 150 30 V 330" />
                            <path class="street-dash" d="M 370 30 V 330" />

                            <path class="trip trip-back" d="M 162 284 V 214 Q 162 212 166 212 H 354 Q 358 212 358 208 V 128" />
                            <path class="trip" data-trip d="M 370 126 V 190 Q 370 200 360 200 H 160 Q 150 200 150 210 V 282" />

                            <g class="node">
                                <rect x="322" y="72" width="96" height="40" rx="10" />
                                <text x="370" y="97" text-anchor="middle">OUTLET</text>
                            </g>
                            <g class="node node--home">
                                <rect x="104" y="288" width="92" height="40" rx="10" />
                                <text x="150" y="313" text-anchor="middle">RUMAH</text>
                            </g>
                        </svg>
                        <div class="map__caption">
                            <strong data-map-caption>Kamu yang datang ke outlet</strong>
                            <span data-map-sub>tanpa biaya tambahan</span>
                        </div>
                    </div>
                </div>

                <div>
                    <article class="mode" data-mode-step="on_site" data-caption="Kamu yang datang ke outlet" data-sub="tanpa biaya tambahan">
                        <div class="mode__head"><span>01</span><h3>Datang ke outlet</h3></div>
                        <p>Pesan slot dulu, datang di jam yang kamu pilih, dan kendaraan langsung dikerjakan. Walk-in tetap bisa bila outlet membukanya.</p>
                        <dl class="fee"><dt>Biaya</dt><dd>Tanpa biaya tambahan</dd></dl>
                    </article>

                    <article class="mode" data-mode-step="pickup_delivery" data-caption="Dijemput, dikerjakan, diantar kembali" data-sub="pergi-pulang sudah termasuk">
                        <div class="mode__head"><span>02</span><h3>Antar jemput</h3></div>
                        <p>Kendaraan dijemput dari alamatmu, dikerjakan di outlet, lalu diantar kembali. Serah terima dicatat dengan foto empat sisi, odometer, dan level bensin.</p>
                        <dl class="fee">
                            <dt>Motor</dt><dd>{{ $feeLine('pickup_delivery', 'motor') }}</dd>
                            <dt>Mobil</dt><dd>{{ $feeLine('pickup_delivery', 'mobil') }}</dd>
                        </dl>
                    </article>

                    <article class="mode" data-mode-step="home_service" data-caption="Petugas datang ke rumahmu" data-sub="dikerjakan di alamatmu">
                        <div class="mode__head"><span>03</span><h3>Layanan di rumah</h3></div>
                        <p>Petugas datang dan mengerjakan di alamatmu. Untuk cuci di rumah, kamu cukup memastikan ada air, listrik, dan tempat parkir yang cukup.</p>
                        <dl class="fee">
                            <dt>Motor</dt><dd>{{ $feeLine('home_service', 'motor') }}</dd>
                            <dt>Mobil</dt><dd>{{ $feeLine('home_service', 'mobil') }}</dd>
                        </dl>
                    </article>
                </div>
            </div>
        </div>
    </section>

    {{-- ======================================================= ESTIMATOR --}}
    <section class="section" id="harga">
        <div class="wrap">
            <div class="section__head">
                <div>
                    <p class="eyebrow" data-reveal>Estimasi harga</p>
                    <h2 class="display h-lg" data-reveal style="--d: 60ms">Cek kisaran harga sebelum berangkat.</h2>
                </div>
                <p class="lede" data-reveal style="--d: 120ms">Pilih kategori kendaraanmu. Angka di bawah adalah kisaran pasar Medan yang dipakai sebagai template awal; tiap usaha bisa menyesuaikan harganya sendiri.</p>
            </div>

            <div class="estimator" data-estimator data-reveal>
                <div class="chips-group">
                    <span class="chips-group__label">Motor</span>
                    <div class="chips" role="group" aria-label="Kategori motor">
                        @foreach ($motorCats as $cat)
                            <button type="button" class="chip" data-cat="{{ $cat['code'] }}" aria-pressed="{{ $cat['code'] === $estimator['default'] ? 'true' : 'false' }}">{{ $shortName($cat['name']) }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="chips-group">
                    <span class="chips-group__label">Mobil</span>
                    <div class="chips" role="group" aria-label="Kategori mobil">
                        @foreach ($carCats as $cat)
                            <button type="button" class="chip" data-cat="{{ $cat['code'] }}" aria-pressed="{{ $cat['code'] === $estimator['default'] ? 'true' : 'false' }}">{{ $shortName($cat['name']) }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="tabs" role="tablist" aria-label="Jenis layanan">
                    <button type="button" class="tab" role="tab" data-module="all" aria-selected="true">Semua</button>
                    @foreach ($estimator['modules'] as $code => $label)
                        <button type="button" class="tab" role="tab" data-module="{{ $code }}" aria-selected="false">{{ $label }}</button>
                    @endforeach
                </div>

                <p class="muted" style="margin: 6px 0 0; font-size: 14px;">
                    <strong data-estimator-title style="color: var(--og-ink)">{{ collect($estimator['categories'])->firstWhere('code', $estimator['default'])['name'] ?? '' }}</strong>
                    · <span data-estimator-count>{{ count($defaultRows) }} layanan</span>
                </p>

                <div style="overflow-x: auto;">
                    <table class="price-table">
                        <thead>
                            <tr>
                                <th scope="col">Layanan</th>
                                <th scope="col" class="col-module">Jenis</th>
                                <th scope="col" class="col-time">Waktu</th>
                                <th scope="col">Estimasi</th>
                            </tr>
                        </thead>
                        <tbody data-estimator-rows aria-live="polite">
                            @foreach ($defaultRows as $row)
                                <tr>
                                    <td class="svc">{{ $row['service'] }}@if ($row['addon']) <span class="tag tag--orange">add-on</span>@endif<small>{{ $row['group'] }}</small></td>
                                    <td class="col-module"><span class="tag">{{ $row['module_label'] }}</span></td>
                                    <td class="col-time">{{ $row['duration'] ? '± ' . $row['duration'] . ' mnt' : '-' }}</td>
                                    <td class="amount">{{ $rp($row['price']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="estimator__foot">
                    <p>Harga jasa, belum termasuk sparepart dan oli. Biaya antar jemput atau layanan di rumah dihitung terpisah. {{ $estimator['disclaimer'] }}</p>
                    <span class="mono">{{ $estimator['template'] }}</span>
                </div>
            </div>
            <script type="application/json" id="estimator-data">@json($estimator)</script>
        </div>
    </section>

    {{-- =========================================================== STEPS --}}
    <section class="section section--tight">
        <div class="wrap">
            <div class="section__head">
                <div>
                    <p class="eyebrow" data-reveal>Alur pemesanan</p>
                    <h2 class="display h-lg" data-reveal style="--d: 60ms">Empat langkah, lalu tinggal tunggu.</h2>
                </div>
            </div>
            <ol class="steps">
                <li class="step" data-reveal>
                    <div class="step__num">1</div>
                    <h3>Pilih kendaraan &amp; layanan</h3>
                    <p>Simpan kendaraanmu sekali. Harga otomatis menyesuaikan kategorinya.</p>
                </li>
                <li class="step" data-reveal style="--d: 90ms">
                    <div class="step__num">2</div>
                    <h3>Atur jadwal &amp; cara</h3>
                    <p>Pilih slot yang masih kosong: datang sendiri, dijemput, atau dikerjakan di rumah.</p>
                </li>
                <li class="step" data-reveal style="--d: 180ms">
                    <div class="step__num">3</div>
                    <h3>Bayar dengan tenang</h3>
                    <p>QRIS, virtual account, e-wallet, atau bayar di tempat bila outlet mengizinkan.</p>
                </li>
                <li class="step" data-reveal style="--d: 270ms">
                    <div class="step__num">4</div>
                    <h3>Pantau sampai selesai</h3>
                    <p>Status pengerjaan, posisi driver, dan foto serah terima tercatat di aplikasi.</p>
                </li>
            </ol>
        </div>
    </section>

    {{-- ======================================================== COVERAGE --}}
    <section class="section section--paper2">
        <div class="wrap coverage">
            <div>
                <p class="eyebrow" data-reveal>Wilayah</p>
                <h2 class="display h-lg" data-reveal style="--d: 60ms">Mulai dari Medan.</h2>
                <p class="lede" data-reveal style="--d: 120ms">Kami memulai di satu kota dulu supaya layanannya benar-benar jalan. Kota lain menyusul setelah Medan stabil.</p>
                <div class="stat-list" data-reveal style="--d: 160ms">
                    <div class="stat"><strong data-count="{{ count($areas) }}">{{ count($areas) }}</strong><span>kecamatan di Kota Medan</span></div>
                    <div class="stat"><strong data-count="{{ $categoryCount }}">{{ $categoryCount }}</strong><span>kategori kendaraan, dari matic sampai mobil premium</span></div>
                    <div class="stat"><strong data-count="{{ $serviceCount }}">{{ $serviceCount }}</strong><span>jasa di template tarif awal</span></div>
                </div>
            </div>
            <ol class="districts" data-reveal="fade" aria-label="Daftar kecamatan">
                @foreach ($areas as $i => $area)
                    <li><span>{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>{{ \Illuminate\Support\Str::after($area, 'Medan ') }}</li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ======================================================== BUSINESS --}}
    <section class="section section--ink" id="usaha">
        <div class="wrap">
            <div class="biz">
                <div>
                    <p class="eyebrow" data-reveal>Untuk pemilik usaha</p>
                    <h2 class="display h-lg" data-reveal style="--d: 60ms">Punya bengkel, doorsmeer, atau toko sparepart?</h2>
                    <p class="lede" data-reveal style="--d: 120ms">Daftarkan usaha dan outletmu, buka modul yang kamu layani, atur tarif per kategori kendaraan, lalu terima pembayaran online. Saldo tercatat rapi dan bisa dicairkan ke rekening usaha.</p>
                    <ul class="ticks" data-reveal style="--d: 160ms">
                        <li>Satu usaha bisa punya banyak outlet</li>
                        <li>Tarif per kategori kendaraan, bisa salin dari template Medan</li>
                        <li>Slot booking mengikuti jumlah bay dan mekanik</li>
                        <li>Pembayaran Tripay: VA, QRIS, e-wallet, gerai retail</li>
                        <li>Riwayat transaksi, saldo, dan pengajuan pencairan dana</li>
                        <li>Akses staf dibatasi sesuai peran: kasir, mekanik, petugas cuci, driver</li>
                    </ul>
                    <div class="hero__ctas" data-reveal style="--d: 200ms">
                        <a class="btn btn--orange" href="{{ route('login') }}">
                            Masuk Portal Mitra
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                        </a>
                        <a class="btn btn--ghost" style="border-color: #f5f3ee; color: #f5f3ee;" href="mailto:halo@otogo.id?subject=Daftar%20usaha%20di%20OtoGo">Tanya lewat email</a>
                    </div>
                </div>

                <div class="panel" data-reveal style="--d: 120ms">
                    <div class="panel__head">
                        <strong>Pengaturan outlet</strong>
                        <small>Contoh · Bengkel</small>
                    </div>
                    <div class="switch-row"><span>Datang ke outlet<small>Booking slot di outlet</small></span><button type="button" class="switch" role="switch" aria-checked="true" data-switch aria-label="Datang ke outlet"></button></div>
                    <div class="switch-row"><span>Terima walk-in<small>Pelanggan tanpa booking</small></span><button type="button" class="switch" role="switch" aria-checked="true" data-switch aria-label="Terima walk-in"></button></div>
                    <div class="switch-row"><span>Antar jemput<small>Radius &amp; tarif per km</small></span><button type="button" class="switch" role="switch" aria-checked="false" data-switch aria-label="Antar jemput"></button></div>
                    <div class="switch-row"><span>Layanan di rumah<small>Tim datang ke pelanggan</small></span><button type="button" class="switch" role="switch" aria-checked="false" data-switch aria-label="Layanan di rumah"></button></div>
                    <div class="switch-row"><span>Bayar online<small>Lewat Tripay</small></span><button type="button" class="switch" role="switch" aria-checked="true" data-switch aria-label="Bayar online"></button></div>
                    <div class="switch-row"><span>Wajib bayar dulu<small>Sebelum booking terkonfirmasi</small></span><button type="button" class="switch" role="switch" aria-checked="false" data-switch aria-label="Wajib bayar dulu"></button></div>
                </div>
            </div>

            <div class="plans" aria-label="Paket langganan">
                @foreach ($plans as $i => $plan)
                    @php($featured = $plan['code'] === 'lengkap')
                    <div class="plan {{ $featured ? 'plan--featured' : '' }}" data-reveal style="--d: {{ $i * 70 }}ms">
                        <span class="plan__name">{{ $plan['name'] }}</span>
                        <div class="plan__price">
                            @if ((int) $plan['monthly_price'] === 0)
                                Gratis <small>{{ $plan['trial_days'] ?? 14 }} hari</small>
                            @else
                                {{ $rp((int) $plan['monthly_price']) }} <small>/bulan</small>
                            @endif
                        </div>
                        <ul class="plan__meta">
                            <li>{{ $plan['modules'] }}</li>
                            <li>{{ $plan['max_outlets'] }} outlet</li>
                            <li>Komisi online {{ str_replace('.', ',', rtrim(rtrim(number_format((float) $plan['commission_percent'], 1, '.', ''), '0'), '.')) }}%</li>
                        </ul>
                    </div>
                @endforeach
            </div>
            <p class="biz__note">Harga paket rencana peluncuran. Bayar tahunan hemat 2 bulan. Komisi hanya dipotong dari transaksi online yang lunas.</p>
        </div>
    </section>

    {{-- ============================================================ BLOG --}}
    @if ($latestPosts->isNotEmpty())
        <section class="section">
            <div class="wrap">
                <div class="section__head">
                    <div>
                        <p class="eyebrow" data-reveal>Blog</p>
                        <h2 class="display h-lg" data-reveal style="--d: 60ms">Catatan dari bengkel.</h2>
                    </div>
                    <p data-reveal style="--d: 120ms; justify-self: end;">
                        <a class="link-arrow" href="{{ route('blog.index') }}">Semua artikel
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                        </a>
                    </p>
                </div>
                <div class="post-grid">
                    @foreach ($latestPosts as $i => $post)
                        @include('frontend.partials.post-card', ['post' => $post, 'delay' => $i * 90])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============================================================= FAQ --}}
    <section class="section section--tight" id="faq">
        <div class="wrap">
            <div class="section__head">
                <div>
                    <p class="eyebrow" data-reveal>Tanya jawab</p>
                    <h2 class="display h-lg" data-reveal style="--d: 60ms">Yang sering ditanyakan.</h2>
                </div>
            </div>
            <div class="faq" data-reveal>
                <details>
                    <summary>Apakah OtoGo sudah bisa dipakai?</summary>
                    <p>OtoGo sedang disiapkan untuk Kota Medan. Pemilik usaha sudah bisa masuk lewat Portal Mitra, sementara aplikasi untuk pelanggan menyusul di Android dan iOS.</p>
                </details>
                <details>
                    <summary>Apakah harga di halaman ini harga pasti?</summary>
                    <p>Bukan. Itu kisaran harga pasar Medan yang kami pakai sebagai template awal. Harga final ditentukan masing-masing usaha dan selalu tampil sebelum kamu membayar.</p>
                </details>
                <details>
                    <summary>Berapa biaya antar jemput?</summary>
                    <p>Biaya dasar sudah mencakup 3 km pertama, lalu ada tarif per km berikutnya. Untuk mobil: {{ $feeLine('pickup_delivery', 'mobil') }}. Biaya ini sudah termasuk perjalanan pergi dan pulang, dan tiap outlet boleh mengatur tarifnya sendiri.</p>
                </details>
                <details>
                    <summary>Bagaimana cara bayarnya?</summary>
                    <p>Pembayaran online diproses Tripay: virtual account bank, QRIS, e-wallet, dan gerai retail. Bila outlet mengizinkan, kamu juga bisa bayar langsung di tempat.</p>
                </details>
                <details>
                    <summary>Bagaimana kalau ternyata ada kerusakan lain?</summary>
                    <p>Pekerjaan tambahan dikirim ke aplikasi lengkap dengan harganya. Mekanik baru mengerjakan setelah kamu menyetujuinya, jadi tagihan tidak membengkak diam-diam.</p>
                </details>
                <details>
                    <summary>Apa yang tercatat saat kendaraan dijemput?</summary>
                    <p>Driver memotret kendaraan dari minimal empat sisi, mencatat odometer dan level bensin, lalu kamu mengonfirmasi serah terimanya di aplikasi. Catatan yang sama dipakai saat kendaraan dikembalikan.</p>
                </details>
            </div>
        </div>
    </section>

    {{-- ========================================================= APP CTA --}}
    <section class="section section--tight" style="padding-top: 0;">
        <div class="wrap">
            <div class="app-cta" data-reveal>
                <div>
                    <p class="eyebrow">Aplikasi pelanggan</p>
                    <h2 class="display h-lg">Aplikasi OtoGo segera hadir.</h2>
                    <p class="lede">Simpan kendaraanmu, pesan servis atau cuci, dan pantau statusnya dari ponsel. Kami umumkan di blog begitu tersedia.</p>
                    <div class="app-cta__badges">
                        <span class="store" aria-disabled="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" aria-hidden="true"><path d="M5 4.5v15l9-7.5-9-7.5Z" /><path d="m14 12 3.6-3 2.4 1.4c.8.5.8 1.7 0 2.2L17.6 15 14 12Z" /></svg>
                            <span><small>Segera di</small>Android</span>
                        </span>
                        <span class="store" aria-disabled="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><rect x="6.5" y="2.8" width="11" height="18.4" rx="2.6" /><path d="M10.5 18h3" /></svg>
                            <span><small>Segera di</small>iOS</span>
                        </span>
                    </div>
                </div>
                <div class="app-cta__mark" aria-hidden="true">
                    <img src="{{ asset('assets/media/branding/logo-mark.svg') }}" alt="" width="240" height="240" loading="lazy" />
                </div>
            </div>
        </div>
    </section>
@endsection

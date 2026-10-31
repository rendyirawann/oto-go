{{-- Topbar: notifications, theme switcher, account menu. --}}
@php
    $user = auth()->user();
    $isSuperadmin = $user?->hasRole(['Superadmin', 'superadmin']) ?? false;
@endphp

<div class="app-topbar">

    <!--begin::Notifications-->
    <div class="position-relative">
        <button type="button"
            class="btn btn-icon btn-color-gray-600 btn-active-color-primary btn-outline btn-active-bg-light w-35px h-35px w-lg-40px h-lg-40px position-relative"
            data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end"
            aria-label="Notifikasi" aria-haspopup="true">
            <i class="ki-duotone ki-notification-bing fs-2">
                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
            </i>
            @if (($notificationCount ?? 0) > 0)
                <span class="app-notif-dot" aria-hidden="true"></span>
                <span class="visually-hidden">{{ $notificationCount }} notifikasi baru</span>
            @endif
        </button>

        <div class="menu menu-sub menu-sub-dropdown menu-column w-300px w-lg-350px" data-kt-menu="true">
            <div class="d-flex align-items-center justify-content-between px-5 pt-5 pb-3">
                <span class="fs-5 fw-bold text-gray-900">Notifikasi</span>
                @if (($notificationCount ?? 0) > 0)
                    <span class="badge badge-light-primary fw-semibold">{{ $notificationCount }} baru</span>
                @endif
            </div>

            <div class="separator"></div>

            <div class="app-notif-list px-3 py-3">
                @forelse ($notifications ?? [] as $item)
                    <a href="{{ $item['url'] }}" class="app-notif-item">
                        <span class="app-notif-item__icon bg-light-{{ $item['color'] }}">
                            <i class="ki-duotone {{ $item['icon'] }} fs-4 text-{{ $item['color'] }}">
                                <span class="path1"></span><span class="path2"></span>
                                <span class="path3"></span><span class="path4"></span>
                            </i>
                        </span>
                        <span class="d-flex flex-column min-w-0">
                            <span class="fs-7 fw-semibold text-gray-900 text-truncate">{{ $item['title'] }}</span>
                            <span class="fs-8 text-muted">{{ $item['actor'] }} &middot; {{ $item['time'] }}</span>
                        </span>
                    </a>
                @empty
                    <div class="text-center text-muted fs-7 py-8">
                        <i class="ki-duotone ki-notification-status fs-2x text-gray-400 d-block mb-3">
                            <span class="path1"></span><span class="path2"></span>
                            <span class="path3"></span><span class="path4"></span>
                        </i>
                        Belum ada aktivitas terbaru.
                    </div>
                @endforelse
            </div>

            <div class="separator"></div>
            <div class="py-3 text-center">
                <a href="{{ route('log-activity.index') }}"
                    class="btn btn-sm btn-light-primary">Lihat semua aktivitas</a>
            </div>
        </div>
    </div>
    <!--end::Notifications-->

    <!--begin::Theme mode-->
    <div class="position-relative">
        <button type="button"
            class="btn btn-icon btn-color-gray-600 btn-active-color-primary btn-outline btn-active-bg-light w-35px h-35px w-lg-40px h-lg-40px"
            data-kt-menu-trigger="{default:'click', lg: 'hover'}" data-kt-menu-attach="parent"
            data-kt-menu-placement="bottom-end" aria-label="Ubah tema tampilan">
            <i class="ki-duotone ki-night-day theme-light-show fs-2">
                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                <span class="path4"></span><span class="path5"></span><span class="path6"></span>
                <span class="path7"></span><span class="path8"></span><span class="path9"></span>
                <span class="path10"></span>
            </i>
            <i class="ki-duotone ki-moon theme-dark-show fs-2"><span class="path1"></span><span class="path2"></span></i>
        </button>

        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-3 fs-base w-150px"
            data-kt-menu="true" data-kt-element="theme-mode-menu">
            <div class="menu-item px-3 my-0">
                <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
                    <span class="menu-icon" data-kt-element="icon">
                        <i class="ki-duotone ki-night-day fs-3">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            <span class="path4"></span><span class="path5"></span><span class="path6"></span>
                            <span class="path7"></span><span class="path8"></span><span class="path9"></span>
                            <span class="path10"></span>
                        </i>
                    </span>
                    <span class="menu-title">Terang</span>
                </a>
            </div>
            <div class="menu-item px-3 my-0">
                <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
                    <span class="menu-icon" data-kt-element="icon">
                        <i class="ki-duotone ki-moon fs-3"><span class="path1"></span><span class="path2"></span></i>
                    </span>
                    <span class="menu-title">Gelap</span>
                </a>
            </div>
            <div class="menu-item px-3 my-0">
                <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
                    <span class="menu-icon" data-kt-element="icon">
                        <i class="ki-duotone ki-screen fs-3">
                            <span class="path1"></span><span class="path2"></span>
                            <span class="path3"></span><span class="path4"></span>
                        </i>
                    </span>
                    <span class="menu-title">Sistem</span>
                </a>
            </div>
        </div>
    </div>
    <!--end::Theme mode-->

    <!--begin::Account-->
    @auth
        <div class="position-relative">
            <button type="button"
                class="btn btn-flex btn-outline btn-active-bg-light align-items-center px-2 px-lg-3 py-1 h-35px h-lg-40px"
                data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end"
                aria-label="Menu akun" aria-haspopup="true">
                <x-avatar :user="$user" :size="28" />
                <span class="d-none d-md-flex flex-column align-items-start ms-2 lh-sm">
                    <span class="fs-8 fw-bold text-gray-800 text-truncate" style="max-width: 130px;">{{ $user->name }}</span>
                    <span class="fs-9 text-muted">{{ $user->role_label }}</span>
                </span>
                <i class="ki-outline ki-down fs-7 ms-1 ms-lg-2 text-gray-500"></i>
            </button>

            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-3 fs-6 w-275px"
                data-kt-menu="true">

                <div class="menu-item px-3">
                    <div class="menu-content d-flex align-items-center px-3">
                        <x-avatar :user="$user" :size="46" class="me-4" />
                        <div class="d-flex flex-column min-w-0">
                            <div class="fw-bold fs-6 text-truncate">{{ $user->name }}</div>
                            <span class="badge badge-light-success fw-bold fs-8 px-2 py-1 align-self-start mt-1">
                                {{ $user->role_label }}
                            </span>
                            <span class="fw-semibold text-muted fs-8 text-truncate mt-1">{{ $user->email }}</span>
                        </div>
                    </div>
                </div>

                <div class="separator my-2"></div>

                <div class="menu-item px-5">
                    <a href="{{ route('account.index') }}" class="menu-link px-5">
                        <span class="menu-icon"><i class="ki-outline ki-profile-circle fs-4"></i></span>
                        <span class="menu-title">Profil Saya</span>
                    </a>
                </div>
                <div class="menu-item px-5">
                    <a href="{{ route('my-security.index') }}" class="menu-link px-5">
                        <span class="menu-icon"><i class="ki-outline ki-shield-tick fs-4"></i></span>
                        <span class="menu-title">Keamanan</span>
                    </a>
                </div>
                <div class="menu-item px-5">
                    <a href="{{ route('my-activity.index') }}" class="menu-link px-5">
                        <span class="menu-icon"><i class="ki-outline ki-notepad fs-4"></i></span>
                        <span class="menu-title">Aktivitas Saya</span>
                    </a>
                </div>
                <div class="menu-item px-5">
                    <a href="{{ route('my-login-session.index') }}" class="menu-link px-5">
                        <span class="menu-icon"><i class="ki-outline ki-technology-2 fs-4"></i></span>
                        <span class="menu-title">Sesi Login</span>
                    </a>
                </div>

                @if ($isSuperadmin)
                    <div class="separator my-2"></div>
                    <div class="menu-item px-5">
                        <a href="{{ route('settings.index') }}" class="menu-link px-5">
                            <span class="menu-icon"><i class="ki-outline ki-setting-2 fs-4"></i></span>
                            <span class="menu-title">Pengaturan Aplikasi</span>
                        </a>
                    </div>
                @endif

                <div class="separator my-2"></div>

                <div class="menu-item px-5">
                    <form method="POST" action="{{ route('logout') }}" id="logout-form">
                        @csrf
                        <button type="submit" class="menu-link px-5 w-100 text-start bg-transparent border-0"
                            data-confirm="Anda akan keluar dari sesi ini. Lanjutkan?"
                            data-confirm-title="Keluar dari akun?"
                            data-confirm-ok="Ya, keluar"
                            data-confirm-cancel="Batal"
                            data-confirm-icon="warning">
                            <span class="menu-icon"><i class="ki-outline ki-exit-right fs-4"></i></span>
                            <span class="menu-title">Keluar</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endauth
    <!--end::Account-->
</div>

@extends('backend.layout.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $user = auth()->user();

        // Superadmins get the fleet-wide cards; everyone else sees their own
        // numbers, so the tile set differs by role.
        $tiles = $isSuperadmin
            ? [
                ['label' => 'Total Pengguna', 'value' => $stats['users'], 'icon' => 'ki-people', 'color' => 'primary', 'hint' => $stats['active_users'] . ' aktif'],
                ['label' => 'Role & Hak Akses', 'value' => $stats['roles'], 'icon' => 'ki-shield-tick', 'color' => 'info', 'hint' => 'Terdefinisi'],
                ['label' => 'Sedang Online', 'value' => $stats['online'] ?? '—', 'icon' => 'ki-pulse', 'color' => 'success', 'hint' => '5 menit terakhir'],
                ['label' => 'Aktivitas Hari Ini', 'value' => $stats['activity_today'], 'icon' => 'ki-notepad', 'color' => 'warning', 'hint' => $stats['activity_week'] . ' minggu ini'],
            ]
            : [
                ['label' => 'Aktivitas Hari Ini', 'value' => $stats['activity_today'], 'icon' => 'ki-notepad', 'color' => 'primary', 'hint' => 'Tercatat sistem'],
                ['label' => 'Aktivitas 7 Hari', 'value' => $stats['activity_week'], 'icon' => 'ki-chart-simple', 'color' => 'info', 'hint' => 'Minggu berjalan'],
                ['label' => 'Login Terakhir', 'value' => $stats['last_login'], 'icon' => 'ki-entrance-right', 'color' => 'success', 'hint' => 'Sesi Anda'],
                ['label' => 'IP Terakhir', 'value' => $stats['last_ip'], 'icon' => 'ki-technology-2', 'color' => 'warning', 'hint' => 'Perangkat Anda'],
            ];

        $breakdownTotal = max(1, collect($breakdown)->sum('count'));
    @endphp

    <!--begin::Page header-->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-6">
        <div class="min-w-0">
            <h1 class="fs-2 fw-bold text-gray-900 mb-1">
                Halo, {{ Str::before($user->name, ' ') }} 👋
            </h1>
            <p class="text-muted fs-6 mb-0">
                {{ now()->translatedFormat('l, d F Y') }} &middot; Ringkasan {{ $brand['name'] }}
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ $isSuperadmin ? route('log-activity.index') : route('my-activity.index') }}"
                class="btn btn-sm btn-light-primary">
                <i class="ki-outline ki-notepad fs-5 me-1"></i> Log Aktivitas
            </a>
            @if ($isSuperadmin)
                <a href="{{ route('users.index') }}" class="btn btn-sm btn-primary">
                    <i class="ki-outline ki-people fs-5 me-1"></i> Kelola Pengguna
                </a>
            @endif
        </div>
    </div>
    <!--end::Page header-->

    <!--begin::Stat tiles-->
    <div class="row g-4 g-xl-5 mb-5 mb-xl-6">
        @foreach ($tiles as $tile)
            <div class="col-6 col-xl-3">
                <div class="card card-flush app-stat-card shadow-sm">
                    <div class="card-body d-flex flex-column gap-3 p-4 p-xl-6">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <span class="app-stat-card__icon bg-light-{{ $tile['color'] }}">
                                <i class="ki-duotone {{ $tile['icon'] }} fs-2 text-{{ $tile['color'] }}">
                                    <span class="path1"></span><span class="path2"></span>
                                    <span class="path3"></span><span class="path4"></span>
                                    <span class="path5"></span>
                                </i>
                            </span>
                        </div>
                        <div class="min-w-0">
                            <div class="fs-2 fs-xl-2hx fw-bold text-gray-900 text-truncate lh-1 mb-1"
                                title="{{ $tile['value'] }}">{{ $tile['value'] }}</div>
                            <div class="fs-7 fw-semibold text-gray-600">{{ $tile['label'] }}</div>
                            <div class="fs-8 text-muted">{{ $tile['hint'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <!--end::Stat tiles-->

    <div class="row g-4 g-xl-5">

        <!--begin::Activity trend-->
        <div class="col-12 col-xxl-8">
            <div class="card card-flush shadow-sm h-100">
                <div class="card-header pt-6 border-0 flex-wrap gap-2">
                    <div class="card-title flex-column align-items-start">
                        <h3 class="fw-bold fs-4 mb-1">Tren Aktivitas</h3>
                        <span class="text-muted fs-7 fw-normal">
                            {{ $isSuperadmin ? 'Seluruh pengguna' : 'Aktivitas Anda' }} &middot; 14 hari terakhir
                        </span>
                    </div>
                </div>
                <div class="card-body pt-2 pb-4">
                    <div id="dashboard-trend-chart" style="min-height: 300px;"></div>
                </div>
            </div>
        </div>
        <!--end::Activity trend-->

        <!--begin::Breakdown-->
        <div class="col-12 col-xxl-4">
            <div class="card card-flush shadow-sm h-100">
                <div class="card-header pt-6 border-0">
                    <div class="card-title flex-column align-items-start">
                        <h3 class="fw-bold fs-4 mb-1">Jenis Aktivitas</h3>
                        <span class="text-muted fs-7 fw-normal">Distribusi 14 hari terakhir</span>
                    </div>
                </div>
                <div class="card-body pt-2 d-flex flex-column gap-5">
                    @foreach ($breakdown as $item)
                        @php $percent = round($item['count'] / $breakdownTotal * 100); @endphp
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2 gap-2">
                                <span class="d-flex align-items-center gap-2 min-w-0">
                                    <i class="ki-outline {{ $item['icon'] }} fs-5 text-{{ $item['color'] }}"></i>
                                    <span class="fs-7 fw-semibold text-gray-700 text-truncate">{{ $item['label'] }}</span>
                                </span>
                                <span class="fs-7 fw-bold text-gray-900">{{ $item['count'] }}</span>
                            </div>
                            <div class="progress h-6px bg-light">
                                <div class="progress-bar bg-{{ $item['color'] }}" role="progressbar"
                                    style="width: {{ $percent }}%" aria-valuenow="{{ $percent }}"
                                    aria-valuemin="0" aria-valuemax="100"
                                    aria-label="{{ $item['label'] }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <!--end::Breakdown-->

        <!--begin::Recent activity-->
        <div class="col-12 {{ $isSuperadmin ? 'col-xxl-8' : '' }}">
            <div class="card card-flush shadow-sm h-100">
                <div class="card-header pt-6 border-0 flex-wrap gap-2">
                    <div class="card-title flex-column align-items-start">
                        <h3 class="fw-bold fs-4 mb-1">Aktivitas Terbaru</h3>
                        <span class="text-muted fs-7 fw-normal">
                            {{ $isSuperadmin ? 'Semua pengguna' : 'Hanya aktivitas Anda' }}
                        </span>
                    </div>
                    <div class="card-toolbar">
                        <a href="{{ $isSuperadmin ? route('log-activity.index') : route('my-activity.index') }}"
                            class="btn btn-sm btn-light">Lihat semua</a>
                    </div>
                </div>
                <div class="card-body pt-2 pb-4">
                    @forelse ($recent as $activity)
                        <div class="d-flex align-items-start gap-3 py-3 {{ ! $loop->last ? 'border-bottom border-gray-200' : '' }}">
                            <x-avatar :user="$activity->causer" :size="38" />
                            <div class="flex-grow-1 min-w-0">
                                <div class="fs-7 fw-semibold text-gray-900">{{ $activity->description }}</div>
                                <div class="fs-8 text-muted">
                                    {{ $activity->causer->name ?? 'Sistem' }}
                                    &middot; {{ $activity->created_at->diffForHumans() }}
                                    @if (! empty($activity->properties['ip']))
                                        &middot; {{ $activity->properties['ip'] }}
                                    @endif
                                </div>
                            </div>
                            <span class="badge badge-light-{{ $activity->log_name ? 'primary' : 'secondary' }} fs-9 text-nowrap d-none d-sm-inline">
                                {{ Str::limit($activity->log_name ?: 'sistem', 18) }}
                            </span>
                        </div>
                    @empty
                        <div class="text-center text-muted py-10">
                            <i class="ki-duotone ki-file-deleted fs-3x text-gray-400 d-block mb-3">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            Belum ada aktivitas tercatat.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
        <!--end::Recent activity-->

        @if ($isSuperadmin)
            <!--begin::Top users-->
            <div class="col-12 col-xxl-4">
                <div class="card card-flush shadow-sm h-100">
                    <div class="card-header pt-6 border-0">
                        <div class="card-title flex-column align-items-start">
                            <h3 class="fw-bold fs-4 mb-1">Pengguna Teraktif</h3>
                            <span class="text-muted fs-7 fw-normal">14 hari terakhir</span>
                        </div>
                    </div>
                    <div class="card-body pt-2 pb-4">
                        @forelse ($topUsers as $row)
                            <div class="d-flex align-items-center gap-3 py-3 {{ ! $loop->last ? 'border-bottom border-gray-200' : '' }}">
                                <x-avatar :user="$row['user']" :size="38" />
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fs-7 fw-semibold text-gray-900 text-truncate">{{ $row['user']->name }}</div>
                                    <div class="fs-8 text-muted">{{ $row['user']->role_label }}</div>
                                </div>
                                <span class="badge badge-light-primary fw-bold">{{ $row['total'] }}</span>
                            </div>
                        @empty
                            <div class="text-center text-muted py-10">Belum ada data.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            <!--end::Top users-->
        @endif
    </div>

    @push('scripts')
        {{-- ApexCharts ships inside plugins.bundle.js, already loaded by the layout. --}}
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var host = document.getElementById('dashboard-trend-chart');

                if (!host || typeof ApexCharts === 'undefined') {
                    return;
                }

                // Read theme tokens from Metronic so the chart follows light/dark.
                var css = getComputedStyle(document.documentElement);
                var primary = css.getPropertyValue('--bs-primary').trim() || '#3E97FF';
                var gridColor = css.getPropertyValue('--bs-gray-200').trim() || '#eff2f5';
                var labelColor = css.getPropertyValue('--bs-gray-500').trim() || '#99a1b7';

                var chart = new ApexCharts(host, {
                    chart: {
                        type: 'area',
                        height: 300,
                        toolbar: { show: false },
                        fontFamily: 'inherit',
                        animations: { enabled: !window.matchMedia('(prefers-reduced-motion: reduce)').matches }
                    },
                    series: [{ name: 'Aktivitas', data: @json($trend['data']) }],
                    xaxis: {
                        categories: @json($trend['labels']),
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: { style: { colors: labelColor, fontSize: '12px' }, rotate: 0, hideOverlappingLabels: true }
                    },
                    yaxis: {
                        min: 0,
                        forceNiceScale: true,
                        labels: { style: { colors: labelColor, fontSize: '12px' } }
                    },
                    stroke: { curve: 'smooth', width: 3 },
                    colors: [primary],
                    fill: {
                        type: 'gradient',
                        gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 90, 100] }
                    },
                    dataLabels: { enabled: false },
                    grid: { borderColor: gridColor, strokeDashArray: 4, xaxis: { lines: { show: false } } },
                    tooltip: { theme: document.documentElement.getAttribute('data-bs-theme') || 'light' },
                    markers: { size: 0, hover: { size: 6 } }
                });

                chart.render();
            });
        </script>
    @endpush
@endsection

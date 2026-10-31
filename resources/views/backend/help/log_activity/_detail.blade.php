{{-- Reusable detail body: shown inside the table modal and on the full page. --}}
@php
    [$typeLabel, $typeColor] = \App\Support\ActivityDiff::badge($diff['type']);
    $isUpdate = $diff['has_old'] && $diff['has_new'];
@endphp

<div class="d-flex flex-column gap-6">

    <!--begin::Summary-->
    <div class="d-flex flex-wrap align-items-center gap-3">
        <span class="badge badge-{{ $typeColor }} fs-7 fw-bold px-3 py-2">{{ $typeLabel }}</span>
        <span class="badge badge-light fs-8 fw-semibold px-3 py-2">{{ $activity->log_name ?: 'sistem' }}</span>
        <span class="text-muted fs-7">
            {{ $activity->created_at->timezone(config('app.timezone'))->translatedFormat('l, d F Y \p\u\k\u\l H:i:s') }}
        </span>
    </div>

    <div class="d-flex align-items-center gap-3">
        <x-avatar :user="$activity->causer" :size="44" />
        <div class="min-w-0">
            <div class="fw-bold fs-6 text-gray-900">{{ $activity->causer->name ?? 'Sistem' }}</div>
            <div class="fs-7 text-muted">
                {{ $activity->causer?->role_label ?? 'Otomatis' }}
                @if ($activity->causer?->email)
                    &middot; {{ $activity->causer->email }}
                @endif
            </div>
        </div>
    </div>

    <div class="notice bg-light-{{ $typeColor }} rounded border-{{ $typeColor }} border border-dashed p-4">
        <div class="fw-semibold text-gray-800">{{ $activity->description }}</div>
    </div>
    <!--end::Summary-->

    <!--begin::Field comparison-->
    @if ($diff['rows'] !== [])
        <div>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h4 class="fw-bold fs-5 mb-0">
                    {{ $isUpdate ? 'Perbandingan Data' : ($diff['has_old'] ? 'Data Yang Dihapus' : 'Data Yang Dibuat') }}
                </h4>
                @if ($isUpdate)
                    <span class="badge badge-light-danger fw-semibold">
                        {{ $diff['changed'] }} field berubah
                    </span>
                @endif
            </div>

            @if ($isUpdate)
                <p class="text-muted fs-8 mb-3">
                    Baris bertanda merah adalah field yang nilainya berubah.
                </p>
            @endif

            <div class="app-scroll-x">
                <table class="table table-row-bordered table-row-gray-200 align-middle gy-3 mb-0">
                    <thead>
                        <tr class="fw-bold fs-8 text-uppercase text-muted">
                            <th style="min-width: 150px;">Field</th>
                            @if ($diff['has_old'])
                                <th style="min-width: 190px;">Sebelum</th>
                            @endif
                            @if ($diff['has_new'])
                                <th style="min-width: 190px;">{{ $isUpdate ? 'Sesudah' : 'Nilai' }}</th>
                            @endif
                            @if ($isUpdate)
                                <th class="text-end" style="min-width: 90px;">Status</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        @foreach ($diff['rows'] as $row)
                            <tr class="{{ $row['changed'] ? 'app-diff-row--changed' : '' }}">
                                <td class="app-diff-key">
                                    {{ $row['label'] }}
                                    <span class="d-block fs-9 text-muted fw-normal">{{ $row['key'] }}</span>
                                </td>

                                @if ($diff['has_old'])
                                    <td class="{{ $row['changed'] ? 'app-diff-old' : 'text-gray-700' }}">
                                        {{ $row['old'] ?? '—' }}
                                    </td>
                                @endif

                                @if ($diff['has_new'])
                                    <td class="{{ $row['changed'] ? 'app-diff-new fw-semibold' : 'text-gray-700' }}">
                                        {{ $row['new'] ?? '—' }}
                                    </td>
                                @endif

                                @if ($isUpdate)
                                    <td class="text-end">
                                        @if ($row['changed'])
                                            <span class="badge badge-light-danger fw-semibold">Berubah</span>
                                        @else
                                            <span class="badge badge-light fw-semibold text-muted">Tetap</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    <!--end::Field comparison-->

    <!--begin::Context-->
    @if ($context !== [])
        <div>
            <h4 class="fw-bold fs-5 mb-3">Informasi Perangkat &amp; Permintaan</h4>
            <div class="row g-3">
                @foreach ($context as $label => $value)
                    <div class="col-12 col-md-6">
                        <div class="border border-gray-200 border-dashed rounded p-3 h-100">
                            <div class="fs-9 text-uppercase text-muted fw-semibold mb-1">{{ $label }}</div>
                            <div class="fs-7 fw-semibold text-gray-800" style="word-break: break-word;">{{ $value }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    <!--end::Context-->

    @if ($diff['rows'] === [] && $context === [])
        <div class="text-center text-muted py-8">Tidak ada detail tambahan untuk aktivitas ini.</div>
    @endif
</div>

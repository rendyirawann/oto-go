@extends('backend.layout.app')

@section('title', 'Detail Log Aktivitas')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-6">
        <div class="min-w-0">
            <h1 class="fs-2 fw-bold text-gray-900 mb-1">Detail Aktivitas</h1>
            <nav aria-label="Breadcrumb">
                <ol class="breadcrumb breadcrumb-separatorless fw-semibold fs-8 my-0">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('log-activity.index') }}" class="text-muted text-hover-primary">Log Aktivitas</a>
                    </li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-gray-900" aria-current="page">#{{ $activity->getKey() }}</li>
                </ol>
            </nav>
        </div>

        <a href="{{ route('log-activity.index') }}" class="btn btn-sm btn-light">
            <i class="ki-outline ki-arrow-left fs-5 me-1"></i>Kembali
        </a>
    </div>

    <div class="card card-flush shadow-sm">
        <div class="card-body p-5 p-lg-8">
            @include('backend.help.log_activity._detail')
        </div>
    </div>
@endsection

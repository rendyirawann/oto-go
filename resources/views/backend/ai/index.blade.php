@extends('backend.layout.app')

@section('title', 'Asisten AI')

@push('stylesheets')
    <link rel="stylesheet" href="{{ asset('assets/ai/ai.css') }}?v={{ @filemtime(public_path('assets/ai/ai.css')) ?: 1 }}" />
@endpush

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-5">
        <div class="min-w-0">
            <h1 class="fs-2 fw-bold text-gray-900 mb-1">Asisten AI</h1>
            <p class="text-muted fs-6 mb-0">
                Agen AI untuk tim OtoGo: estimasi tarif, draf konten, ringkasan data, dan riset web.
            </p>
        </div>
    </div>

    @unless ($configured)
        <div class="alert alert-warning d-flex align-items-center gap-3 mb-5" role="status">
            <span>Kunci <code>GROQ_API_KEY</code> belum diisi di <code>.env</code>. Asisten akan menjawab "tidak tersedia" sampai kunci diisi.</span>
        </div>
    @endunless

    <div class="ai-page" data-ai-page
        data-delete-url="{{ route('admin.ai.conversations.destroy', ['id' => '__ID__']) }}">

        <aside class="ai-page__list" aria-label="Riwayat percakapan">
            <div class="ai-page__list-head">
                <span class="ai-page__list-title">Percakapan</span>
                <button type="button" class="ai-btn-ghost" data-ai-page-new>
                    <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    Baru
                </button>
            </div>

            <ul class="ai-convs" data-ai-convs>
                @forelse ($conversations as $conversation)
                    <li class="ai-convs__item" data-id="{{ $conversation->id }}">
                        <button type="button" class="ai-convs__open" data-ai-open="{{ $conversation->id }}">
                            <span class="ai-convs__title">{{ $conversation->title ?: 'Percakapan' }}</span>
                            <span class="ai-convs__meta">
                                {{ $conversation->updated_at?->timezone('Asia/Jakarta')->translatedFormat('d M, H:i') }}
                                @if ($conversation->channel === 'mobile') &middot; aplikasi @endif
                            </span>
                        </button>
                        <button type="button" class="ai-convs__delete" data-ai-delete="{{ $conversation->id }}" aria-label="Hapus percakapan">
                            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M5 7h14M10 7V5h4v2M7 7l1 12h8l1-12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </li>
                @empty
                    <li class="ai-convs__empty" data-ai-convs-empty>Belum ada percakapan.</li>
                @endforelse
            </ul>
        </aside>

        <section class="ai-page__chat">
            @include('backend.ai._chat', ['mode' => 'page'])
        </section>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/ai/ai.js') }}?v={{ @filemtime(public_path('assets/ai/ai.js')) ?: 1 }}" defer></script>
@endpush

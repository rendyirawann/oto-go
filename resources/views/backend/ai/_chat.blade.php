{{--
    Chat surface shared by the full page and the floating widget.
    @param string $mode  'page' | 'widget'
--}}
@php
    $isWidget = ($mode ?? 'page') === 'widget';
    $uid = $isWidget ? 'ai-w' : 'ai-p';
    $suggestions = [
        'Estimasi biaya servis berkala MPV',
        'Berapa ongkos antar jemput mobil sejauh 8 km?',
        'Tulis draf artikel blog tentang cuci salju',
        'Ringkas aktivitas platform minggu ini',
    ];
@endphp

<div class="ai-chat {{ $isWidget ? 'ai-chat--widget' : 'ai-chat--page' }}"
    data-ai-chat
    data-mode="{{ $isWidget ? 'widget' : 'page' }}"
    data-endpoint="{{ route('admin.ai.chat') }}"
    data-conversation-url="{{ route('admin.ai.conversations.show', ['id' => '__ID__']) }}"
    data-avatar="{{ asset('assets/media/branding/logo-mark.svg') }}"
    @if ($isWidget) data-storage-key="otogo.ai.conversation" @endif>

    <header class="ai-chat__head">
        <img src="{{ asset('assets/media/branding/logo-mark.svg') }}" alt="" class="ai-chat__mark" width="32" height="32" />
        <div class="ai-chat__titles">
            <h2 class="ai-chat__title" id="{{ $uid }}-title">Asisten OtoGo</h2>
            <p class="ai-chat__subtitle">Tarif pasar Medan, blog, dan pencarian web</p>
        </div>
        <div class="ai-chat__actions">
            <button type="button" class="ai-icon-btn" data-ai-new title="Percakapan baru" aria-label="Mulai percakapan baru">
                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
            @if ($isWidget)
                <a class="ai-icon-btn" href="{{ route('admin.ai.index') }}" title="Buka halaman penuh" aria-label="Buka Asisten AI di halaman penuh">
                    <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M14 4h6v6M20 4l-8 8M10 6H5a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
                <button type="button" class="ai-icon-btn" data-ai-close title="Tutup" aria-label="Tutup asisten">
                    <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
            @endif
        </div>
    </header>

    <div class="ai-chat__thread" data-ai-thread role="log" aria-live="polite" aria-relevant="additions" aria-labelledby="{{ $uid }}-title" tabindex="0">
        <div class="ai-empty" data-ai-empty>
            <img src="{{ asset('assets/media/branding/logo-mark.svg') }}" alt="" width="44" height="44" class="ai-empty__mark" />
            <p class="ai-empty__title">Ada yang bisa dibantu, {{ Str::before(auth()->user()->name, ' ') }}?</p>
            <p class="ai-empty__text">
                Saya bisa menghitung estimasi biaya servis dan cuci di Medan, mencari artikel blog OtoGo,
                menelusuri web, dan merangkum data platform untuk staf.
            </p>
            <div class="ai-chips" role="list">
                @foreach ($suggestions as $suggestion)
                    <button type="button" class="ai-chip" role="listitem" data-ai-suggest>{{ $suggestion }}</button>
                @endforeach
            </div>
        </div>
    </div>

    <form class="ai-composer" data-ai-form novalidate>
        <label for="{{ $uid }}-input" class="visually-hidden">Tulis pertanyaan untuk Asisten OtoGo</label>
        <textarea id="{{ $uid }}-input" class="ai-composer__input" data-ai-input rows="1"
            maxlength="{{ (int) config('ai.max_message_chars', 4000) }}"
            placeholder="Tanyakan apa saja tentang OtoGo atau kendaraan…" autocomplete="off"></textarea>
        <button type="submit" class="ai-composer__send" data-ai-send aria-label="Kirim pertanyaan" disabled>
            <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path d="M5 12h13M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </form>
    <p class="ai-composer__hint">Enter untuk kirim, Shift + Enter untuk baris baru. Jawaban AI bisa keliru; cek ulang angka penting.</p>
</div>

{{--
    Floating AI assistant, included by the admin layout on every page.
    Hidden on the full assistant page and for guests; renders nothing if the
    module routes are missing, so it can never break a page.
--}}
@auth
    @if (Route::has('admin.ai.chat') && ! request()->routeIs('admin.ai.*'))
        <link rel="stylesheet" href="{{ asset('assets/ai/ai.css') }}?v={{ @filemtime(public_path('assets/ai/ai.css')) ?: 1 }}" />

        <div class="ai-widget" data-ai-widget>
            <button type="button" class="ai-launcher" data-ai-launcher aria-expanded="false" aria-controls="ai-widget-panel">
                <img src="{{ asset('assets/media/branding/logo-mark.svg') }}" alt="" width="30" height="30" />
                <span class="ai-launcher__label">Tanya AI</span>
            </button>

            <div class="ai-panel" id="ai-widget-panel" role="dialog" aria-modal="false" aria-label="Asisten OtoGo" hidden>
                @include('backend.ai._chat', ['mode' => 'widget'])
            </div>
        </div>

        <script src="{{ asset('assets/ai/ai.js') }}?v={{ @filemtime(public_path('assets/ai/ai.js')) ?: 1 }}" defer></script>
    @endif
@endauth

{{-- Row actions for the activity-log table. --}}
<div class="d-flex justify-content-end gap-2">
    <button type="button" class="btn btn-sm btn-light-primary px-3 py-2"
        data-log-detail="{{ route('log-activity.detail', $activity->getKey()) }}">
        <i class="ki-outline ki-eye fs-6 me-1"></i>Detail
    </button>
    <a href="{{ route('log-activity.show', $activity->getKey()) }}"
        class="btn btn-sm btn-icon btn-light px-3 py-2" title="Buka di halaman penuh"
        aria-label="Buka detail di halaman penuh">
        <i class="ki-outline ki-exit-right-corner fs-6"></i>
    </a>
</div>

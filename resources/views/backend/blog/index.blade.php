@extends('backend.layout.app')

@section('title', 'Blog')

@section('content')
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-6">
        <div class="min-w-0">
            <h1 class="fs-2 fw-bold text-gray-900 mb-1">Blog</h1>
            <p class="text-muted fs-7 mb-0">Artikel yang tampil di halaman publik, aplikasi mobile, dan API.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('blog.index') }}" class="btn btn-sm btn-light" target="_blank" rel="noopener">
                <i class="ki-outline ki-exit-up fs-5"></i> Lihat blog
            </a>
            <a href="{{ route('admin.posts.create') }}" class="btn btn-sm btn-primary">
                <i class="ki-outline ki-plus fs-5"></i> Artikel baru
            </a>
        </div>
    </div>

    <div class="card card-flush">
        <div class="card-header py-6 gap-3">
            <form method="GET" action="{{ route('admin.posts.index') }}" class="d-flex flex-wrap align-items-center gap-3 w-100">
                <div class="position-relative flex-grow-1" style="max-width: 360px;">
                    <i class="ki-outline ki-magnifier fs-3 position-absolute top-50 translate-middle-y ms-4"></i>
                    <input type="search" name="q" value="{{ $search }}" maxlength="80" class="form-control form-control-solid ps-12" placeholder="Cari judul atau ringkasan" />
                </div>
                <select name="status" class="form-select form-select-solid w-auto" aria-label="Filter status">
                    <option value="">Semua status</option>
                    @foreach (\App\Models\Post::STATUSES as $key => $label)
                        <option value="{{ $key }}" @selected($status === $key)>{{ $label }} ({{ (int) ($counts[$key] ?? 0) }})</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-light-primary">Terapkan</button>
                @if ($search !== '' || $status)
                    <a href="{{ route('admin.posts.index') }}" class="btn btn-light">Reset</a>
                @endif
            </form>
        </div>

        <div class="card-body pt-0">
            @if ($posts->isEmpty())
                <div class="text-center py-15">
                    <i class="ki-outline ki-message-text-2 fs-3x text-gray-400 mb-4"></i>
                    <p class="text-gray-700 fw-semibold mb-1">Belum ada artikel{{ $search !== '' ? ' yang cocok' : '' }}.</p>
                    <a href="{{ route('admin.posts.create') }}" class="btn btn-sm btn-primary mt-4">Tulis artikel pertama</a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-4">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th class="min-w-300px">Artikel</th>
                                <th class="min-w-125px">Kategori</th>
                                <th class="min-w-100px">Status</th>
                                <th class="min-w-150px">Terbit</th>
                                <th class="text-end min-w-150px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 fw-semibold">
                            @foreach ($posts as $post)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-4">
                                            <img src="{{ $post->cover_url }}" alt="" width="96" height="50" loading="lazy"
                                                class="rounded border object-fit-cover" style="width: 96px; height: 50px;" />
                                            <div class="min-w-0">
                                                <a href="{{ route('admin.posts.edit', $post) }}" class="text-gray-900 text-hover-primary fw-bold d-block text-truncate" style="max-width: 420px;">{{ $post->title }}</a>
                                                <span class="text-muted fs-7">{{ $post->reading_minutes }} menit baca · {{ $post->author?->name ?? 'Tim OtoGo' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $post->category?->name ?? '—' }}</td>
                                    <td>
                                        @if ($post->is_published)
                                            <span class="badge badge-light-success">Terbit</span>
                                        @elseif ($post->status === \App\Models\Post::STATUS_PUBLISHED)
                                            <span class="badge badge-light-info">Terjadwal</span>
                                        @else
                                            <span class="badge badge-light-warning">Draf</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ $post->published_at?->translatedFormat('j M Y, H:i') ?? '—' }}</td>
                                    <td class="text-end text-nowrap">
                                        @if ($post->is_published)
                                            <a href="{{ route('blog.show', $post->slug) }}" target="_blank" rel="noopener" class="btn btn-sm btn-icon btn-light" title="Lihat">
                                                <i class="ki-outline ki-eye fs-5"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('admin.posts.edit', $post) }}" class="btn btn-sm btn-icon btn-light-primary" title="Ubah">
                                            <i class="ki-outline ki-pencil fs-5"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.posts.destroy', $post) }}" class="d-inline" data-confirm-delete="{{ $post->title }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-icon btn-light-danger" title="Hapus">
                                                <i class="ki-outline ki-trash fs-5"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-4">
                    <span class="text-muted fs-7">Menampilkan {{ $posts->firstItem() }}–{{ $posts->lastItem() }} dari {{ $posts->total() }} artikel</span>
                    {{ $posts->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('form[data-confirm-delete]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (form.dataset.confirmed === '1') {
                    return;
                }
                event.preventDefault();
                var title = form.getAttribute('data-confirm-delete');
                var proceed = function () { form.dataset.confirmed = '1'; form.submit(); };

                if (window.Swal) {
                    Swal.fire({
                        title: 'Hapus artikel?',
                        text: '"' + title + '" akan dihapus permanen.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal',
                        customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-light' },
                        buttonsStyling: false
                    }).then(function (result) { if (result.isConfirmed) { proceed(); } });
                } else if (window.confirm('Hapus artikel "' + title + '"?')) {
                    proceed();
                }
            });
        });
    </script>
@endpush

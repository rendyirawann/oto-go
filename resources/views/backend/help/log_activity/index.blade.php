@extends('backend.layout.app')

@section('title', 'Log Aktivitas')

@section('content')
    <!--begin::Page header-->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-6">
        <div class="min-w-0">
            <h1 class="fs-2 fw-bold text-gray-900 mb-1">Log Aktivitas</h1>
            <p class="text-muted fs-7 mb-0">
                @if ($isSuperadmin)
                    Menampilkan aktivitas dari <strong>seluruh pengguna dan role</strong>.
                @else
                    Menampilkan <strong>aktivitas Anda sendiri</strong>. Log pengguna lain hanya dapat dilihat Superadmin.
                @endif
            </p>
        </div>
    </div>
    <!--end::Page header-->

    <div class="card card-flush shadow-sm">
        <div class="card-header pt-6 border-0 flex-wrap gap-3">
            <div class="card-title flex-wrap gap-3">
                <div class="d-flex align-items-center position-relative">
                    <i class="ki-outline ki-magnifier fs-4 position-absolute ms-4" aria-hidden="true"></i>
                    <label for="log-search" class="visually-hidden">Cari aktivitas</label>
                    <input type="search" id="log-search" class="form-control form-control-solid w-250px ps-12"
                        placeholder="Cari aktivitas..." />
                </div>

                <div class="d-flex align-items-center gap-2">
                    <label for="log-type" class="visually-hidden">Filter jenis aktivitas</label>
                    <select id="log-type" class="form-select form-select-solid w-150px">
                        <option value="">Semua jenis</option>
                        <option value="create">Tambah</option>
                        <option value="update">Ubah</option>
                        <option value="delete">Hapus</option>
                        <option value="login">Login</option>
                        <option value="logout">Logout</option>
                    </select>
                </div>
            </div>

            <div class="card-toolbar flex-wrap gap-2">
                <label for="log-from" class="visually-hidden">Dari tanggal</label>
                <input type="date" id="log-from" class="form-control form-control-solid w-150px" />
                <label for="log-to" class="visually-hidden">Sampai tanggal</label>
                <input type="date" id="log-to" class="form-control form-control-solid w-150px" />

                <button type="button" class="btn btn-sm btn-light-primary" id="log-refresh">
                    <i class="ki-outline ki-arrows-circle fs-5 me-1"></i>Muat ulang
                </button>
            </div>
        </div>

        <div class="card-body pt-2">
            <div class="app-scroll-x">
                <table class="table align-middle table-row-dashed fs-7 gy-4 w-100" id="log-table">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-8 text-uppercase gs-0">
                            <th class="min-w-125px">Jenis</th>
                            <th class="min-w-125px">Pengguna</th>
                            <th class="min-w-200px">Deskripsi</th>
                            <th class="min-w-110px">IP Address</th>
                            <th class="min-w-110px">Perangkat</th>
                            <th class="min-w-150px">Waktu</th>
                            <th class="text-end min-w-125px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 fw-semibold"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!--begin::Detail modal-->
    <div class="modal fade" id="log-detail-modal" tabindex="-1" aria-labelledby="log-detail-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-4 fw-bold" id="log-detail-title">Detail Aktivitas</h2>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal"
                        aria-label="Tutup">
                        <i class="ki-outline ki-cross fs-2"></i>
                    </button>
                </div>
                <div class="modal-body" id="log-detail-body">
                    <div class="text-center py-10">
                        <span class="spinner-border text-primary" role="status" aria-hidden="true"></span>
                        <div class="text-muted fs-7 mt-3">Memuat detail...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--end::Detail modal-->

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var filters = {
                    type: document.getElementById('log-type'),
                    from: document.getElementById('log-from'),
                    to: document.getElementById('log-to')
                };

                var table = $('#log-table').DataTable({
                    processing: true,
                    serverSide: true,
                    order: [],
                    pageLength: 10,
                    lengthMenu: [10, 25, 50, 100],
                    autoWidth: false,
                    responsive: false,
                    language: {
                        processing: 'Memuat...',
                        zeroRecords: 'Tidak ada aktivitas yang cocok',
                        emptyTable: 'Belum ada aktivitas tercatat',
                        info: 'Menampilkan _START_-_END_ dari _TOTAL_ aktivitas',
                        infoEmpty: 'Tidak ada data',
                        infoFiltered: '(disaring dari _MAX_ total)',
                        lengthMenu: 'Tampilkan _MENU_',
                        paginate: { first: 'Awal', last: 'Akhir', next: 'Berikutnya', previous: 'Sebelumnya' }
                    },
                    dom: "<'table-responsive'tr><'d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 pt-4'<'d-flex align-items-center gap-2'li><''p>>",
                    ajax: {
                        url: @json(route('get-datalogactivity')),
                        data: function (params) {
                            params.type = filters.type.value;
                            params.from = filters.from.value;
                            params.to = filters.to.value;
                        }
                    },
                    columns: [
                        { data: 'log_name', name: 'log_name', orderable: false },
                        { data: 'causer_id', name: 'causer_id', orderable: false },
                        { data: 'description', name: 'description', orderable: false },
                        { data: 'ip', name: 'ip', orderable: false, searchable: false },
                        { data: 'device', name: 'device', orderable: false, searchable: false },
                        { data: 'created_at', name: 'created_at', orderable: false, searchable: false },
                        { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
                    ]
                });

                // Debounced free-text search.
                var searchTimer;
                document.getElementById('log-search').addEventListener('input', function (event) {
                    clearTimeout(searchTimer);
                    var value = event.target.value;
                    searchTimer = setTimeout(function () {
                        table.search(value).draw();
                    }, 400);
                });

                Object.keys(filters).forEach(function (key) {
                    filters[key].addEventListener('change', function () {
                        table.draw();
                    });
                });

                document.getElementById('log-refresh').addEventListener('click', function () {
                    table.ajax.reload(null, false);
                });

                // --- Detail modal -------------------------------------------------
                var modalEl = document.getElementById('log-detail-modal');
                var modal = new bootstrap.Modal(modalEl);
                var body = document.getElementById('log-detail-body');
                var title = document.getElementById('log-detail-title');
                var loading = body.innerHTML;

                document.addEventListener('click', function (event) {
                    var trigger = event.target.closest('[data-log-detail]');

                    if (!trigger) {
                        return;
                    }

                    event.preventDefault();
                    body.innerHTML = loading;
                    title.textContent = 'Detail Aktivitas';
                    modal.show();

                    fetch(trigger.getAttribute('data-log-detail'), {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('http ' + response.status);
                            }
                            return response.json();
                        })
                        .then(function (data) {
                            title.textContent = data.title || 'Detail Aktivitas';
                            body.innerHTML = data.html;
                        })
                        .catch(function () {
                            body.innerHTML = '<div class="alert alert-danger mb-0">Gagal memuat detail aktivitas.</div>';
                        });
                });
            });
        </script>
    @endpush
@endsection

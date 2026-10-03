@extends('layouts.app')

@section('content')
    <div class="main-content order-table-page">
        <div class="mb-4">
            <h2 class="h4 mb-1 font-weight-bold"><i class="fa fa-users me-2"></i>Monitor Sesi Meja</h2>
            <p class="text-muted mb-0">Pantau sesi device di meja. Tidak ada hard delete.</p>
        </div>

        @unless ($nodeEnabled)
            <div class="alert alert-warning border-0 shadow-sm"><i class="fa fa-plug me-2"></i>Aksi <strong>Tutup Sesi</strong> dinonaktifkan sampai integrasi layanan Node diaktifkan.</div>
        @endunless

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-3">Daftar Sesi</h5>
                <div class="row g-2">
                    <div class="col-md-3"><label for="outlet-filter" class="form-label small mb-1">Outlet</label><select id="outlet-filter" class="form-select form-select-sm"><option value="">Semua</option>@foreach ($outlets as $outlet)<option value="{{ $outlet->id }}">{{ $outlet->name }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label for="status-filter" class="form-label small mb-1">Status</label><select id="status-filter" class="form-select form-select-sm"><option value="">Semua</option><option value="open">open</option><option value="closed">closed</option></select></div>
                    <div class="col-md-3"><label for="date-from-filter" class="form-label small mb-1">Dari</label><input id="date-from-filter" type="date" class="form-control form-control-sm"></div>
                    <div class="col-md-3"><label for="date-to-filter" class="form-label small mb-1">Sampai</label><input id="date-to-filter" type="date" class="form-control form-control-sm"></div>
                </div>
            </div>
            <div class="card-body"><div class="table-responsive">{!! $dataTable->table(['class' => 'table table-hover align-middle mb-0']) !!}</div></div>
        </div>
    </div>

    @push('js')
        {!! $dataTable->scripts() !!}
        <script>
            $(function () {
                const table = window.LaravelDataTables['session-monitor-table'];
                $('#outlet-filter,#status-filter,#date-from-filter,#date-to-filter').on('change', () => table.ajax.reload());
                $('.main-content').on('click', '.action-close-session', function (event) {
                    event.preventDefault();
                    const button = this;
                    Swal.fire({ title: 'Tutup sesi?', input: 'text', inputLabel: 'Alasan (opsional)', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, tutup sesi', cancelButtonText: 'Batal' })
                        .then(r => { if (!r.isConfirmed) return; $.post(button.href, { _token: @json(csrf_token()), reason: r.value }).done(res => { showToast(res.status || 'success', res.message); table.ajax.reload(null, false); }).fail(xhr => showToast('error', xhr.responseJSON?.message || 'Gagal mengirim permintaan')); });
                });
            });
        </script>
    @endpush
    @push('css')<style>.order-table-page .card{border-radius:10px}</style>@endpush
@endsection

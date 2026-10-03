@extends('layouts.app')

@section('content')
    @php
        $outlets = App\Models\Outlets::query()
            ->whereIn('id', auth()->user()->outletIds())
            ->orderBy('name')
            ->get();
    @endphp
    <div class="main-content order-table-page">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h2 class="h4 mb-1 font-weight-bold"><i class="fa fa-qrcode me-2"></i>Meja & QR Order</h2>
                <p class="text-muted mb-0">Kelola meja, status QR, dan materi QR yang ditempatkan di outlet.</p>
            </div>
            @can('create order-table/dining-tables')
                <a href="{{ route('order-table/dining-tables/create') }}" class="btn btn-primary btn-round action">
                    <i class="fa fa-plus me-2"></i>Tambah Meja
                </a>
            @endcan
        </div>

        <div class="alert alert-info border-0 shadow-sm">
            <i class="fa fa-shield-alt me-2"></i>
            Token QR tidak ditampilkan di daftar. Rotasi token langsung membatalkan QR lama dan tidak dapat dibatalkan.
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="mb-1">Daftar Meja</h5>
                    <small class="text-muted">Gunakan filter outlet untuk mempersempit meja operasional.</small>
                </div>
                <div class="order-table-filter">
                    <label for="outlet-filter" class="form-label small mb-1">Filter outlet</label>
                    <select id="outlet-filter" class="form-select form-select-sm">
                        <option value="">Semua outlet</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0']) !!}
                </div>
            </div>
        </div>
    </div>

    @push('js')
        {!! $dataTable->scripts() !!}
        <script>
            $(function () {
                const tableId = 'dining-table-table';

                $('#outlet-filter').on('change', function () {
                    window.LaravelDataTables[tableId].ajax.reload();
                });

                handleAction(tableId);

                $('.main-content').on('click', '.delete', function (event) {
                    event.preventDefault();
                    const button = this;

                    Swal.fire({
                        title: 'Hapus meja?',
                        text: 'Meja akan dihapus dari daftar. Riwayat sesi dan order tidak ikut dihapus.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus meja',
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (!result.isConfirmed) return;

                        $.ajax({
                            url: button.href,
                            method: 'DELETE',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function (response) {
                                showToast(response.status || 'success', response.message || 'Meja berhasil dihapus');
                                window.LaravelDataTables[tableId].ajax.reload(null, false);
                            },
                            error: function (xhr) {
                                showToast('error', xhr.responseJSON?.message || 'Gagal menghapus meja');
                            }
                        });
                    });
                });

                $('.main-content').on('click', '.qr-preview', function (event) {
                    event.preventDefault();
                    handleAjax(this.href).excute();
                });

                $('.main-content').on('click', '.sensitive-action', function (event) {
                    event.preventDefault();
                    const button = this;
                    const actionType = $(button).data('action');
                    const content = actionType === 'rotate'
                        ? ['Rotasi token QR?', 'QR yang sudah dicetak akan langsung tidak berlaku.', 'Ya, rotasi token']
                        : ['Ubah status QR?', 'QR meja akan diaktifkan atau dinonaktifkan.', 'Ya, ubah status'];

                    Swal.fire({
                        title: content[0],
                        text: content[1],
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: content[2],
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (!result.isConfirmed) return;

                        $.ajax({
                            url: button.href,
                            method: 'POST',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function (response) {
                                showToast(response.status || 'success', response.message || 'QR meja diperbarui');
                                window.LaravelDataTables[tableId].ajax.reload(null, false);
                            },
                            error: function (xhr) {
                                showToast('error', xhr.responseJSON?.message || 'Gagal memperbarui QR meja');
                            }
                        });
                    });
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            .order-table-page .card { border-radius: 10px; }
            .order-table-filter { min-width: 240px; }
            .order-table-actions { display: inline-flex; flex-wrap: wrap; justify-content: center; gap: 4px; }
            .order-table-actions .btn { width: 32px; height: 32px; padding: 6px; }
        </style>
    @endpush
@endsection

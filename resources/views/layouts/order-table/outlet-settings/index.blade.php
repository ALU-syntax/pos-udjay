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
                <h2 class="h4 mb-1 font-weight-bold"><i class="fa fa-store me-2"></i>Pengaturan Order Outlet</h2>
                <p class="text-muted mb-0">Atur ketersediaan order, stok, jam operasional, biaya layanan, dan geofence outlet.</p>
            </div>
            @can('create order-table/outlet-settings')
                <a href="{{ route('order-table/outlet-settings/create') }}" class="btn btn-primary btn-round action">
                    <i class="fa fa-plus me-2"></i>Tambah Pengaturan
                </a>
            @endcan
        </div>

        <div class="alert alert-warning border-0 shadow-sm">
            <i class="fa fa-exclamation-triangle me-2"></i>
            Perubahan pause, jam operasional, dan geofence langsung memengaruhi pelanggan Order Table.
        </div>

        @can('update order-table/outlet-settings')
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-1">Domain Halaman Order (Global)</h5>
                    <small class="text-muted">Base URL yang dipakai saat membuat QR meja. Berlaku untuk semua outlet.</small>
                </div>
                <div class="card-body">
                    <form id="public-url-form" action="{{ route('order-table/outlet-settings/public-url') }}" method="POST" class="row g-3 align-items-end">
                        @csrf
                        @method('put')
                        <div class="col-md-8">
                            <label for="public_url" class="form-label">Base URL</label>
                            <input id="public_url" name="public_url" type="url" class="form-control"
                                value="{{ old('public_url', $publicUrl['value']) }}"
                                placeholder="https://order.example.com">
                            <small class="text-muted">
                                Kosongkan untuk memakai domain backoffice ({{ $publicUrl['fallback'] }}).
                                URL QR saat ini: <span class="text-break">{{ $publicUrl['resolved'] }}</span>
                            </small>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save me-1"></i>Simpan Base URL
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="mb-1">Konfigurasi per Outlet</h5>
                    <small class="text-muted">Status efektif memperhitungkan enabled, pause manual, dan jam operasional.</small>
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
                const tableId = 'outlet-setting-table';

                $('#outlet-filter').on('change', function () {
                    window.LaravelDataTables[tableId].ajax.reload();
                });

                handleAction(tableId);

                $('#public-url-form').on('submit', function (event) {
                    event.preventDefault();
                    const form = this;

                    $.ajax({
                        url: form.action,
                        method: 'POST',
                        data: $(form).serialize(),
                        success: function (response) {
                            showToast(response.status || 'success', response.message || 'Base URL QR diperbarui');
                            window.location.reload();
                        },
                        error: function (xhr) {
                            const errors = xhr.responseJSON?.errors;
                            const firstError = errors ? Object.values(errors)[0]?.[0] : null;
                            showToast('error', firstError || xhr.responseJSON?.message || 'Gagal menyimpan base URL');
                        }
                    });
                });

                $('.main-content').on('click', '.toggle-outlet-order', function (event) {
                    event.preventDefault();
                    const button = this;
                    const pausing = $(button).data('paused') !== 1;

                    Swal.fire({
                        title: pausing ? 'Pause order outlet?' : 'Buka kembali order?',
                        text: pausing
                            ? 'Pelanggan tidak dapat membuat order baru sampai outlet dibuka kembali.'
                            : 'Outlet akan kembali menerima order sesuai jam operasional.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: pausing ? 'Ya, pause order' : 'Ya, buka order',
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (!result.isConfirmed) return;

                        $.ajax({
                            url: button.href,
                            method: 'POST',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function (response) {
                                showToast(response.status || 'success', response.message || 'Status outlet diperbarui');
                                window.LaravelDataTables[tableId].ajax.reload(null, false);
                            },
                            error: function (xhr) {
                                showToast('error', xhr.responseJSON?.message || 'Gagal memperbarui status outlet');
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
            .order-table-actions { display: inline-flex; flex-wrap: wrap; justify-content: center; gap: 5px; }
            .order-table-actions .btn { width: 34px; height: 34px; padding: 7px; }
        </style>
    @endpush
@endsection

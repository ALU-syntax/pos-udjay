@extends('layouts.app')
@section('content')
    @include('layouts.reports.partials.ui-styles')

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Warehouse</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-warehouse"></i></span>
                    Inventory Lokasi
                </h1>
                <p class="ob-header__desc">Kelola lokasi penyimpanan bahan baku di gudang dan outlet beserta saldo stoknya.</p>
            </div>
            @can('create warehouse/inventory')
                <a href="{{ route('warehouse/inventory/create') }}" class="btn ob-btn ob-btn-primary action">
                    <i class="fas fa-plus" aria-hidden="true"></i> Tambah Lokasi
                </a>
            @endcan
        </div>

        {{-- Ringkasan (diperbarui dari respons simpan/arsip) --}}
        <div class="ob-stats">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-map-marker-alt"></i></span>
                <div>
                    <p class="ob-stat__label">Total Lokasi</p>
                    <p class="ob-stat__value" id="stats-total">{{ number_format($stats['total'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--success" aria-hidden="true"><i class="fas fa-check-circle"></i></span>
                <div>
                    <p class="ob-stat__label">Aktif</p>
                    <p class="ob-stat__value" id="stats-active">{{ number_format($stats['active'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--danger" aria-hidden="true"><i class="fas fa-pause-circle"></i></span>
                <div>
                    <p class="ob-stat__label">Nonaktif</p>
                    <p class="ob-stat__value" id="stats-inactive">{{ number_format($stats['inactive'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-cubes"></i></span>
                <div>
                    <p class="ob-stat__label">Berisi Stok</p>
                    <p class="ob-stat__value" id="stats-stocked">{{ number_format($stats['stocked_locations'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- Tabel --}}
        <div class="ob-card">
            <div class="ob-tabs-bar">
                <div>
                    <h2 class="ob-section-title mb-1"><i class="fas fa-list" aria-hidden="true"></i> Daftar Lokasi</h2>
                    <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Isi stok menghitung jumlah bahan; qty per bahan ada di detail lokasi.</span>
                </div>
                <div class="ob-list-filters">
                    <div class="ob-field ob-list-filter">
                        <label for="filter-type"><i class="fas fa-tag" aria-hidden="true"></i> Tipe</label>
                        <select id="filter-type" class="form-select">
                            <option value="">Semua tipe</option>
                            @foreach ($types as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}{{ $type->is_active ? '' : ' (Nonaktif)' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ob-field ob-list-filter">
                        <label for="filter-outlet"><i class="fas fa-store" aria-hidden="true"></i> Outlet</label>
                        <select id="filter-outlet" class="form-select">
                            <option value="">Semua pilihan</option>
                            <option value="all">Berlaku semua outlet</option>
                            @foreach ($outlets as $outlet)
                                <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ob-field ob-list-filter">
                        <label for="filter-brand"><i class="fas fa-tags" aria-hidden="true"></i> Brand</label>
                        <select id="filter-brand" class="form-select">
                            <option value="">Semua pilihan</option>
                            <option value="all">Berlaku semua brand</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ob-field ob-list-filter">
                        <label for="filter-status"><i class="fas fa-toggle-on" aria-hidden="true"></i> Status</label>
                        <select id="filter-status" class="form-select">
                            <option value="">Semua status</option>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="ob-table-wrap">
                {!! $dataTable->table(['class' => 'table w-100']) !!}
            </div>
        </div>
    </div>

    @push('js')
        {!! $dataTable->scripts() !!}
        <script>
            $(function () {
                const datatable = 'inventory-locations-table';
                const table = window.LaravelDataTables[datatable];

                function applyInventoryStats(data) {
                    if (!data) return;

                    const format = (value) => Number(value || 0).toLocaleString('id-ID');
                    $('#stats-total').text(format(data.total));
                    $('#stats-active').text(format(data.active));
                    $('#stats-inactive').text(format(data.inactive));
                    $('#stats-stocked').text(format(data.stocked_locations));
                }

                $('#filter-type, #filter-outlet, #filter-brand, #filter-status').on('change', function () {
                    table.ajax.reload();
                });

                handleAction(datatable, null, function (res) {
                    applyInventoryStats(res.data);
                });

                $('.main-content').on('click', '.archive-location', function (event) {
                    event.preventDefault();
                    const button = this;

                    Swal.fire({
                        title: 'Arsipkan lokasi?',
                        text: 'Lokasi ' + $(button).data('name') + ' akan diarsipkan dari daftar inventory.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, arsipkan',
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (!result.isConfirmed) return;

                        $.ajax({
                            url: button.href,
                            method: 'DELETE',
                            data: { _token: @json(csrf_token()) },
                            success: function (response) {
                                showToast(response.status || 'success', response.message || 'Lokasi inventory berhasil diarsipkan.');
                                applyInventoryStats(response.data);
                                table.ajax.reload(null, false);
                            },
                            error: function (xhr) {
                                showToast('error', xhr.responseJSON?.message || 'Gagal mengarsipkan lokasi');
                            }
                        });
                    });
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            .ob-cell-stack {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: .2rem;
            }

            .inv-strong {
                font-weight: 600;
                color: var(--ob-text);
                white-space: nowrap;
            }

            .inv-empty-count {
                font-weight: 600;
                color: var(--ob-danger);
                white-space: nowrap;
            }
        </style>
    @endpush
@endsection

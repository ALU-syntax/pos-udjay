@extends('layouts.app')
@section('content')
    @include('layouts.reports.partials.ui-styles')

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Library</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-cubes"></i></span>
                    Bahan Baku
                </h1>
                <p class="ob-header__desc">Kelola master bahan baku untuk stok, pembelian, supplier, dan konversi satuan.</p>
            </div>
            @can('create library/bahan-baku')
                <a href="{{ route('library/bahan-baku/create') }}" class="btn ob-btn ob-btn-primary action">
                    <i class="fas fa-plus" aria-hidden="true"></i> Tambah Bahan
                </a>
            @endcan
        </div>

        {{-- Ringkasan (diperbarui dari respons simpan/arsip) --}}
        <div class="ob-stats">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-cubes"></i></span>
                <div>
                    <p class="ob-stat__label">Total Bahan</p>
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
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-layer-group"></i></span>
                <div>
                    <p class="ob-stat__label">Tanpa Kategori</p>
                    <p class="ob-stat__value" id="stats-uncategorized">{{ number_format($stats['uncategorized'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- Tabel --}}
        <div class="ob-card">
            <div class="ob-tabs-bar">
                <div>
                    <h2 class="ob-section-title mb-1"><i class="fas fa-list" aria-hidden="true"></i> Daftar Bahan Baku</h2>
                    <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Pencarian mencakup nama, kode, dan catatan bahan.</span>
                </div>
                <div class="ob-list-filters">
                    <div class="ob-field ob-list-filter">
                        <label for="filter-category"><i class="fas fa-layer-group" aria-hidden="true"></i> Kategori</label>
                        <select id="filter-category" class="form-select">
                            <option value="">Semua kategori</option>
                            <option value="none">Tanpa kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}{{ $category->is_active ? '' : ' (Nonaktif)' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ob-field ob-list-filter">
                        <label for="filter-storage"><i class="fas fa-warehouse" aria-hidden="true"></i> Penyimpanan</label>
                        <select id="filter-storage" class="form-select">
                            <option value="">Semua penyimpanan</option>
                            @foreach ($storageTypes as $storageType)
                                <option value="{{ $storageType->id }}">{{ $storageType->name }}</option>
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
                const datatable = 'raw-materials-table';
                const table = window.LaravelDataTables[datatable];

                function applyRawMaterialStats(data) {
                    if (!data) return;

                    const format = (value) => Number(value || 0).toLocaleString('id-ID');
                    $('#stats-total').text(format(data.total));
                    $('#stats-active').text(format(data.active));
                    $('#stats-inactive').text(format(data.inactive));
                    $('#stats-uncategorized').text(format(data.uncategorized));
                }

                $('#filter-category, #filter-storage, #filter-status').on('change', function () {
                    table.ajax.reload();
                });

                handleAction(datatable, null, function (res) {
                    applyRawMaterialStats(res.data);
                });

                $('.main-content').on('click', '.archive-material', function (event) {
                    event.preventDefault();
                    const button = this;

                    Swal.fire({
                        title: 'Arsipkan bahan baku?',
                        text: 'Bahan ' + $(button).data('name') + ' akan diarsipkan dari daftar master.',
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
                                showToast(response.status || 'success', response.message || 'Bahan baku berhasil diarsipkan.');
                                applyRawMaterialStats(response.data);
                                table.ajax.reload(null, false);
                            },
                            error: function (xhr) {
                                showToast('error', xhr.responseJSON?.message || 'Gagal mengarsipkan bahan baku');
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

            .ob-page .bb-notes {
                display: inline-block;
                max-width: 360px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .bb-strong {
                font-weight: 600;
                color: var(--ob-text);
                white-space: nowrap;
            }
        </style>
    @endpush
@endsection

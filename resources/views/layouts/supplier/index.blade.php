@extends('layouts.app')
@section('content')
    @include('layouts.reports.partials.ui-styles')

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Warehouse</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-truck"></i></span>
                    Supplier
                </h1>
                <p class="ob-header__desc">Kelola pemasok, cara pembelian, jadwal operasional, dan bahan baku yang mereka sediakan.</p>
            </div>
            @can('create warehouse/supplier')
                <a href="{{ route('warehouse/supplier/create') }}" class="btn ob-btn ob-btn-primary action">
                    <i class="fas fa-plus" aria-hidden="true"></i> Tambah Supplier
                </a>
            @endcan
        </div>

        {{-- Ringkasan (diperbarui dari respons simpan & hapus) --}}
        <div class="ob-stats">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-building"></i></span>
                <div>
                    <p class="ob-stat__label">Total Supplier</p>
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
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-boxes"></i></span>
                <div>
                    <p class="ob-stat__label">Bahan Baku Terhubung</p>
                    <p class="ob-stat__value" id="stats-supplied-materials">{{ number_format($stats['supplied_materials'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- Tabel --}}
        <div class="ob-card">
            <div class="ob-tabs-bar">
                <div>
                    <h2 class="ob-section-title mb-1"><i class="fas fa-list" aria-hidden="true"></i> Daftar Supplier</h2>
                    <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Hanya supplier aktif yang bisa dipilih saat membuat procurement plan.</span>
                </div>
                <div class="ob-list-filters">
                    <div class="ob-field ob-list-filter">
                        <label for="filter-status"><i class="fas fa-toggle-on" aria-hidden="true"></i> Status</label>
                        <select id="filter-status" class="form-select">
                            <option value="">Semua status</option>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                    <div class="ob-field ob-list-filter">
                        <label for="filter-mode"><i class="fas fa-shopping-cart" aria-hidden="true"></i> Mode Pembelian</label>
                        <select id="filter-mode" class="form-select">
                            <option value="">Semua mode</option>
                            @foreach (\App\Enums\ProcurementMode::options() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
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
                const datatable = 'supplier-table';
                const table = window.LaravelDataTables[datatable];

                function applySupplierStats(data) {
                    if (!data) return;

                    const format = (value) => Number(value || 0).toLocaleString('id-ID');
                    $('#stats-total').text(format(data.total));
                    $('#stats-active').text(format(data.active));
                    $('#stats-inactive').text(format(data.inactive));
                    $('#stats-supplied-materials').text(format(data.supplied_materials));
                }

                $('#filter-status, #filter-mode').on('change', function () {
                    table.ajax.reload();
                });

                handleAction(datatable, null, function (res) {
                    applySupplierStats(res.data);
                });

                $('.main-content').on('click', '.delete-supplier', function (event) {
                    event.preventDefault();
                    const button = this;

                    Swal.fire({
                        title: 'Hapus supplier?',
                        text: 'Supplier ' + $(button).data('name') + ' akan dihapus dari daftar.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (!result.isConfirmed) return;

                        $.ajax({
                            url: button.href,
                            method: 'DELETE',
                            data: { _token: @json(csrf_token()) },
                            success: function (response) {
                                showToast(response.status || 'success', response.message || 'Supplier berhasil dihapus.');
                                applySupplierStats(response.data);
                                table.ajax.reload(null, false);
                            },
                            error: function (xhr) {
                                showToast('error', xhr.responseJSON?.message || 'Gagal menghapus supplier');
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
                white-space: nowrap;
            }

            .sp-strong {
                font-weight: 600;
                color: var(--ob-text);
                white-space: nowrap;
            }

            .sp-truncate {
                display: inline-block;
                max-width: 220px;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .ob-page .sp-mode {
                gap: .35rem;
                font-weight: 600;
                white-space: nowrap;
            }

            .ob-page .sp-mode i {
                font-size: .72rem;
            }

            /* 1 = Offline, 2 = Online, 3 = Online & Offline (App\Enums\ProcurementMode) */
            .ob-page .sp-mode--1 {
                background: #fef3c7;
                border-color: #fde68a;
                color: #92400e;
            }

            .ob-page .sp-mode--2 {
                background: var(--ob-primary-soft);
                border-color: #cfe2fb;
                color: var(--ob-primary);
            }

            .ob-page .sp-mode--3 {
                background: var(--ob-success-soft);
                border-color: #bbf7d0;
                color: var(--ob-success);
            }

            .ob-page .sp-chip-more {
                color: var(--ob-muted);
            }
        </style>
    @endpush
@endsection

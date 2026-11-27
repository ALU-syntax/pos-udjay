@extends('layouts.app')
@section('content')
    @include('layouts.reports.partials.ui-styles')

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Library</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-layer-group"></i></span>
                    Kategori Bahan Baku
                </h1>
                <p class="ob-header__desc">Kelola pengelompokan bahan baku agar pencarian, pembelian, dan stok lebih rapi.</p>
            </div>
            @can('create library/category-bahan-baku')
                <a href="{{ route('library/category-bahan-baku/create') }}" class="btn ob-btn ob-btn-primary action">
                    <i class="fas fa-plus" aria-hidden="true"></i> Tambah Kategori
                </a>
            @endcan
        </div>

        {{-- Ringkasan (diperbarui dari respons simpan) --}}
        <div class="ob-stats">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-layer-group"></i></span>
                <div>
                    <p class="ob-stat__label">Total Kategori</p>
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
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-inbox"></i></span>
                <div>
                    <p class="ob-stat__label">Belum Dipakai</p>
                    <p class="ob-stat__value" id="stats-empty">{{ number_format($stats['empty'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- Tabel --}}
        <div class="ob-card">
            <div class="ob-tabs-bar">
                <div>
                    <h2 class="ob-section-title mb-1"><i class="fas fa-list" aria-hidden="true"></i> Daftar Kategori</h2>
                    <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Jumlah bahan hanya menghitung bahan baku yang belum dihapus.</span>
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
                        <label for="filter-usage"><i class="fas fa-cubes" aria-hidden="true"></i> Pemakaian</label>
                        <select id="filter-usage" class="form-select">
                            <option value="">Semua pemakaian</option>
                            <option value="used">Sudah dipakai</option>
                            <option value="empty">Belum dipakai</option>
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
                const datatable = 'raw-material-categories-table';
                const table = window.LaravelDataTables[datatable];

                function applyCategoryStats(data) {
                    if (!data) return;

                    const format = (value) => Number(value || 0).toLocaleString('id-ID');
                    $('#stats-total').text(format(data.total));
                    $('#stats-active').text(format(data.active));
                    $('#stats-inactive').text(format(data.inactive));
                    $('#stats-empty').text(format(data.empty));
                }

                $('#filter-status, #filter-usage').on('change', function () {
                    table.ajax.reload();
                });

                handleAction(datatable, null, function (res) {
                    applyCategoryStats(res.data);
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

            .ob-page .kb-notes {
                display: inline-block;
                max-width: 360px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .kb-strong {
                font-weight: 600;
                color: var(--ob-text);
                white-space: nowrap;
            }
        </style>
    @endpush
@endsection

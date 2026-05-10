@extends('layouts.app')
@section('content')
    <div class="main-content raw-material-page">
        <div class="mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h2 class="h4 mb-0 font-weight-bold">
                        <i class="fa fa-cubes me-2"></i>Bahan Baku
                    </h2>
                    <p class="text-muted small mt-1 mb-0">Kelola master bahan baku untuk stok, pembelian, supplier, dan konversi satuan.</p>
                </div>
                @can('create library/bahan-baku')
                    <a href="{{ route('library/bahan-baku/create') }}" class="btn btn-primary btn-round action">
                        <i class="fa fa-plus me-2"></i>Tambah Bahan
                    </a>
                @endcan
            </div>

            <div class="row g-3">
                <div class="col-md-4 col-xl-4">
                    <div class="card material-stat-card stat-blue h-100">
                        <div class="card-body">
                            <div class="material-stat-top">
                                <span class="material-stat-icon"><i class="fa fa-cubes"></i></span>
                                <span class="material-stat-chip">Master</span>
                            </div>
                            <p class="text-muted small mb-1">Total Bahan</p>
                            <h3 class="mb-1 font-weight-bold" id="stats-total">{{ $stats['total'] ?? 0 }}</h3>
                            <small class="text-muted">
                                <span id="stats-active-inline">{{ $stats['active'] ?? 0 }} aktif</span> /
                                <span id="stats-inactive-inline">{{ $stats['inactive'] ?? 0 }} nonaktif</span>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-xl-4">
                    <div class="card material-stat-card stat-green h-100">
                        <div class="card-body">
                            <div class="material-stat-top">
                                <span class="material-stat-icon"><i class="fa fa-toggle-on"></i></span>
                                <span class="material-stat-chip">Aktif</span>
                            </div>
                            <p class="text-muted small mb-1">Siap Digunakan</p>
                            <h3 class="mb-1 font-weight-bold text-success" id="stats-active">{{ $stats['active'] ?? 0 }}</h3>
                            <small class="text-muted">Bisa dipilih pada stok, supplier, dan transaksi gudang.</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-xl-4">
                    <div class="card material-stat-card stat-rose h-100">
                        <div class="card-body">
                            <div class="material-stat-top">
                                <span class="material-stat-icon"><i class="fa fa-layer-group"></i></span>
                                <span class="material-stat-chip">Kategori</span>
                            </div>
                            <p class="text-muted small mb-1">Tanpa Kategori</p>
                            <h3 class="mb-1 font-weight-bold text-danger" id="stats-uncategorized">{{ $stats['uncategorized'] ?? 0 }}</h3>
                            <small class="text-muted">
                                <span id="stats-categorized">{{ $stats['categorized'] ?? 0 }}</span> bahan sudah berkategori.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="material-insight-card mb-3">
            <div class="material-insight-copy">
                <span class="material-insight-icon"><i class="fa fa-clipboard-check"></i></span>
                <div>
                    <h6 class="mb-1">Kontrol Master Bahan</h6>
                    <p class="mb-0 text-muted small">
                        @if ($latestMaterial)
                            Update terakhir: <strong>{{ $latestMaterial->name }}</strong> pada {{ $latestMaterial->updated_at->format('d M Y H:i') }}.
                        @else
                            Belum ada bahan baku. Tambahkan bahan pertama untuk mulai membangun master gudang.
                        @endif
                    </p>
                </div>
            </div>
            <div class="material-insight-meter">
                @php
                    $coverage = ($stats['total'] ?? 0) > 0 ? round((($stats['categorized'] ?? 0) / $stats['total']) * 100) : 0;
                @endphp
                <span id="stats-category-coverage">{{ $coverage }}%</span>
                <small class="text-muted">berkategori</small>
            </div>
        </div>

        <div class="material-table-shell shadow-sm">
            <div class="material-table-toolbar">
                <div>
                    <h5 class="mb-1">Daftar Bahan Baku</h5>
                    <small class="text-muted">Filter bahan berdasarkan kategori, penyimpanan, status, dan kebutuhan stok.</small>
                </div>
                <div class="material-toolbar-actions">
                    <select id="filterCategory" class="form-select form-select-sm bg-light">
                        <option value="">Semua Kategori</option>
                        <option value="__empty__">Tanpa Kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->name }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <select id="filterStorage" class="form-select form-select-sm bg-light">
                        <option value="">Semua Penyimpanan</option>
                        @foreach ($storageTypes as $storageType)
                            <option value="{{ $storageType->name }}">{{ $storageType->name }}</option>
                        @endforeach
                    </select>

                    <select id="filterStatus" class="form-select form-select-sm bg-light">
                        <option value="">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Tidak Aktif</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive material-table-wrap">
                {!! $dataTable->table(['class' => 'table table-hover table-sm mb-0 align-middle']) !!}
            </div>
        </div>
    </div>

    @push('js')
        {!! $dataTable->scripts() !!}

        <script>
            const datatable = 'raw-materials-table';

            function applyRawMaterialStats(data) {
                if (!data) {
                    return;
                }

                $('#stats-total').text(data.total ?? 0);
                $('#stats-active').text(data.active ?? 0);
                $('#stats-uncategorized').text(data.uncategorized ?? 0);
                $('#stats-categorized').text(data.categorized ?? 0);
                $('#stats-active-inline').text((data.active ?? 0) + ' aktif');
                $('#stats-inactive-inline').text((data.inactive ?? 0) + ' nonaktif');

                const total = Number(data.total ?? 0);
                const categorized = Number(data.categorized ?? 0);
                $('#stats-category-coverage').text(total > 0 ? Math.round((categorized / total) * 100) + '%' : '0%');
            }

            $(document).ready(function() {
                if ($.fn.DataTable.isDataTable('#' + datatable)) {
                    const table = $('#' + datatable).DataTable();

                    $('#filterCategory').on('change', function() {
                        table.column('3:visible').search(this.value).draw();
                    });

                    $('#filterStorage').on('change', function() {
                        table.column('5:visible').search(this.value).draw();
                    });

                    $('#filterStatus').on('change', function() {
                        table.column('7:visible').search(this.value).draw();
                    });

                    const wrapper = $('#' + datatable).closest('.dataTables_wrapper');
                    wrapper.find('.dataTables_length, .dataTables_filter').closest('.row').addClass('mx-3');
                    wrapper.find('.dataTables_info, .dataTables_paginate').closest('.row').addClass('mx-3');
                }

                handleAction(datatable, null, function(res) {
                    applyRawMaterialStats(res.data);
                });

                handleDelete(datatable, 'Bahan baku akan diarsipkan dari daftar master.', function(res) {
                    applyRawMaterialStats(res.data);
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            .raw-material-page .card {
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
            }

            .material-stat-card {
                overflow: hidden;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .material-stat-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 26px rgba(18, 38, 63, 0.08);
            }

            .material-stat-card::before {
                content: "";
                display: block;
                height: 4px;
            }

            .stat-blue::before {
                background: #3157c9;
            }

            .stat-green::before {
                background: #15965f;
            }

            .stat-amber::before {
                background: #d89012;
            }

            .stat-rose::before {
                background: #d94d5f;
            }

            .material-stat-top {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
                margin-bottom: 14px;
            }

            .material-stat-icon,
            .material-insight-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 36px;
                height: 36px;
                border-radius: 8px;
                background: #f3f6fb;
                color: #2f3b52;
            }

            .material-stat-chip {
                display: inline-flex;
                align-items: center;
                min-height: 24px;
                padding: 3px 9px;
                border-radius: 999px;
                background: #f7f8fa;
                color: #667085;
                font-size: 12px;
                font-weight: 600;
            }

            .material-insight-card,
            .material-table-shell {
                background: #fff;
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
            }

            .material-insight-card {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 18px;
                padding: 16px 18px;
            }

            .material-insight-copy {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .material-insight-meter {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                min-width: 110px;
            }

            .material-insight-meter span {
                color: #3157c9;
                font-size: 24px;
                font-weight: 800;
                line-height: 1;
            }

            .material-table-shell {
                overflow: hidden;
            }

            .material-table-toolbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
                padding: 18px;
                background: linear-gradient(180deg, #fbfcfe 0%, #ffffff 100%);
                border-bottom: 1px solid rgba(18, 38, 63, 0.08);
            }

            .material-toolbar-actions {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 8px;
                flex-wrap: wrap;
            }

            .material-toolbar-actions .form-select {
                min-width: 160px;
                border-color: rgba(18, 38, 63, 0.08);
            }

            .material-table-wrap {
                padding: 18px 0;
            }

            .unit-pill,
            .storage-pill {
                display: inline-flex;
                align-items: center;
                min-height: 26px;
                padding: 3px 9px;
                border-radius: 999px;
                font-weight: 700;
                white-space: nowrap;
            }

            .unit-pill {
                background: #eef4ff;
                color: #3157c9;
            }

            .storage-pill {
                background: #ecfdf3;
                color: #147a4b;
            }

            #raw-materials-table tbody tr:hover {
                background-color: rgba(49, 87, 201, 0.04);
            }

            @media (max-width: 991.98px) {
                .material-table-toolbar {
                    align-items: stretch;
                    flex-direction: column;
                }

                .material-toolbar-actions {
                    justify-content: stretch;
                }
            }

            @media (max-width: 767.98px) {
                .material-insight-card {
                    align-items: stretch;
                    flex-direction: column;
                }

                .material-insight-meter {
                    align-items: flex-start;
                }

                .material-toolbar-actions .form-select {
                    width: 100%;
                    min-width: 0;
                }
            }
        </style>
    @endpush
@endsection

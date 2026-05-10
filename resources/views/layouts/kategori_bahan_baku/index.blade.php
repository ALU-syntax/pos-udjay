@extends('layouts.app')
@section('content')
    <div class="main-content raw-category-page">
        <div class="raw-category-header mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h2 class="h4 mb-0 font-weight-bold">
                        <i class="fa fa-layer-group me-2"></i>Kategori Bahan Baku
                    </h2>
                    <p class="text-muted small mt-1 mb-0">Kelola pengelompokan bahan baku agar pencarian, pembelian, dan stok lebih rapi.</p>
                </div>
                @can('create library/category-bahan-baku')
                    <a href="{{ route('library/category-bahan-baku/create') }}" class="btn btn-primary btn-round action">
                        <i class="fa fa-plus me-2"></i>Tambah Kategori
                    </a>
                @endcan
            </div>

            <div class="row g-3">
                <div class="col-md-6 col-xl-3">
                    <div class="card raw-stat-card stat-indigo h-100">
                        <div class="card-body">
                            <div class="raw-stat-top">
                                <span class="raw-stat-icon"><i class="fa fa-boxes"></i></span>
                                <span class="raw-stat-chip">Master</span>
                            </div>
                            <p class="text-muted small mb-1">Total Kategori</p>
                            <h3 class="mb-1 font-weight-bold" id="stats-total">{{ $stats['total'] ?? 0 }}</h3>
                            <small class="text-muted">
                                <span id="stats-active-inline">{{ $stats['active'] ?? 0 }} aktif</span> /
                                <span id="stats-inactive-inline">{{ $stats['inactive'] ?? 0 }} nonaktif</span>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card raw-stat-card stat-emerald h-100">
                        <div class="card-body">
                            <div class="raw-stat-top">
                                <span class="raw-stat-icon"><i class="fa fa-check-circle"></i></span>
                                <span class="raw-stat-chip">Aktif</span>
                            </div>
                            <p class="text-muted small mb-1">Siap Dipakai</p>
                            <h3 class="mb-1 font-weight-bold text-success" id="stats-active">{{ $stats['active'] ?? 0 }}</h3>
                            <small class="text-muted">Kategori yang bisa dipilih untuk bahan baku baru.</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card raw-stat-card stat-amber h-100">
                        <div class="card-body">
                            <div class="raw-stat-top">
                                <span class="raw-stat-icon"><i class="fa fa-link"></i></span>
                                <span class="raw-stat-chip">Terpakai</span>
                            </div>
                            <p class="text-muted small mb-1">Kategori Digunakan</p>
                            <h3 class="mb-1 font-weight-bold text-warning" id="stats-used">{{ $stats['used'] ?? 0 }}</h3>
                            <small class="text-muted">
                                <span id="stats-raw-material-total">{{ $stats['raw_material_total'] ?? 0 }}</span> bahan baku sudah tercatat.
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card raw-stat-card stat-rose h-100">
                        <div class="card-body">
                            <div class="raw-stat-top">
                                <span class="raw-stat-icon"><i class="fa fa-inbox"></i></span>
                                <span class="raw-stat-chip">Kosong</span>
                            </div>
                            <p class="text-muted small mb-1">Belum Dipakai</p>
                            <h3 class="mb-1 font-weight-bold text-danger" id="stats-empty">{{ $stats['empty'] ?? 0 }}</h3>
                            <small class="text-muted">Bisa dinonaktifkan atau dilengkapi dengan bahan baku.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="raw-insight-card mb-3">
            <div class="raw-insight-copy">
                <span class="raw-insight-icon"><i class="fa fa-lightbulb"></i></span>
                <div>
                    <h6 class="mb-1">Ringkasan Operasional</h6>
                    <p class="mb-0 text-muted small">
                        @if ($latestCategory)
                            Pembaruan terakhir: <strong>{{ $latestCategory->name }}</strong> pada {{ $latestCategory->updated_at->format('d M Y H:i') }}.
                        @else
                            Belum ada kategori bahan baku. Tambahkan kategori pertama untuk mulai merapikan master bahan.
                        @endif
                    </p>
                </div>
            </div>
            <div class="raw-insight-meter">
                @php
                    $coverage = ($stats['total'] ?? 0) > 0 ? round((($stats['used'] ?? 0) / $stats['total']) * 100) : 0;
                @endphp
                <span id="stats-coverage">{{ $coverage }}%</span>
                <small class="text-muted">kategori terpakai</small>
            </div>
        </div>

        <div class="raw-table-shell shadow-sm">
            <div class="raw-table-toolbar">
                <div>
                    <h5 class="mb-1">Daftar Kategori</h5>
                    <small class="text-muted">Gunakan filter untuk melihat kategori aktif, nonaktif, terpakai, atau belum dipakai.</small>
                </div>
                <div class="raw-toolbar-actions">
                    <select id="filterStatus" class="form-select form-select-sm bg-light">
                        <option value="">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Tidak Aktif</option>
                    </select>
                    <select id="filterUsage" class="form-select form-select-sm bg-light">
                        <option value="">Semua Pemakaian</option>
                        <option value="used">Sudah Dipakai</option>
                        <option value="empty">Belum Dipakai</option>
                    </select>
                    @can('create library/category-bahan-baku')
                        <a href="{{ route('library/category-bahan-baku/create') }}" class="btn btn-primary btn-sm action">
                            <i class="fa fa-plus me-1"></i> Kategori Baru
                        </a>
                    @endcan
                </div>
            </div>

            <div class="table-responsive raw-table-wrap">
                {!! $dataTable->table(['class' => 'table table-hover table-sm mb-0 align-middle']) !!}
            </div>
        </div>
    </div>

    @push('js')
        {!! $dataTable->scripts() !!}

        <script>
            const datatable = 'raw-material-categories-table';

            function applyRawCategoryStats(data) {
                if (!data) {
                    return;
                }

                $('#stats-total').text(data.total ?? 0);
                $('#stats-active').text(data.active ?? 0);
                $('#stats-used').text(data.used ?? 0);
                $('#stats-empty').text(data.empty ?? 0);
                $('#stats-active-inline').text((data.active ?? 0) + ' aktif');
                $('#stats-inactive-inline').text((data.inactive ?? 0) + ' nonaktif');
                $('#stats-raw-material-total').text(data.raw_material_total ?? 0);

                const total = Number(data.total ?? 0);
                const used = Number(data.used ?? 0);
                $('#stats-coverage').text(total > 0 ? Math.round((used / total) * 100) + '%' : '0%');
            }

            $(document).ready(function() {
                if ($.fn.DataTable.isDataTable('#' + datatable)) {
                    const table = $('#' + datatable).DataTable();

                    $('#filterStatus').on('change', function() {
                        table.column('3:visible').search(this.value).draw();
                    });

                    $('#filterUsage').on('change', function() {
                        table.column('2:visible').search(this.value).draw();
                    });

                    const wrapper = $('#' + datatable).closest('.dataTables_wrapper');
                    wrapper.find('.dataTables_length, .dataTables_filter').closest('.row').addClass('mx-3');
                    wrapper.find('.dataTables_info, .dataTables_paginate').closest('.row').addClass('mx-3');
                }

                handleAction(datatable, null, function(res) {
                    applyRawCategoryStats(res.data);
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            .raw-category-page .card {
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
            }

            .raw-stat-card {
                overflow: hidden;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .raw-stat-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 26px rgba(18, 38, 63, 0.08);
            }

            .raw-stat-card::before {
                content: "";
                display: block;
                height: 4px;
            }

            .stat-indigo::before {
                background: #3157c9;
            }

            .stat-emerald::before {
                background: #15965f;
            }

            .stat-amber::before {
                background: #d89012;
            }

            .stat-rose::before {
                background: #d94d5f;
            }

            .raw-stat-top {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
                margin-bottom: 14px;
            }

            .raw-stat-icon,
            .raw-insight-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 36px;
                height: 36px;
                border-radius: 8px;
                background: #f3f6fb;
                color: #2f3b52;
            }

            .raw-stat-chip {
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

            .raw-insight-card,
            .raw-table-shell {
                background: #fff;
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
            }

            .raw-insight-card {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 18px;
                padding: 16px 18px;
            }

            .raw-insight-copy {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .raw-insight-meter {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                min-width: 110px;
            }

            .raw-insight-meter span {
                color: #3157c9;
                font-size: 24px;
                font-weight: 800;
                line-height: 1;
            }

            .raw-table-shell {
                overflow: hidden;
            }

            .raw-table-toolbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
                padding: 18px;
                background: linear-gradient(180deg, #fbfcfe 0%, #ffffff 100%);
                border-bottom: 1px solid rgba(18, 38, 63, 0.08);
            }

            .raw-toolbar-actions {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 8px;
                flex-wrap: wrap;
            }

            .raw-toolbar-actions .form-select {
                min-width: 180px;
                border-color: rgba(18, 38, 63, 0.08);
            }

            .raw-table-wrap {
                padding: 18px 0;
            }

            .material-count-pill {
                display: inline-flex;
                align-items: center;
                min-height: 28px;
                padding: 4px 10px;
                border-radius: 999px;
                background: #eef4ff;
                color: #3157c9;
                font-weight: 700;
                white-space: nowrap;
            }

            #raw-material-categories-table tbody tr:hover {
                background-color: rgba(49, 87, 201, 0.04);
            }

            @media (max-width: 767.98px) {
                .raw-insight-card,
                .raw-table-toolbar {
                    align-items: stretch;
                    flex-direction: column;
                }

                .raw-insight-meter {
                    align-items: flex-start;
                }

                .raw-toolbar-actions {
                    justify-content: stretch;
                }

                .raw-toolbar-actions .btn,
                .raw-toolbar-actions .form-select {
                    width: 100%;
                    min-width: 0;
                }
            }
        </style>
    @endpush
@endsection

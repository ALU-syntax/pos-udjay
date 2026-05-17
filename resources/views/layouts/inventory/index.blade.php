@extends('layouts.app')
@section('content')
    <div class="main-content inventory-page">
        <div class="mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h2 class="h4 mb-0 font-weight-bold">
                        <i class="fa fa-warehouse me-2"></i>Inventory Lokasi
                    </h2>
                    <p class="text-muted small mt-1 mb-0">Kelola struktur lokasi penyimpanan dan distribusi bahan baku antar gudang/outlet.</p>
                </div>
                @can('create warehouse/inventory')
                    <a href="{{ route('warehouse/inventory/create') }}" class="btn btn-primary btn-round action">
                        <i class="fa fa-plus me-2"></i>Tambah Lokasi
                    </a>
                @endcan
            </div>

            <div class="row g-3">
                <div class="col-md-6 col-xl-3">
                    <div class="card inv-stat-card stat-ocean h-100">
                        <div class="card-body">
                            <div class="inv-stat-top">
                                <span class="inv-stat-icon"><i class="fa fa-map-marker-alt"></i></span>
                                <span class="inv-stat-chip">Lokasi</span>
                            </div>
                            <p class="text-muted small mb-1">Total Inventory</p>
                            <h3 class="mb-1 font-weight-bold" id="stats-total">{{ $stats['total'] ?? 0 }}</h3>
                            <small class="text-muted">
                                <span id="stats-active-inline">{{ $stats['active'] ?? 0 }} aktif</span> /
                                <span id="stats-inactive-inline">{{ $stats['inactive'] ?? 0 }} nonaktif</span>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card inv-stat-card stat-mint h-100">
                        <div class="card-body">
                            <div class="inv-stat-top">
                                <span class="inv-stat-icon"><i class="fa fa-store"></i></span>
                                <span class="inv-stat-chip">Outlet</span>
                            </div>
                            <p class="text-muted small mb-1">Lokasi Terhubung Outlet</p>
                            <h3 class="mb-1 font-weight-bold text-success" id="stats-outlet">{{ $stats['assigned_outlet'] ?? 0 }}</h3>
                            <small class="text-muted">Lokasi yang punya relasi outlet spesifik.</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card inv-stat-card stat-amber h-100">
                        <div class="card-body">
                            <div class="inv-stat-top">
                                <span class="inv-stat-icon"><i class="fa fa-tags"></i></span>
                                <span class="inv-stat-chip">Brand</span>
                            </div>
                            <p class="text-muted small mb-1">Lokasi Terhubung Brand</p>
                            <h3 class="mb-1 font-weight-bold text-warning" id="stats-brand">{{ $stats['assigned_brand'] ?? 0 }}</h3>
                            <small class="text-muted">Membantu segmentasi stok antar brand.</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card inv-stat-card stat-violet h-100">
                        <div class="card-body">
                            <div class="inv-stat-top">
                                <span class="inv-stat-icon"><i class="fa fa-cubes"></i></span>
                                <span class="inv-stat-chip">Stok</span>
                            </div>
                            <p class="text-muted small mb-1">Lokasi Berisi Bahan</p>
                            <h3 class="mb-1 font-weight-bold text-info" id="stats-stocked">{{ $stats['stocked_locations'] ?? 0 }}</h3>
                            <small class="text-muted">Sudah ada bahan baku yang tersimpan.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="inv-insight-card mb-3">
            <div class="inv-insight-copy">
                <span class="inv-insight-icon"><i class="fa fa-lightbulb"></i></span>
                <div>
                    <h6 class="mb-1">Struktur Lokasi Inventory</h6>
                    <p class="mb-0 text-muted small">
                        @if ($latestInventory)
                            Lokasi terakhir diperbarui: <strong>{{ $latestInventory->name }}</strong> pada {{ $latestInventory->updated_at->format('d M Y H:i') }}.
                        @else
                            Belum ada lokasi inventory. Tambahkan lokasi pertama untuk mulai manajemen stok per titik.
                        @endif
                    </p>
                </div>
            </div>
            <div class="inv-insight-meter">
                @php
                    $coverage = ($stats['total'] ?? 0) > 0 ? round((($stats['stocked_locations'] ?? 0) / $stats['total']) * 100) : 0;
                @endphp
                <span id="stats-stock-coverage">{{ $coverage }}%</span>
                <small class="text-muted">sudah berisi stok</small>
            </div>
        </div>

        <div class="inv-table-shell shadow-sm">
            <div class="inv-table-toolbar">
                <div>
                    <h5 class="mb-1">Daftar Lokasi Inventory</h5>
                    <small class="text-muted">Filter berdasarkan tipe lokasi, parent, outlet, brand, dan status aktif.</small>
                </div>
                <div class="inv-toolbar-actions">
                    <select id="filterType" class="form-select form-select-sm bg-light">
                        <option value="">Semua Tipe</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->name }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                    <select id="filterParent" class="form-select form-select-sm bg-light">
                        <option value="">Semua Parent</option>
                        <option value="__empty__">Tanpa parent</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->name }}">{{ $parent->name }}</option>
                        @endforeach
                    </select>
                    <select id="filterOutlet" class="form-select form-select-sm bg-light">
                        <option value="">Semua Outlet</option>
                        <option value="__empty__">Semua outlet</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->name }}">{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                    <select id="filterBrand" class="form-select form-select-sm bg-light">
                        <option value="">Semua Brand</option>
                        <option value="__empty__">Semua brand</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->name }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                    <select id="filterStatus" class="form-select form-select-sm bg-light">
                        <option value="">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Tidak Aktif</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive inv-table-wrap">
                {!! $dataTable->table(['class' => 'table table-hover table-sm mb-0 align-middle']) !!}
            </div>
        </div>
    </div>

    @push('js')
        {!! $dataTable->scripts() !!}

        <script>
            const datatable = 'inventory-locations-table';

            function applyInventoryStats(data) {
                if (!data) {
                    return;
                }

                $('#stats-total').text(data.total ?? 0);
                $('#stats-outlet').text(data.assigned_outlet ?? 0);
                $('#stats-brand').text(data.assigned_brand ?? 0);
                $('#stats-stocked').text(data.stocked_locations ?? 0);
                $('#stats-active-inline').text((data.active ?? 0) + ' aktif');
                $('#stats-inactive-inline').text((data.inactive ?? 0) + ' nonaktif');

                const total = Number(data.total ?? 0);
                const stocked = Number(data.stocked_locations ?? 0);
                $('#stats-stock-coverage').text(total > 0 ? Math.round((stocked / total) * 100) + '%' : '0%');
            }

            $(document).ready(function() {
                if ($.fn.DataTable.isDataTable('#' + datatable)) {
                    const table = $('#' + datatable).DataTable();

                    $('#filterType').on('change', function() {
                        table.column('3:visible').search(this.value).draw();
                    });
                    $('#filterParent').on('change', function() {
                        table.column('4:visible').search(this.value).draw();
                    });
                    $('#filterOutlet').on('change', function() {
                        table.column('5:visible').search(this.value).draw();
                    });
                    $('#filterBrand').on('change', function() {
                        table.column('6:visible').search(this.value).draw();
                    });
                    $('#filterStatus').on('change', function() {
                        table.column('8:visible').search(this.value).draw();
                    });

                    const wrapper = $('#' + datatable).closest('.dataTables_wrapper');
                    wrapper.find('.dataTables_length, .dataTables_filter').closest('.row').addClass('mx-3');
                    wrapper.find('.dataTables_info, .dataTables_paginate').closest('.row').addClass('mx-3');
                }

                handleAction(datatable, null, function(res) {
                    applyInventoryStats(res.data);
                });

                handleDelete(datatable, 'Lokasi inventory akan diarsipkan dari daftar.', function(res) {
                    applyInventoryStats(res.data);
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            .inventory-page .card {
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
            }

            .inv-stat-card {
                overflow: hidden;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .inv-stat-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 26px rgba(18, 38, 63, 0.08);
            }

            .inv-stat-card::before {
                content: "";
                display: block;
                height: 4px;
            }

            .stat-ocean::before {
                background: #1b6ec2;
            }

            .stat-mint::before {
                background: #119f7a;
            }

            .stat-amber::before {
                background: #d89012;
            }

            .stat-violet::before {
                background: #6168d9;
            }

            .inv-stat-top {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
                margin-bottom: 14px;
            }

            .inv-stat-icon,
            .inv-insight-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 36px;
                height: 36px;
                border-radius: 8px;
                background: #f3f6fb;
                color: #2f3b52;
            }

            .inv-stat-chip {
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

            .inv-insight-card,
            .inv-table-shell {
                background: #fff;
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
            }

            .inv-insight-card {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 18px;
                padding: 16px 18px;
            }

            .inv-insight-copy {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .inv-insight-meter {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                min-width: 110px;
            }

            .inv-insight-meter span {
                color: #1b6ec2;
                font-size: 24px;
                font-weight: 800;
                line-height: 1;
            }

            .inv-table-shell {
                overflow: hidden;
            }

            .inv-table-toolbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
                padding: 18px;
                background: linear-gradient(180deg, #fbfcfe 0%, #ffffff 100%);
                border-bottom: 1px solid rgba(18, 38, 63, 0.08);
            }

            .inv-toolbar-actions {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 8px;
                flex-wrap: wrap;
            }

            .inv-toolbar-actions .form-select {
                min-width: 150px;
                border-color: rgba(18, 38, 63, 0.08);
            }

            .inv-table-wrap {
                padding: 18px 0;
            }

            .inv-tag {
                display: inline-flex;
                align-items: center;
                min-height: 24px;
                padding: 3px 9px;
                border-radius: 999px;
                font-weight: 700;
                white-space: nowrap;
                font-size: 12px;
            }

            .inv-tag-type {
                background: #edf3ff;
                color: #204aa8;
            }

            .inv-stock-meta {
                display: flex;
                flex-direction: column;
                gap: 2px;
            }

            #inventory-locations-table tbody tr:hover {
                background-color: rgba(27, 110, 194, 0.04);
            }

            @media (max-width: 991.98px) {
                .inv-table-toolbar {
                    align-items: stretch;
                    flex-direction: column;
                }

                .inv-toolbar-actions {
                    justify-content: stretch;
                }
            }

            @media (max-width: 767.98px) {
                .inv-insight-card {
                    align-items: stretch;
                    flex-direction: column;
                }

                .inv-insight-meter {
                    align-items: flex-start;
                }

                .inv-toolbar-actions .form-select {
                    width: 100%;
                }
            }
        </style>
    @endpush
@endsection

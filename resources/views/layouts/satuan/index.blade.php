@extends('layouts.app')
@section('content')
    @php
        $canCreateConversion = $rawMaterials->isNotEmpty() && $units->count() >= 2;
    @endphp

    <div class="main-content">
        <div class="mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h2 class="h4 mb-0 font-weight-bold">
                        <i class="fa fa-balance-scale me-2"></i>Manajemen Satuan
                    </h2>
                    <p class="text-muted small mt-1 mb-0">Kelola master satuan dan aturan konversi khusus per bahan baku.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('library/satuan/create') }}" class="btn btn-primary btn-round action">
                        <i class="fa fa-plus me-2"></i>Tambah Satuan
                    </a>
                    @if ($canCreateConversion)
                        <a href="{{ route('library/satuan/conversions/create') }}" class="btn btn-outline-primary btn-round action">
                            <i class="fa fa-random me-2"></i>Tambah Konversi
                        </a>
                    @else
                        <button type="button" class="btn btn-outline-secondary btn-round" disabled>
                            <i class="fa fa-random me-2"></i>Tambah Konversi
                        </button>
                    @endif
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6 col-xl-3">
                    <div class="card unit-stat-card stat-blue h-100">
                        <div class="card-body">
                            <div class="unit-stat-top">
                                <span class="unit-stat-icon"><i class="fa fa-balance-scale"></i></span>
                                <span class="unit-stat-chip">Master</span>
                            </div>
                            <p class="text-muted small mb-1">Total Satuan</p>
                            <h3 class="mb-1 font-weight-bold" id="stats-total">{{ $stats['total'] ?? 0 }}</h3>
                            <small class="text-muted">
                                <span id="stats-active-inline">{{ $stats['active'] ?? 0 }} aktif</span> /
                                <span id="stats-inactive-inline">{{ $stats['inactive'] ?? 0 }} nonaktif</span>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card unit-stat-card stat-green h-100">
                        <div class="card-body">
                            <div class="unit-stat-top">
                                <span class="unit-stat-icon"><i class="fa fa-tag"></i></span>
                                <span class="unit-stat-chip">Label</span>
                            </div>
                            <p class="text-muted small mb-1">Symbol Terisi</p>
                            <h3 class="mb-1 font-weight-bold text-success" id="stats-symbolized">{{ $stats['symbolized'] ?? 0 }}</h3>
                            <small class="text-muted">Dipakai untuk tampilan singkat seperti kg, g, ml.</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card unit-stat-card stat-amber h-100">
                        <div class="card-body">
                            <div class="unit-stat-top">
                                <span class="unit-stat-icon"><i class="fa fa-random"></i></span>
                                <span class="unit-stat-chip">Konversi</span>
                            </div>
                            <p class="text-muted small mb-1">Aturan Konversi</p>
                            <h3 class="mb-1 font-weight-bold text-warning" id="stats-conversion-total">{{ $stats['conversion_total'] ?? 0 }}</h3>
                            <small class="text-muted">Formula antar satuan per bahan baku.</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card unit-stat-card stat-violet h-100">
                        <div class="card-body">
                            <div class="unit-stat-top">
                                <span class="unit-stat-icon"><i class="fa fa-cubes"></i></span>
                                <span class="unit-stat-chip">Bahan</span>
                            </div>
                            <p class="text-muted small mb-1">Bahan Terhubung</p>
                            <h3 class="mb-1 font-weight-bold text-info">
                                <span id="stats-converted-materials">{{ $stats['converted_materials'] ?? 0 }}</span>
                                <span class="unit-stat-total">/ {{ $stats['raw_material_total'] ?? 0 }}</span>
                            </h3>
                            <small class="text-muted">Bahan baku yang sudah punya aturan konversi.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="unit-tabs-shell shadow-sm">
            <div class="unit-tabs-header">
                <ul class="nav nav-pills unit-tabs" id="satuanTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="master-tab" data-bs-toggle="tab" data-bs-target="#master-pane"
                            type="button" role="tab" aria-controls="master-pane" aria-selected="true">
                            <i class="fa fa-list me-2"></i>Master Satuan
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="conversion-tab" data-bs-toggle="tab" data-bs-target="#conversion-pane"
                            type="button" role="tab" aria-controls="conversion-pane" aria-selected="false">
                            <i class="fa fa-exchange me-2"></i>Konversi per Bahan Baku
                        </button>
                    </li>
                </ul>
            </div>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="master-pane" role="tabpanel" aria-labelledby="master-tab">
                    <div class="unit-panel-toolbar">
                        <div>
                            <h5 class="mb-1">Master Satuan</h5>
                            <small class="text-muted">Daftar satuan utama untuk produk, stok, dan bahan baku.</small>
                        </div>
                        <div class="unit-toolbar-actions">
                            <select id="filterStatus" class="form-select form-select-sm bg-light">
                                <option value="">Semua Status</option>
                                <option value="1">Aktif</option>
                                <option value="0">Tidak Aktif</option>
                            </select>
                            <a href="{{ route('library/satuan/create') }}" class="btn btn-primary btn-sm action">
                                <i class="fa fa-plus me-1"></i> Satuan Baru
                            </a>
                        </div>
                    </div>

                    <div class="table-responsive unit-table-wrap">
                        {!! $dataTable->table(['class' => 'table table-hover table-sm mb-0 align-middle']) !!}
                    </div>
                </div>

                <div class="tab-pane fade" id="conversion-pane" role="tabpanel" aria-labelledby="conversion-tab">
                    <div class="unit-panel-toolbar">
                        <div>
                            <h5 class="mb-1">Konversi per Bahan Baku</h5>
                            <small class="text-muted">Atur formula seperti 1 box telur = 15 kg atau 1 kg tepung = 1000 g.</small>
                        </div>
                        <div class="unit-toolbar-actions">
                            <input id="conversionSearch" type="search" class="form-control form-control-sm bg-light"
                                placeholder="Cari konversi">
                            <select id="filterMaterial" class="form-select form-select-sm bg-light">
                                <option value="">Semua Bahan</option>
                                @foreach ($rawMaterials as $material)
                                    <option value="{{ $material->name }}">{{ $material->name }}</option>
                                @endforeach
                            </select>
                            @if ($canCreateConversion)
                                <a href="{{ route('library/satuan/conversions/create') }}" class="btn btn-primary btn-sm action">
                                    <i class="fa fa-plus me-1"></i> Konversi Baru
                                </a>
                            @else
                                <button type="button" class="btn btn-secondary btn-sm" disabled>
                                    <i class="fa fa-plus me-1"></i> Konversi Baru
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="table-responsive unit-table-wrap">
                        <table id="conversion-table" class="table table-hover table-sm mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Bahan Baku</th>
                                    <th>Formula Konversi</th>
                                    <th>Catatan</th>
                                    <th>Update</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($conversions as $index => $conversion)
                                    @php
                                        $fromLabel = optional($conversion->fromUnit)->symbol ?: optional($conversion->fromUnit)->name ?: '-';
                                        $toLabel = optional($conversion->toUnit)->symbol ?: optional($conversion->toUnit)->name ?: '-';
                                        $baseUnit = optional(optional($conversion->rawMaterial)->baseUnit)->symbol
                                            ?: optional(optional($conversion->rawMaterial)->baseUnit)->name;
                                        $multiplier = rtrim(rtrim(number_format($conversion->multiplier, 6, ',', '.'), '0'), ',');
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ optional($conversion->rawMaterial)->name ?? '-' }}</div>
                                            <small class="text-muted">Base: {{ $baseUnit ?? '-' }}</small>
                                        </td>
                                        <td>
                                            <span class="formula-pill">1 {{ $fromLabel }} = {{ $multiplier }} {{ $toLabel }}</span>
                                            <div class="text-muted small mt-1">
                                                {{ optional($conversion->fromUnit)->name ?? '-' }} ke {{ optional($conversion->toUnit)->name ?? '-' }}
                                            </div>
                                        </td>
                                        <td>{{ $conversion->notes ? \Illuminate\Support\Str::limit($conversion->notes, 80) : '-' }}</td>
                                        <td>{{ optional($conversion->updated_at)->format('d M Y H:i') ?? '-' }}</td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('library/satuan/conversions/edit', $conversion->id) }}"
                                                    class="btn btn-sm btn-outline-primary action">
                                                    Edit
                                                </a>
                                                <a href="{{ route('library/satuan/conversions/destroy', $conversion->id) }}"
                                                    class="btn btn-sm btn-outline-danger delete-conversion">
                                                    Hapus
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('js')
        {!! $dataTable->scripts() !!}

        <script>
            var success = "{{ session('success') }}";
            const datatable = 'satuan-table';
            var table;
            var conversionTable;

            function applyStats(data) {
                if (!data) {
                    return;
                }

                $('#stats-total').text(data.total ?? 0);
                $('#stats-active-inline').text((data.active ?? 0) + ' aktif');
                $('#stats-inactive-inline').text((data.inactive ?? 0) + ' nonaktif');
                $('#stats-symbolized').text(data.symbolized ?? 0);
                $('#stats-conversion-total').text(data.conversion_total ?? 0);
                $('#stats-converted-materials').text(data.converted_materials ?? 0);
            }

            function activateSavedTab() {
                const savedTab = localStorage.getItem('satuan-active-tab');
                const hashTab = window.location.hash === '#konversi' ? 'conversion' : null;
                const activeTab = hashTab || savedTab;

                if (activeTab === 'conversion' && document.querySelector('#conversion-tab')) {
                    if (window.bootstrap && bootstrap.Tab) {
                        bootstrap.Tab.getOrCreateInstance(document.querySelector('#conversion-tab')).show();
                    } else {
                        $('#master-tab').removeClass('active').attr('aria-selected', 'false');
                        $('#master-pane').removeClass('show active');
                        $('#conversion-tab').addClass('active').attr('aria-selected', 'true');
                        $('#conversion-pane').addClass('show active');
                    }
                }
            }

            $(document).ready(function() {
                activateSavedTab();

                $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                    localStorage.setItem('satuan-active-tab', e.target.id === 'conversion-tab' ? 'conversion' : 'master');
                });

                if ($.fn.DataTable.isDataTable('#' + datatable)) {
                    table = $('#' + datatable).DataTable();

                    $('#filterStatus').on('change', function() {
                        table.column('3:visible').search(this.value).draw();
                    });

                    const wrapper = $('#' + datatable).closest('.dataTables_wrapper');
                    wrapper.find('.dataTables_length, .dataTables_filter').closest('.row').addClass('mx-4');
                    wrapper.find('.dataTables_info, .dataTables_paginate').closest('.row').addClass('mx-4');
                }

                if ($('#conversion-table').length) {
                    conversionTable = $('#conversion-table').DataTable({
                        pageLength: 10,
                        lengthChange: false,
                        autoWidth: false,
                        order: [
                            [1, 'asc']
                        ],
                        dom: 'rtip',
                        language: {
                            emptyTable: 'Belum ada konversi per bahan baku.',
                            info: 'Menampilkan _START_ - _END_ dari _TOTAL_ konversi',
                            infoEmpty: 'Belum ada konversi',
                            paginate: {
                                previous: 'Sebelumnya',
                                next: 'Berikutnya'
                            }
                        }
                    });

                    $('#conversionSearch').on('keyup search', function() {
                        conversionTable.search(this.value).draw();
                    });

                    $('#filterMaterial').on('change', function() {
                        conversionTable.column(1).search(this.value).draw();
                    });
                }

                handleAction(datatable, null, function(res) {
                    const data = res.data;
                    applyStats(data);

                    if (data && data.refresh_conversions) {
                        localStorage.setItem('satuan-active-tab', 'conversion');
                        setTimeout(function() {
                            location.reload();
                        }, 450);
                    }
                });

                $('.main-content').on('click', '.delete-conversion', function(e) {
                    e.preventDefault();
                    const url = this.href;

                    Swal.fire({
                        title: 'Hapus konversi?',
                        text: 'Aturan konversi bahan baku ini akan dihapus.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, hapus'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: url,
                                method: 'DELETE',
                                success: function() {
                                    showToast('success', 'Konversi bahan baku berhasil dihapus');
                                    localStorage.setItem('satuan-active-tab', 'conversion');
                                    setTimeout(function() {
                                        location.reload();
                                    }, 450);
                                },
                                error: function(err) {
                                    showToast('error', err.responseJSON?.message || 'Gagal menghapus konversi bahan baku');
                                }
                            });
                        }
                    });
                });
            });

            if (success) {
                Swal.fire({
                    title: 'Sukses!',
                    text: success,
                    icon: 'success',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false,
                    timer: 3000
                });
            }
        </script>
    @endpush

    @push('css')
        <style>
            .unit-stat-card {
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
                overflow: hidden;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .unit-stat-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 26px rgba(18, 38, 63, 0.08);
            }

            .unit-stat-card::before {
                content: "";
                display: block;
                height: 4px;
            }

            .stat-blue::before {
                background: #3568d4;
            }

            .stat-green::before {
                background: #1f9d63;
            }

            .stat-amber::before {
                background: #d89012;
            }

            .stat-violet::before {
                background: #5f6bd8;
            }

            .unit-stat-top {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
                margin-bottom: 14px;
            }

            .unit-stat-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 36px;
                height: 36px;
                border-radius: 8px;
                background: #f3f6fb;
                color: #2f3b52;
            }

            .unit-stat-chip {
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

            .unit-stat-total {
                color: #98a2b3;
                font-size: 18px;
                font-weight: 600;
            }

            .unit-tabs-shell {
                background: #fff;
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
                overflow: hidden;
            }

            .unit-tabs-header {
                padding: 14px 16px 0;
                background: linear-gradient(180deg, #fbfcfe 0%, #ffffff 100%);
                border-bottom: 1px solid rgba(18, 38, 63, 0.08);
            }

            .unit-tabs {
                gap: 8px;
            }

            .unit-tabs .nav-link {
                border-radius: 8px 8px 0 0;
                color: #667085;
                font-weight: 600;
                min-height: 42px;
            }

            .unit-tabs .nav-link.active {
                background: #1f5eff;
                color: #fff;
            }

            .unit-panel-toolbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
                padding: 18px;
                border-bottom: 1px solid rgba(18, 38, 63, 0.08);
            }

            .unit-toolbar-actions {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 8px;
                flex-wrap: wrap;
            }

            .unit-toolbar-actions .form-select,
            .unit-toolbar-actions .form-control {
                min-width: 190px;
                border-color: rgba(18, 38, 63, 0.08);
            }

            .unit-table-wrap {
                padding: 18px 0;
            }

            .formula-pill {
                display: inline-flex;
                align-items: center;
                min-height: 28px;
                padding: 4px 10px;
                border-radius: 999px;
                background: #eef4ff;
                color: #1f5eff;
                font-weight: 700;
                white-space: nowrap;
            }

            #satuan-table tbody tr:hover,
            #conversion-table tbody tr:hover {
                background-color: rgba(31, 94, 255, 0.04);
            }

            #conversion-table_wrapper .dataTables_info,
            #conversion-table_wrapper .dataTables_paginate {
                padding: 12px 18px 0;
            }

            @media (max-width: 767.98px) {
                .unit-panel-toolbar {
                    align-items: stretch;
                    flex-direction: column;
                }

                .unit-toolbar-actions {
                    justify-content: stretch;
                }

                .unit-toolbar-actions .btn,
                .unit-toolbar-actions .form-select,
                .unit-toolbar-actions .form-control {
                    width: 100%;
                    min-width: 0;
                }

                .formula-pill {
                    white-space: normal;
                }
            }
        </style>
    @endpush
@endsection

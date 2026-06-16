@extends('layouts.app')
@section('content')
    <div class="main-content supplier-page">
        <div class="mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h2 class="h4 mb-0 font-weight-bold">
                        <i class="fa fa-truck me-2"></i> Supplier & Bahan Baku
                    </h2>
                    <p class="text-muted small mt-1 mb-0">Kelola pemasok, channel pemesanan, jadwal operasional, dan bahan baku supplier.</p>
                </div>
                <a href="{{ route('warehouse/supplier/create') }}" class="btn btn-primary btn-round action">
                    <i class="fa fa-plus me-2"></i> Tambah Supplier
                </a>
            </div>

            <div class="row g-3">
                <div class="col-md-6 col-xl-3">
                    <div class="card supplier-stat-card stat-blue h-100">
                        <div class="card-body">
                            <div class="supplier-stat-top">
                                <span class="supplier-stat-icon"><i class="fa fa-building"></i></span>
                                <span class="supplier-stat-chip">Master</span>
                            </div>
                            <p class="text-muted small mb-1">Total Supplier</p>
                            <h3 class="mb-1 font-weight-bold" id="stats-total">{{ $stats['total'] ?? 0 }}</h3>
                            <small class="text-muted">
                                <span id="stats-active-inline">{{ $stats['active'] ?? 0 }} aktif</span> /
                                <span id="stats-inactive-inline">{{ $stats['inactive'] ?? 0 }} nonaktif</span>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card supplier-stat-card stat-green h-100">
                        <div class="card-body">
                            <div class="supplier-stat-top">
                                <span class="supplier-stat-icon"><i class="fa fa-toggle-on"></i></span>
                                <span class="supplier-stat-chip">Aktif</span>
                            </div>
                            <p class="text-muted small mb-1">Supplier Aktif</p>
                            <h3 class="mb-1 font-weight-bold text-success" id="stats-active">{{ $stats['active'] ?? 0 }}</h3>
                            <small class="text-muted">Siap dipilih untuk kebutuhan procurement.</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card supplier-stat-card stat-rose h-100">
                        <div class="card-body">
                            <div class="supplier-stat-top">
                                <span class="supplier-stat-icon"><i class="fa fa-pause-circle"></i></span>
                                <span class="supplier-stat-chip">Nonaktif</span>
                            </div>
                            <p class="text-muted small mb-1">Supplier Tidak Aktif</p>
                            <h3 class="mb-1 font-weight-bold text-danger" id="stats-inactive">{{ $stats['inactive'] ?? 0 }}</h3>
                            <small class="text-muted">Tidak tampil sebagai supplier aktif.</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card supplier-stat-card stat-amber h-100">
                        <div class="card-body">
                            <div class="supplier-stat-top">
                                <span class="supplier-stat-icon"><i class="fa fa-boxes"></i></span>
                                <span class="supplier-stat-chip">Bahan</span>
                            </div>
                            <p class="text-muted small mb-1">Total Bahan Supplier</p>
                            <h3 class="mb-1 font-weight-bold text-warning" id="stats-supplied-materials">{{ $stats['supplied_materials'] ?? 0 }}</h3>
                            <small class="text-muted">Bahan baku yang tersambung ke supplier.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle me-2"></i>
                <strong>Sukses!</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="supplier-insight-card mb-3">
            <div class="supplier-insight-copy">
                <span class="supplier-insight-icon"><i class="fa fa-route"></i></span>
                <div>
                    <h6 class="mb-1">Jaringan Supplier</h6>
                    <p class="mb-0 text-muted small">Pantau mode procurement, tempat pemesanan, dan cakupan bahan baku dalam satu daftar.</p>
                </div>
            </div>
            <div class="supplier-insight-meter">
                @php
                    $activeCoverage = ($stats['total'] ?? 0) > 0 ? round((($stats['active'] ?? 0) / $stats['total']) * 100) : 0;
                @endphp
                <span id="stats-active-coverage">{{ $activeCoverage }}%</span>
                <small class="text-muted">supplier aktif</small>
            </div>
        </div>

        <div class="supplier-table-shell shadow-sm">
            <div class="supplier-table-toolbar">
                <div>
                    <h5 class="mb-1">Daftar Supplier</h5>
                    <small class="text-muted">Kode, procurement, operasional, channel, dan bahan baku supplier.</small>
                </div>
                <div class="supplier-toolbar-actions">
                    <a href="{{ route('warehouse/supplier/create') }}" class="btn btn-outline-primary btn-sm action">
                        <i class="fa fa-plus me-1"></i> Supplier Baru
                    </a>
                </div>
            </div>

            <div class="table-responsive supplier-table-wrap">
                {!! $dataTable->table(['class' => 'table table-hover table-sm mb-0 align-middle']) !!}
            </div>
        </div>
    </div>

    @push('js')
        {!! $dataTable->scripts() !!}
        <script>
            const datatable = 'supplier-table';

            function applySupplierStats(data) {
                if (!data) {
                    return;
                }

                $('#stats-total').text(data.total ?? 0);
                $('#stats-active').text(data.active ?? 0);
                $('#stats-inactive').text(data.inactive ?? 0);
                $('#stats-supplied-materials').text(data.supplied_materials ?? 0);
                $('#stats-active-inline').text((data.active ?? 0) + ' aktif');
                $('#stats-inactive-inline').text((data.inactive ?? 0) + ' nonaktif');

                const total = Number(data.total ?? 0);
                const active = Number(data.active ?? 0);
                $('#stats-active-coverage').text(total > 0 ? Math.round((active / total) * 100) + '%' : '0%');
            }

            function initSupplierTooltips() {
                if (typeof bootstrap === 'undefined' || !bootstrap.Tooltip) {
                    return;
                }

                document.querySelectorAll('.supplier-page [data-bs-toggle="tooltip"]').forEach(function(element) {
                    const tooltip = bootstrap.Tooltip.getInstance(element);

                    if (tooltip) {
                        tooltip.dispose();
                    }

                    new bootstrap.Tooltip(element, {
                        container: 'body'
                    });
                });
            }

            function decorateSupplierTableControls() {
                const wrapper = $('#' + datatable).closest('.dataTables_wrapper');

                if (!wrapper.length || wrapper.data('supplierControlsStyled')) {
                    return;
                }

                const filter = wrapper.find('.dataTables_filter');
                const searchInput = filter.find('input').detach();

                searchInput
                    .addClass('supplier-search-input')
                    .attr('placeholder', 'Cari supplier, kode, channel...');

                filter
                    .addClass('supplier-dt-filter')
                    .empty()
                    .append(
                        $('<div class="supplier-search-control"></div>')
                            .append('<i class="fa fa-search"></i>')
                            .append(searchInput)
                    );

                const length = wrapper.find('.dataTables_length');
                const lengthSelect = length.find('select').detach();

                lengthSelect.addClass('supplier-length-select');

                length
                    .addClass('supplier-dt-length')
                    .empty()
                    .append('<span>Tampilkan</span>')
                    .append($('<div class="supplier-select-wrap"></div>').append(lengthSelect))
                    .append('<span>data</span>');

                wrapper.find('.dataTables_length, .dataTables_filter').closest('.row').addClass('supplier-table-control-row');
                wrapper.find('.dataTables_info, .dataTables_paginate').closest('.row').addClass('supplier-table-footer-row');
                wrapper.data('supplierControlsStyled', true);
                syncSupplierStickyRowsWidth();
            }

            function syncSupplierStickyRowsWidth() {
                const wrapper = $('#' + datatable).closest('.dataTables_wrapper');
                const tableWrap = wrapper.closest('.supplier-table-wrap');

                if (!wrapper.length || !tableWrap.length) {
                    return;
                }

                const visibleWidth = Math.max(tableWrap[0].clientWidth, 320);

                wrapper
                    .find('.supplier-table-control-row, .supplier-table-footer-row')
                    .css('--supplier-sticky-row-width', visibleWidth + 'px');
            }

            $(document).ready(function () {
                if ($.fn.DataTable.isDataTable('#' + datatable)) {
                    const table = $('#' + datatable).DataTable();

                    decorateSupplierTableControls();
                    initSupplierTooltips();

                    table.on('draw.dt', function() {
                        initSupplierTooltips();
                        syncSupplierStickyRowsWidth();
                    });

                    $(window).on('resize.supplierTable', function() {
                        syncSupplierStickyRowsWidth();
                    });
                }

                handleAction(datatable, null, function (res) {
                    applySupplierStats(res.data);
                });
                handleDelete(datatable, 'Supplier akan dihapus dari daftar.', function (res) {
                    applySupplierStats(res.data);
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            .supplier-page .card {
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
            }

            .supplier-stat-card {
                overflow: hidden;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .supplier-stat-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 26px rgba(18, 38, 63, 0.08);
            }

            .supplier-stat-card::before {
                content: "";
                display: block;
                height: 4px;
            }

            .stat-blue::before {
                background: #2f62d8;
            }

            .stat-green::before {
                background: #14966f;
            }

            .stat-rose::before {
                background: #d94d5f;
            }

            .stat-amber::before {
                background: #d89012;
            }

            .supplier-stat-top {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
                margin-bottom: 14px;
            }

            .supplier-stat-icon,
            .supplier-insight-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 36px;
                height: 36px;
                border-radius: 8px;
                background: #f3f6fb;
                color: #2f3b52;
            }

            .supplier-stat-chip {
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

            .supplier-insight-card,
            .supplier-table-shell {
                background: #fff;
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
            }

            .supplier-insight-card {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 18px;
                padding: 16px 18px;
            }

            .supplier-insight-copy {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .supplier-insight-meter {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                min-width: 112px;
            }

            .supplier-insight-meter span {
                color: #2f62d8;
                font-size: 24px;
                font-weight: 800;
                line-height: 1;
            }

            .supplier-table-shell {
                overflow: hidden;
            }

            .supplier-table-toolbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
                padding: 18px;
                background: linear-gradient(180deg, #fbfcfe 0%, #ffffff 100%);
                border-bottom: 1px solid rgba(18, 38, 63, 0.08);
            }

            .supplier-toolbar-actions {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 8px;
                flex-wrap: wrap;
            }

            .supplier-table-wrap {
                overflow-x: auto;
                padding: 18px 0;
            }

            #supplier-table_wrapper {
                min-width: max-content;
            }

            #supplier-table {
                width: 100% !important;
            }

            #supplier-table thead th {
                border-bottom: 1px solid rgba(18, 38, 63, 0.08);
                color: #667085;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0;
                text-transform: uppercase;
                white-space: nowrap;
            }

            #supplier-table tbody td {
                color: #2f3b52;
                padding-bottom: 13px;
                padding-top: 13px;
            }

            #supplier-table tbody tr:hover {
                background-color: rgba(47, 98, 216, 0.04);
            }

            #supplier-table_wrapper .supplier-table-control-row {
                display: flex;
                align-items: center;
                background: #fff;
                border-bottom: 1px solid rgba(18, 38, 63, 0.08);
                gap: 12px;
                justify-content: space-between;
                left: 0;
                margin-left: 0;
                margin-right: 0;
                max-width: var(--supplier-sticky-row-width, 100%);
                min-width: var(--supplier-sticky-row-width, 100%);
                padding: 0 18px 14px;
                position: sticky;
                row-gap: 12px;
                width: var(--supplier-sticky-row-width, 100%);
                z-index: 7;
            }

            #supplier-table_wrapper .supplier-table-footer-row {
                display: flex;
                align-items: center;
                background: #fff;
                border-top: 1px solid rgba(18, 38, 63, 0.08);
                gap: 12px;
                justify-content: space-between;
                left: 0;
                margin-left: 0;
                margin-right: 0;
                max-width: var(--supplier-sticky-row-width, 100%);
                min-width: var(--supplier-sticky-row-width, 100%);
                padding: 14px 18px 6px;
                position: sticky;
                row-gap: 12px;
                width: var(--supplier-sticky-row-width, 100%);
                z-index: 7;
            }

            #supplier-table_wrapper .supplier-table-control-row > [class*="col-"],
            #supplier-table_wrapper .supplier-table-footer-row > [class*="col-"] {
                flex: 0 0 auto;
                max-width: 100%;
                padding-left: 0;
                padding-right: 0;
                width: auto;
            }

            #supplier-table_wrapper .supplier-table-control-row > [class*="col-"]:last-child,
            #supplier-table_wrapper .supplier-table-footer-row > [class*="col-"]:last-child {
                margin-left: auto;
            }

            .supplier-dt-length {
                display: flex;
                align-items: center;
                gap: 8px;
                color: #667085;
                font-size: 13px;
                font-weight: 600;
            }

            .supplier-select-wrap {
                position: relative;
            }

            .supplier-length-select {
                min-width: 74px;
                height: 36px;
                border: 1px solid rgba(18, 38, 63, 0.12);
                border-radius: 8px;
                color: #2f3b52;
                font-weight: 700;
                padding: 6px 28px 6px 12px;
                background-color: #f8fafc;
                box-shadow: none;
            }

            .supplier-dt-filter {
                display: flex;
                justify-content: flex-end;
            }

            .supplier-search-control {
                position: relative;
                width: min(360px, 100%);
            }

            .supplier-search-control i {
                position: absolute;
                left: 16px;
                top: 50%;
                color: #98a2b3;
                pointer-events: none;
                transform: translateY(-50%);
                width: 16px;
                z-index: 2;
            }

            #supplier-table_wrapper .supplier-search-control .supplier-search-input {
                width: 100% !important;
                height: 40px;
                margin: 0 !important;
                border: 1px solid rgba(18, 38, 63, 0.12);
                border-radius: 8px;
                background: #f8fafc;
                color: #2f3b52;
                padding: 8px 14px 8px 46px !important;
                box-shadow: none;
                text-indent: 0;
                transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
            }

            #supplier-table_wrapper .supplier-search-control .supplier-search-input::placeholder {
                color: #98a2b3;
                opacity: 1;
            }

            #supplier-table_wrapper .supplier-search-control .supplier-search-input:focus,
            .supplier-length-select:focus {
                border-color: rgba(47, 98, 216, 0.45);
                background: #fff;
                box-shadow: 0 0 0 3px rgba(47, 98, 216, 0.12);
                outline: none;
            }

            #supplier-table_wrapper .dataTables_info {
                color: #667085;
                font-size: 13px;
            }

            #supplier-table_wrapper .pagination {
                gap: 4px;
                margin-bottom: 0;
            }

            #supplier-table_wrapper .page-link {
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
                color: #2f3b52;
                min-width: 34px;
                text-align: center;
            }

            #supplier-table_wrapper .page-item.active .page-link {
                background: #2f62d8;
                border-color: #2f62d8;
                color: #fff;
            }

            .supplier-code-pill,
            .supplier-channel-pill,
            .supplier-channel-more,
            .supplier-material-count,
            .supplier-mode-chip {
                display: inline-flex;
                align-items: center;
                min-height: 28px;
                border-radius: 999px;
                font-size: 12px;
                font-weight: 800;
                white-space: nowrap;
            }

            .supplier-code-pill {
                padding: 4px 10px;
                background: #eef4ff;
                border: 1px solid #d8e4ff;
                color: #2446a8;
            }

            .supplier-name-cell {
                display: flex;
                flex-direction: column;
                gap: 4px;
                min-width: 160px;
            }

            .supplier-name-text {
                color: #24324a;
                font-weight: 800;
                line-height: 1.25;
            }

            .supplier-name-cell small {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                color: #667085;
                font-weight: 600;
            }

            .supplier-status-dot {
                width: 8px;
                height: 8px;
                border-radius: 999px;
                background: #98a2b3;
            }

            .supplier-status-dot.is-active {
                background: #14966f;
                box-shadow: 0 0 0 3px rgba(20, 150, 111, 0.12);
            }

            .supplier-mode-chip {
                gap: 7px;
                padding: 4px 10px;
            }

            .supplier-mode-chip i {
                font-size: 11px;
            }

            .mode-online {
                background: #eef4ff;
                color: #2446a8;
            }

            .mode-offline {
                background: #fef3e2;
                color: #a85c00;
            }

            .mode-both {
                background: #ecfdf3;
                color: #087443;
            }

            .mode-empty {
                background: #f2f4f7;
                color: #667085;
            }

            .supplier-stack-cell {
                display: flex;
                flex-direction: column;
                gap: 5px;
                min-width: 168px;
            }

            .supplier-main-line,
            .supplier-sub-line {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                line-height: 1.25;
            }

            .supplier-main-line {
                color: #24324a;
                font-weight: 800;
            }

            .supplier-sub-line {
                color: #667085;
                font-weight: 600;
            }

            .supplier-main-line i,
            .supplier-sub-line i {
                color: #98a2b3;
                font-size: 12px;
                width: 14px;
            }

            .supplier-channel-list {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 6px;
                max-width: 220px;
            }

            .supplier-channel-pill {
                padding: 4px 9px;
                background: #f2f4f7;
                color: #344054;
            }

            .supplier-channel-more {
                padding: 4px 8px;
                background: #fff7ed;
                color: #b54708;
            }

            .supplier-material-single {
                display: inline-block;
                max-width: 220px;
                color: #24324a;
                font-weight: 700;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .supplier-material-count {
                gap: 7px;
                padding: 5px 10px;
                background: #f0f9ff;
                color: #026aa2;
                cursor: help;
            }

            .supplier-action-group {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                white-space: nowrap;
            }

            .supplier-action-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 32px;
                height: 32px;
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
                background: #fff;
                color: #475467;
                transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
            }

            .supplier-action-btn:hover {
                background: #eef4ff;
                border-color: #d8e4ff;
                color: #2446a8;
                transform: translateY(-1px);
            }

            .supplier-action-btn.danger:hover {
                background: #fff1f3;
                border-color: #ffd6dd;
                color: #c01048;
            }

            @media (max-width: 991.98px) {
                .supplier-table-toolbar {
                    align-items: stretch;
                    flex-direction: column;
                }

                .supplier-toolbar-actions {
                    justify-content: flex-start;
                }
            }

            @media (max-width: 767.98px) {
                .supplier-insight-card {
                    align-items: stretch;
                    flex-direction: column;
                }

                .supplier-insight-meter {
                    align-items: flex-start;
                }

                .supplier-dt-filter {
                    justify-content: stretch;
                }

                #supplier-table_wrapper .supplier-table-control-row,
                #supplier-table_wrapper .supplier-table-footer-row {
                    align-items: stretch;
                    flex-direction: column;
                    padding-left: 14px;
                    padding-right: 14px;
                }

                #supplier-table_wrapper .supplier-table-control-row > [class*="col-"],
                #supplier-table_wrapper .supplier-table-footer-row > [class*="col-"] {
                    width: 100%;
                }

                #supplier-table_wrapper .supplier-table-control-row > [class*="col-"]:last-child,
                #supplier-table_wrapper .supplier-table-footer-row > [class*="col-"]:last-child {
                    margin-left: 0;
                }

                .supplier-search-control {
                    width: 100%;
                }

                .supplier-dt-length {
                    justify-content: flex-start;
                    flex-wrap: wrap;
                }
            }
        </style>
    @endpush
@endsection

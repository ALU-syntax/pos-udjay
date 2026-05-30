@extends('layouts.app')
@section('content')
    <div class="main-content procurement-plan-page">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h2 class="h4 mb-0 font-weight-bold">
                        <i class="fa fa-clipboard-check me-2"></i>Procurement Plan
                    </h2>
                    <p class="text-muted small mt-1 mb-0">Kumpulkan item Request Order yang sudah approved sebelum digenerate menjadi Purchase Order.</p>
                </div>
                <a href="{{ route('warehouse/procurement-plan/create') }}" class="btn btn-primary btn-round">
                    <i class="fa fa-plus me-2"></i>Tambah Plan
                </a>
            </div>

            <div class="row g-3">
                <div class="col-12 col-sm-6 col-xl-2">
                    <div class="card pp-stat-card stat-blue h-100">
                        <div class="card-body">
                            <p class="pp-stat-title text-muted small mb-2">Total Plan</p>
                            <div class="pp-stat-value-row">
                                <span class="pp-stat-icon"><i class="fa fa-list-alt"></i></span>
                                <h3 class="mb-0 font-weight-bold" id="stats-total">{{ $stats['total'] ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-2">
                    <div class="card pp-stat-card stat-slate h-100">
                        <div class="card-body">
                            <p class="pp-stat-title text-muted small mb-2">Draft</p>
                            <div class="pp-stat-value-row">
                                <span class="pp-stat-icon"><i class="fa fa-pencil-alt"></i></span>
                                <h3 class="mb-0 font-weight-bold text-secondary" id="stats-draft">{{ $stats['draft'] ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-2">
                    <div class="card pp-stat-card stat-green h-100">
                        <div class="card-body">
                            <p class="pp-stat-title text-muted small mb-2">Approved</p>
                            <div class="pp-stat-value-row">
                                <span class="pp-stat-icon"><i class="fa fa-check-circle"></i></span>
                                <h3 class="mb-0 font-weight-bold text-success" id="stats-approved">{{ $stats['approved'] ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-2">
                    <div class="card pp-stat-card stat-teal h-100">
                        <div class="card-body">
                            <p class="pp-stat-title text-muted small mb-2">Converted PO</p>
                            <div class="pp-stat-value-row">
                                <span class="pp-stat-icon"><i class="fa fa-file-invoice"></i></span>
                                <h3 class="mb-0 font-weight-bold text-info" id="stats-converted">{{ $stats['converted_to_po'] ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-2">
                    <div class="card pp-stat-card stat-red h-100">
                        <div class="card-body">
                            <p class="pp-stat-title text-muted small mb-2">Cancelled</p>
                            <div class="pp-stat-value-row">
                                <span class="pp-stat-icon"><i class="fa fa-ban"></i></span>
                                <h3 class="mb-0 font-weight-bold text-danger" id="stats-cancelled">{{ $stats['cancelled'] ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-2">
                    <div class="card pp-stat-card stat-amber h-100">
                        <div class="card-body">
                            <p class="pp-stat-title text-muted small mb-2">Ready Sources</p>
                            <div class="pp-stat-value-row">
                                <span class="pp-stat-icon"><i class="fa fa-layer-group"></i></span>
                                <h3 class="mb-0 font-weight-bold text-warning" id="stats-sources">{{ $stats['available_sources'] ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pp-table-shell shadow-sm">
            <div class="pp-table-toolbar">
                <div>
                    <h5 class="mb-1">Daftar Procurement Plan</h5>
                    <small class="text-muted">Draft berasal dari item Request Order approved yang dipilih saat membuat plan.</small>
                </div>
                <div class="pp-toolbar-actions">
                    <select id="filterStatus" class="form-select form-select-sm bg-light">
                        <option value="">Semua Status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->code }}">{{ $status->name }}</option>
                        @endforeach
                    </select>
                    <select id="filterPlanningLocation" class="form-select form-select-sm bg-light">
                        <option value="">Semua Lokasi</option>
                        @foreach ($inventories as $inventory)
                            <option value="{{ $inventory->name }}">{{ $inventory->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="pp-table-wrap">
                {!! $dataTable->table(['class' => 'table table-hover table-sm mb-0 align-middle']) !!}
            </div>
        </div>
    </div>

    @push('js')
        {!! $dataTable->scripts() !!}

        <script>
            const datatable = 'procurement-plan-table';

            function applyProcurementPlanStats(data) {
                if (!data) {
                    return;
                }

                $('#stats-total').text(data.total ?? 0);
                $('#stats-draft').text(data.draft ?? 0);
                $('#stats-approved').text(data.approved ?? 0);
                $('#stats-converted').text(data.converted_to_po ?? 0);
                $('#stats-cancelled').text(data.cancelled ?? 0);
                $('#stats-sources').text(data.available_sources ?? 0);
            }

            $(document).ready(function() {
                if ($.fn.DataTable.isDataTable('#' + datatable)) {
                    const table = $('#' + datatable).DataTable();
                    let floatingActionDropdown = null;

                    function adjustProcurementPlanTable() {
                        table.columns.adjust();

                        if (table.responsive) {
                            table.responsive.recalc();
                        }
                    }

                    function adjustProcurementPlanTableWithDelay() {
                        adjustProcurementPlanTable();
                        setTimeout(adjustProcurementPlanTable, 150);
                        setTimeout(adjustProcurementPlanTable, 350);
                    }

                    function positionFloatingActionDropdown() {
                        if (!floatingActionDropdown) {
                            return;
                        }

                        const toggle = floatingActionDropdown.toggle;
                        const menu = floatingActionDropdown.menu;
                        const rect = toggle.getBoundingClientRect();
                        const menuWidth = menu.outerWidth();
                        const menuHeight = menu.outerHeight();
                        const topFromButton = rect.bottom + 4;
                        const top = topFromButton + menuHeight > window.innerHeight
                            ? Math.max(8, rect.top - menuHeight - 4)
                            : topFromButton;
                        const right = Math.max(8, window.innerWidth - rect.right);

                        menu.css({
                            position: 'fixed',
                            top: top + 'px',
                            right: right + 'px',
                            left: 'auto',
                            transform: 'none',
                            zIndex: 2050,
                            minWidth: Math.max(menuWidth, 160) + 'px'
                        });
                    }

                    $('#filterStatus').on('change', function() {
                        table.column('2:visible').search(this.value).draw();
                    });

                    $('#filterPlanningLocation').on('change', function() {
                        table.column('3:visible').search(this.value).draw();
                    });

                    const wrapper = $('#' + datatable).closest('.dataTables_wrapper');
                    wrapper.find('.dataTables_length, .dataTables_filter').closest('.row').addClass('mx-3');
                    wrapper.find('.dataTables_info, .dataTables_paginate').closest('.row').addClass('mx-3');
                    const scrollBody = wrapper.find('.dataTables_scrollBody');

                    table.on('draw', adjustProcurementPlanTable);
                    $(window).on('resize', adjustProcurementPlanTableWithDelay);
                    $('.toggle-sidebar, .sidenav-toggler, .topbar-toggler').on('click', adjustProcurementPlanTableWithDelay);
                    $('.main-panel, .sidebar').on('transitionend', adjustProcurementPlanTable);

                    if (window.ResizeObserver) {
                        new ResizeObserver(adjustProcurementPlanTable).observe($('.pp-table-shell')[0]);
                    }

                    $('.main-content').on('shown.bs.dropdown', '#' + datatable + ' .btn-group', function() {
                        const dropdown = $(this);
                        const menu = dropdown.find('.dropdown-menu');
                        const toggle = dropdown.find('[data-bs-toggle="dropdown"]')[0];
                        const placeholder = $('<span class="pp-dropdown-placeholder d-none"></span>');

                        if (!menu.length || !toggle) {
                            return;
                        }

                        placeholder.insertBefore(menu);
                        $('body').append(menu.detach());
                        menu.addClass('pp-floating-dropdown show');

                        floatingActionDropdown = {
                            dropdown,
                            menu,
                            toggle,
                            placeholder
                        };

                        positionFloatingActionDropdown();
                        $(window).on('scroll.procurementPlanDropdown resize.procurementPlanDropdown', positionFloatingActionDropdown);
                        scrollBody.on('scroll.procurementPlanDropdown', positionFloatingActionDropdown);
                    });

                    $('.main-content').on('hidden.bs.dropdown', '#' + datatable + ' .btn-group', function() {
                        if (!floatingActionDropdown) {
                            return;
                        }

                        const menu = floatingActionDropdown.menu;
                        const placeholder = floatingActionDropdown.placeholder;

                        menu.removeClass('pp-floating-dropdown show').removeAttr('style');
                        placeholder.replaceWith(menu.detach());
                        floatingActionDropdown = null;

                        $(window).off('scroll.procurementPlanDropdown resize.procurementPlanDropdown');
                        scrollBody.off('scroll.procurementPlanDropdown');
                    });

                    adjustProcurementPlanTableWithDelay();
                }

                handleDelete(datatable, 'Procurement plan draft akan dihapus dari daftar.', function(res) {
                    applyProcurementPlanStats(res.data);
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            .procurement-plan-page .card,
            .pp-table-shell {
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
            }

            .pp-stat-card {
                overflow: hidden;
            }

            .pp-stat-card .card-body {
                padding: 14px 12px;
            }

            .pp-stat-value-row {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .pp-stat-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 34px;
                width: 34px;
                height: 34px;
                border-radius: 8px;
                background: #f3f6fb;
                color: #2f3b52;
                font-size: 15px;
            }

            .pp-stat-title {
                font-weight: 800;
                line-height: 1.2;
            }

            .pp-stat-card::before {
                content: "";
                display: block;
                height: 4px;
            }

            .stat-blue::before {
                background: #2f6fcf;
            }

            .stat-slate::before {
                background: #6b7280;
            }

            .stat-amber::before {
                background: #d89012;
            }

            .stat-green::before {
                background: #15965f;
            }

            .stat-teal::before {
                background: #0f8f8a;
            }

            .stat-red::before {
                background: #d94d5f;
            }

            .pp-table-shell {
                overflow: hidden;
                background: #fff;
            }

            .pp-table-toolbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
                padding: 18px;
                background: #fbfcfe;
                border-bottom: 1px solid rgba(18, 38, 63, 0.08);
            }

            .pp-toolbar-actions {
                display: flex;
                justify-content: flex-end;
                gap: 8px;
                flex-wrap: wrap;
            }

            .pp-toolbar-actions .form-select {
                min-width: 160px;
                border-color: rgba(18, 38, 63, 0.08);
            }

            .pp-table-wrap {
                padding: 18px 0;
                overflow: hidden;
            }

            .procurement-plan-page .dataTables_wrapper,
            #procurement-plan-table {
                width: 100% !important;
            }

            #procurement-plan-table {
                table-layout: auto;
            }

            .procurement-plan-page .dataTables_wrapper > .row {
                margin-left: 0;
                margin-right: 0;
            }

            .procurement-plan-page .dataTables_scroll {
                width: 100%;
            }

            .procurement-plan-page .dataTables_scrollBody {
                overflow-x: auto !important;
                overflow-y: hidden !important;
            }

            .procurement-plan-page .dataTables_scrollBody thead tr,
            .procurement-plan-page .dataTables_scrollBody thead th {
                height: 0 !important;
                padding-top: 0 !important;
                padding-bottom: 0 !important;
                border-top: 0 !important;
                border-bottom: 0 !important;
                line-height: 0 !important;
                color: transparent !important;
            }

            .procurement-plan-page .dataTables_scrollBody thead th::before,
            .procurement-plan-page .dataTables_scrollBody thead th::after {
                display: none !important;
            }

            .procurement-plan-page .dataTables_scrollHead,
            .procurement-plan-page .dataTables_scrollFoot {
                overflow: hidden !important;
            }

            .pp-floating-dropdown {
                display: block;
                box-shadow: 0 12px 32px rgba(15, 23, 42, 0.16);
            }

            .procurement-plan-page .dataTables_length,
            .procurement-plan-page .dataTables_filter,
            .procurement-plan-page .dataTables_info,
            .procurement-plan-page .dataTables_paginate {
                position: relative;
                z-index: 1;
            }

            @media (max-width: 991.98px) {
                .pp-table-toolbar {
                    align-items: stretch;
                    flex-direction: column;
                }

                .pp-toolbar-actions {
                    justify-content: stretch;
                }

                .pp-toolbar-actions .form-select {
                    width: 100%;
                }
            }
        </style>
    @endpush
@endsection

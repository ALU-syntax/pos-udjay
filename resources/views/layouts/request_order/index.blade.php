@extends('layouts.app')
@section('content')
    <div class="main-content request-order-page">
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
                        <i class="fa fa-clipboard-list me-2"></i>Request Order
                    </h2>
                    <p class="text-muted small mt-1 mb-0">Buat draft kebutuhan bahan baku dari outlet/kitchen/bar/pastry sebelum masuk proses review.</p>
                </div>
                <a href="{{ route('warehouse/request-order/create') }}" class="btn btn-primary btn-round">
                    <i class="fa fa-plus me-2"></i>Tambah Request
                </a>
            </div>

            <div class="row g-3">
                <div class="col-12 col-sm-6 col-xl-2">
                    <div class="card ro-stat-card stat-blue h-100">
                        <div class="card-body">
                            <p class="ro-stat-title text-muted small mb-2">Total Request</p>
                            <div class="ro-stat-value-row">
                                <span class="ro-stat-icon"><i class="fa fa-list-alt"></i></span>
                                <h3 class="mb-0 font-weight-bold" id="stats-total">{{ $stats['total'] ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-2">
                    <div class="card ro-stat-card stat-slate h-100">
                        <div class="card-body">
                            <p class="ro-stat-title text-muted small mb-2">Pending Review</p>
                            <div class="ro-stat-value-row">
                                <span class="ro-stat-icon"><i class="fa fa-hourglass-half"></i></span>
                                <h3 class="mb-0 font-weight-bold text-secondary" id="stats-pending-review">{{ $stats['pending_review'] ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-2">
                    <div class="card ro-stat-card stat-green h-100">
                        <div class="card-body">
                            <p class="ro-stat-title text-muted small mb-2">Approved</p>
                            <div class="ro-stat-value-row">
                                <span class="ro-stat-icon"><i class="fa fa-check-circle"></i></span>
                                <h3 class="mb-0 font-weight-bold text-success" id="stats-approved">{{ $stats['approved'] ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-2">
                    <div class="card ro-stat-card stat-amber h-100">
                        <div class="card-body">
                            <p class="ro-stat-title text-muted small mb-2">Partially Fulfilled</p>
                            <div class="ro-stat-value-row">
                                <span class="ro-stat-icon"><i class="fa fa-tasks"></i></span>
                                <h3 class="mb-0 font-weight-bold text-warning" id="stats-partially-fulfilled">{{ $stats['partially_fulfilled'] ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-2">
                    <div class="card ro-stat-card stat-teal h-100">
                        <div class="card-body">
                            <p class="ro-stat-title text-muted small mb-2">Fulfilled</p>
                            <div class="ro-stat-value-row">
                                <span class="ro-stat-icon"><i class="fa fa-clipboard-check"></i></span>
                                <h3 class="mb-0 font-weight-bold text-success" id="stats-fulfilled">{{ $stats['fulfilled'] ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-2">
                    <div class="card ro-stat-card stat-red h-100">
                        <div class="card-body">
                            <p class="ro-stat-title text-muted small mb-2">Rejected</p>
                            <div class="ro-stat-value-row">
                                <span class="ro-stat-icon"><i class="fa fa-times-circle"></i></span>
                                <h3 class="mb-0 font-weight-bold text-danger" id="stats-rejected">{{ $stats['rejected'] ?? 0 }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="ro-table-shell shadow-sm">
            <div class="ro-table-toolbar">
                <div>
                    <h5 class="mb-1">Daftar Request Order</h5>
                    <small class="text-muted">Draft masih bisa diedit dan dihapus. Setelah sesuai, submit untuk masuk tahap review.</small>
                </div>
                <div class="ro-toolbar-actions">
                    <select id="filterStatus" class="form-select form-select-sm bg-light">
                        <option value="">Semua Status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->code }}">{{ $status->name }}</option>
                        @endforeach
                    </select>
                    <select id="filterRequester" class="form-select form-select-sm bg-light">
                        <option value="">Semua Pemohon</option>
                        @foreach ($inventories as $inventory)
                            <option value="{{ $inventory->name }}">{{ $inventory->name }}</option>
                        @endforeach
                    </select>
                    <select id="filterFulfillment" class="form-select form-select-sm bg-light">
                        <option value="">Semua Pemenuhan</option>
                        <option value="__empty__">Belum ditentukan</option>
                        @foreach ($inventories as $inventory)
                            <option value="{{ $inventory->name }}">{{ $inventory->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="ro-table-wrap">
                {!! $dataTable->table(['class' => 'table table-hover table-sm mb-0 align-middle']) !!}
            </div>
        </div>
    </div>

    @push('js')
        {!! $dataTable->scripts() !!}

        <script>
            const datatable = 'request-order-table';

            function applyRequestOrderStats(data) {
                if (!data) {
                    return;
                }

                $('#stats-total').text(data.total ?? 0);
                $('#stats-pending-review').text(data.pending_review ?? data.submitted ?? 0);
                $('#stats-approved').text(data.approved ?? 0);
                $('#stats-partially-fulfilled').text(data.partially_fulfilled ?? 0);
                $('#stats-fulfilled').text(data.fulfilled ?? 0);
                $('#stats-rejected').text(data.rejected ?? 0);
            }

            $(document).ready(function() {
                if ($.fn.DataTable.isDataTable('#' + datatable)) {
                    const table = $('#' + datatable).DataTable();
                    let floatingActionDropdown = null;

                    function adjustRequestOrderTable() {
                        table.columns.adjust();

                        if (table.responsive) {
                            table.responsive.recalc();
                        }
                    }

                    function adjustRequestOrderTableWithDelay() {
                        adjustRequestOrderTable();
                        setTimeout(adjustRequestOrderTable, 150);
                        setTimeout(adjustRequestOrderTable, 350);
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

                    $('#filterRequester').on('change', function() {
                        table.column('3:visible').search(this.value).draw();
                    });

                    $('#filterFulfillment').on('change', function() {
                        table.column('4:visible').search(this.value).draw();
                    });

                    const wrapper = $('#' + datatable).closest('.dataTables_wrapper');
                    wrapper.find('.dataTables_length, .dataTables_filter').closest('.row').addClass('mx-3');
                    wrapper.find('.dataTables_info, .dataTables_paginate').closest('.row').addClass('mx-3');
                    const scrollBody = wrapper.find('.dataTables_scrollBody');

                    table.on('draw', adjustRequestOrderTable);
                    $(window).on('resize', adjustRequestOrderTableWithDelay);
                    $('.toggle-sidebar, .sidenav-toggler, .topbar-toggler').on('click', adjustRequestOrderTableWithDelay);
                    $('.main-panel, .sidebar').on('transitionend', adjustRequestOrderTable);

                    if (window.ResizeObserver) {
                        new ResizeObserver(adjustRequestOrderTable).observe($('.ro-table-shell')[0]);
                    }

                    $('.main-content').on('shown.bs.dropdown', '#' + datatable + ' .btn-group', function() {
                        const dropdown = $(this);
                        const menu = dropdown.find('.dropdown-menu');
                        const toggle = dropdown.find('[data-bs-toggle="dropdown"]')[0];
                        const placeholder = $('<span class="ro-dropdown-placeholder d-none"></span>');

                        if (!menu.length || !toggle) {
                            return;
                        }

                        placeholder.insertBefore(menu);
                        $('body').append(menu.detach());
                        menu.addClass('ro-floating-dropdown show');

                        floatingActionDropdown = {
                            dropdown,
                            menu,
                            toggle,
                            placeholder
                        };

                        positionFloatingActionDropdown();
                        $(window).on('scroll.requestOrderDropdown resize.requestOrderDropdown', positionFloatingActionDropdown);
                        scrollBody.on('scroll.requestOrderDropdown', positionFloatingActionDropdown);
                    });

                    $('.main-content').on('hidden.bs.dropdown', '#' + datatable + ' .btn-group', function() {
                        if (!floatingActionDropdown) {
                            return;
                        }

                        const menu = floatingActionDropdown.menu;
                        const placeholder = floatingActionDropdown.placeholder;

                        menu.removeClass('ro-floating-dropdown show').removeAttr('style');
                        placeholder.replaceWith(menu.detach());
                        floatingActionDropdown = null;

                        $(window).off('scroll.requestOrderDropdown resize.requestOrderDropdown');
                        scrollBody.off('scroll.requestOrderDropdown');
                    });

                    adjustRequestOrderTableWithDelay();
                }

                handleDelete(datatable, 'Request order draft akan dihapus dari daftar.', function(res) {
                    applyRequestOrderStats(res.data);
                });

                $('#' + datatable).on('click', '.submit-request-order', function(e) {
                    e.preventDefault();
                    const url = this.href;

                    Swal.fire({
                        title: 'Submit request order?',
                        text: 'Setelah disubmit, request order masuk tahap review dan tidak bisa diedit dari draft.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, submit'
                    }).then((result) => {
                        if (!result.isConfirmed) {
                            return;
                        }

                        $.ajax({
                            url: url,
                            method: 'POST',
                            success: function(res) {
                                showToast(res.status, res.message);
                                applyRequestOrderStats(res.data);
                                window.LaravelDataTables[datatable].ajax.reload(null, false);
                            },
                            error: function(err) {
                                showToast('error', err.responseJSON?.message || 'Gagal submit request order');
                            }
                        });
                    });
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            .request-order-page .card,
            .ro-table-shell {
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
            }

            .ro-stat-card {
                overflow: hidden;
            }

            .ro-stat-card .card-body {
                padding: 14px 12px;
            }

            .ro-stat-value-row {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .ro-stat-icon {
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

            .ro-stat-title {
                font-weight: 800;
                line-height: 1.2;
            }

            .ro-stat-card::before {
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

            .ro-table-shell {
                overflow: hidden;
                background: #fff;
            }

            .ro-table-toolbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
                padding: 18px;
                background: #fbfcfe;
                border-bottom: 1px solid rgba(18, 38, 63, 0.08);
            }

            .ro-toolbar-actions {
                display: flex;
                justify-content: flex-end;
                gap: 8px;
                flex-wrap: wrap;
            }

            .ro-toolbar-actions .form-select {
                min-width: 160px;
                border-color: rgba(18, 38, 63, 0.08);
            }

            .ro-table-wrap {
                padding: 18px 0;
                overflow: hidden;
            }

            .request-order-page .dataTables_wrapper,
            #request-order-table {
                width: 100% !important;
            }

            #request-order-table {
                table-layout: auto;
            }

            .request-order-page .dataTables_wrapper > .row {
                margin-left: 0;
                margin-right: 0;
            }

            .request-order-page .dataTables_scroll {
                width: 100%;
            }

            .request-order-page .dataTables_scrollBody {
                overflow-x: auto !important;
                overflow-y: hidden !important;
            }

            .request-order-page .dataTables_scrollBody thead tr,
            .request-order-page .dataTables_scrollBody thead th {
                height: 0 !important;
                padding-top: 0 !important;
                padding-bottom: 0 !important;
                border-top: 0 !important;
                border-bottom: 0 !important;
                line-height: 0 !important;
                color: transparent !important;
            }

            .request-order-page .dataTables_scrollBody thead th::before,
            .request-order-page .dataTables_scrollBody thead th::after {
                display: none !important;
            }

            .request-order-page .dataTables_scrollHead,
            .request-order-page .dataTables_scrollFoot {
                overflow: hidden !important;
            }

            .ro-floating-dropdown {
                display: block;
                box-shadow: 0 12px 32px rgba(15, 23, 42, 0.16);
            }

            .request-order-page .dataTables_length,
            .request-order-page .dataTables_filter,
            .request-order-page .dataTables_info,
            .request-order-page .dataTables_paginate {
                position: relative;
                z-index: 1;
            }

            @media (max-width: 991.98px) {
                .ro-table-toolbar {
                    align-items: stretch;
                    flex-direction: column;
                }

                .ro-toolbar-actions {
                    justify-content: stretch;
                }

                .ro-toolbar-actions .form-select {
                    width: 100%;
                }
            }
        </style>
    @endpush
@endsection

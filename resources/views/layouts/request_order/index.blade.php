@extends('layouts.app')
@section('content')
    @php
        $totalRequests = (int) ($stats['total'] ?? 0);
        $pendingReview = (int) ($stats['pending_review'] ?? ($stats['submitted'] ?? 0));
        $approvedRequests = (int) ($stats['approved'] ?? 0);
        $partiallyFulfilled = (int) ($stats['partially_fulfilled'] ?? 0);
        $fulfilledRequests = (int) ($stats['fulfilled'] ?? 0);
        $rejectedRequests = (int) ($stats['rejected'] ?? 0);
        $activePipeline = $pendingReview + $approvedRequests + $partiallyFulfilled;
        $fulfillmentRate = $totalRequests > 0 ? round(($fulfilledRequests / $totalRequests) * 100) : 0;
        $statProgress = fn (int $value) => $totalRequests > 0 ? min(100, round(($value / $totalRequests) * 100)) : 0;
        $requestOrderCards = [
            [
                'id' => 'stats-total',
                'progress_id' => 'stats-total-progress',
                'title' => 'Total Request',
                'chip' => 'Semua',
                'icon' => 'fa-list-alt',
                'tone' => 'blue',
                'value' => $totalRequests,
                'description' => 'Seluruh request order',
                'progress' => $totalRequests > 0 ? 100 : 0,
            ],
            [
                'id' => 'stats-pending-review',
                'progress_id' => 'stats-pending-review-progress',
                'title' => 'Pending Review',
                'chip' => 'Review',
                'icon' => 'fa-hourglass-half',
                'tone' => 'slate',
                'value' => $pendingReview,
                'description' => 'Menunggu persetujuan',
                'progress' => $statProgress($pendingReview),
            ],
            [
                'id' => 'stats-approved',
                'progress_id' => 'stats-approved-progress',
                'title' => 'Approved',
                'chip' => 'Setuju',
                'icon' => 'fa-check-circle',
                'tone' => 'green',
                'value' => $approvedRequests,
                'description' => 'Siap dipenuhi',
                'progress' => $statProgress($approvedRequests),
            ],
            [
                'id' => 'stats-partially-fulfilled',
                'progress_id' => 'stats-partially-fulfilled-progress',
                'title' => 'Partially Fulfilled',
                'chip' => 'Parsial',
                'icon' => 'fa-tasks',
                'tone' => 'amber',
                'value' => $partiallyFulfilled,
                'description' => 'Sebagian terpenuhi',
                'progress' => $statProgress($partiallyFulfilled),
            ],
            [
                'id' => 'stats-fulfilled',
                'progress_id' => 'stats-fulfilled-progress',
                'title' => 'Fulfilled',
                'chip' => 'Selesai',
                'icon' => 'fa-clipboard-check',
                'tone' => 'teal',
                'value' => $fulfilledRequests,
                'description' => 'Sudah terpenuhi',
                'progress' => $statProgress($fulfilledRequests),
            ],
            [
                'id' => 'stats-rejected',
                'progress_id' => 'stats-rejected-progress',
                'title' => 'Rejected',
                'chip' => 'Tolak',
                'icon' => 'fa-times-circle',
                'tone' => 'red',
                'value' => $rejectedRequests,
                'description' => 'Tidak dilanjutkan',
                'progress' => $statProgress($rejectedRequests),
            ],
        ];
    @endphp

    <div class="main-content request-order-page">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show ro-alert" role="alert">
                <i class="fa fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="mb-4">
            <div class="ro-page-heading mb-3">
                <div>
                    <span class="ro-eyebrow">Alur Gudang</span>
                    <h2 class="h4 mb-1 font-weight-bold">Request Order</h2>
                    <p class="text-muted small mb-0">Draft kebutuhan bahan baku dari outlet, kitchen, bar, atau pastry sebelum masuk review.</p>
                </div>
                <a href="{{ route('warehouse/request-order/create') }}" class="btn btn-primary btn-round">
                    <i class="fa fa-plus me-2"></i>Tambah Request
                </a>
            </div>

            <div class="ro-overview-grid">
                <div class="ro-preview-card">
                    <div class="ro-preview-copy">
                        <span class="ro-preview-kicker">Ringkasan request</span>
                        <h3 class="mb-2">Alur request tetap terbaca cepat.</h3>
                        <p class="mb-0">Pantau request yang masih berjalan, sudah selesai, dan butuh tindak lanjut tanpa harus membuka detail satu per satu.</p>
                    </div>
                    <div class="ro-preview-metrics">
                        <div>
                            <span>Total</span>
                            <strong id="stats-total-preview">{{ $totalRequests }}</strong>
                        </div>
                        <div>
                            <span>Berjalan</span>
                            <strong id="stats-active-pipeline">{{ $activePipeline }}</strong>
                        </div>
                        <div>
                            <span>Selesai</span>
                            <strong id="stats-fulfilled-inline">{{ $fulfilledRequests }}</strong>
                        </div>
                    </div>
                    <div class="ro-preview-progress">
                        <div class="ro-preview-progress-label">
                            <span>Tingkat terpenuhi</span>
                            <strong id="stats-fulfillment-rate">{{ $fulfillmentRate }}%</strong>
                        </div>
                        <div class="ro-progress-track">
                            <span id="stats-fulfillment-rate-bar" style="width: {{ $fulfillmentRate }}%;"></span>
                        </div>
                    </div>
                    <i class="fa fa-clipboard-list ro-preview-watermark"></i>
                </div>

                <div class="ro-stat-grid">
                    @foreach ($requestOrderCards as $card)
                        <div class="card ro-stat-card stat-{{ $card['tone'] }} h-100">
                            <div class="card-body">
                                <div class="ro-stat-top">
                                    <span class="ro-stat-icon"><i class="fa {{ $card['icon'] }}"></i></span>
                                    <span class="ro-stat-chip">{{ $card['chip'] }}</span>
                                </div>
                                <p class="ro-stat-title text-muted small mb-1">{{ $card['title'] }}</p>
                                <div class="ro-stat-value-row">
                                    <h3 class="mb-0 font-weight-bold" id="{{ $card['id'] }}">{{ $card['value'] }}</h3>
                                    <small class="text-muted" id="{{ $card['progress_id'] }}-label">{{ $card['progress'] }}%</small>
                                </div>
                                <div class="ro-card-progress">
                                    <span id="{{ $card['progress_id'] }}" style="width: {{ $card['progress'] }}%;"></span>
                                </div>
                                <small class="text-muted">{{ $card['description'] }}</small>
                            </div>
                        </div>
                    @endforeach
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
                    <div class="ro-filter-control">
                        <i class="fa fa-flag"></i>
                        <select id="filterStatus" class="form-select form-select-sm">
                            <option value="">Semua Status</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->code }}">{{ $status->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ro-filter-control">
                        <i class="fa fa-store"></i>
                        <select id="filterRequester" class="form-select form-select-sm">
                            <option value="">Semua Pemohon</option>
                            @foreach ($inventories as $inventory)
                                <option value="{{ $inventory->name }}">{{ $inventory->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ro-filter-control">
                        <i class="fa fa-truck-loading"></i>
                        <select id="filterFulfillment" class="form-select form-select-sm">
                            <option value="">Semua Pemenuhan</option>
                            <option value="__empty__">Belum ditentukan</option>
                            @foreach ($inventories as $inventory)
                                <option value="{{ $inventory->name }}">{{ $inventory->name }}</option>
                            @endforeach
                        </select>
                    </div>
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

                const total = Number(data.total ?? 0);
                const statsMap = {
                    total: {
                        value: total,
                        valueSelector: '#stats-total',
                        progressSelector: '#stats-total-progress'
                    },
                    pendingReview: {
                        value: Number(data.pending_review ?? data.submitted ?? 0),
                        valueSelector: '#stats-pending-review',
                        progressSelector: '#stats-pending-review-progress'
                    },
                    approved: {
                        value: Number(data.approved ?? 0),
                        valueSelector: '#stats-approved',
                        progressSelector: '#stats-approved-progress'
                    },
                    partiallyFulfilled: {
                        value: Number(data.partially_fulfilled ?? 0),
                        valueSelector: '#stats-partially-fulfilled',
                        progressSelector: '#stats-partially-fulfilled-progress'
                    },
                    fulfilled: {
                        value: Number(data.fulfilled ?? 0),
                        valueSelector: '#stats-fulfilled',
                        progressSelector: '#stats-fulfilled-progress'
                    },
                    rejected: {
                        value: Number(data.rejected ?? 0),
                        valueSelector: '#stats-rejected',
                        progressSelector: '#stats-rejected-progress'
                    }
                };

                function progress(value) {
                    return total > 0 ? Math.min(100, Math.round((value / total) * 100)) : 0;
                }

                Object.values(statsMap).forEach(function(item) {
                    const percentage = item.valueSelector === '#stats-total' && total > 0 ? 100 : progress(item.value);

                    $(item.valueSelector).text(item.value);
                    $(item.progressSelector).css('width', percentage + '%');
                    $(item.progressSelector + '-label').text(percentage + '%');
                });

                const pendingReview = statsMap.pendingReview.value;
                const approved = statsMap.approved.value;
                const partiallyFulfilled = statsMap.partiallyFulfilled.value;
                const fulfilled = Number(data.fulfilled ?? 0);
                const fulfillmentRate = progress(fulfilled);

                $('#stats-total-preview').text(total);
                $('#stats-active-pipeline').text(pendingReview + approved + partiallyFulfilled);
                $('#stats-fulfilled-inline').text(fulfilled);
                $('#stats-fulfillment-rate').text(fulfillmentRate + '%');
                $('#stats-fulfillment-rate-bar').css('width', fulfillmentRate + '%');
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
            .request-order-page {
                --ro-border: rgba(18, 38, 63, 0.09);
                --ro-soft: #f7f9fc;
                --ro-ink: #22304a;
                --ro-muted: #667085;
                --ro-blue: #2f6fcf;
                --ro-green: #15965f;
                --ro-amber: #d89012;
                --ro-teal: #0f8f8a;
                --ro-red: #d94d5f;
            }

            .ro-alert {
                border: 1px solid rgba(21, 150, 95, 0.18);
                border-radius: 8px;
                box-shadow: 0 10px 26px rgba(18, 38, 63, 0.06);
            }

            .ro-page-heading {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 18px;
                padding: 18px;
                background:
                    linear-gradient(135deg, rgba(47, 111, 207, 0.08), rgba(15, 143, 138, 0.08)),
                    #fff;
                border: 1px solid var(--ro-border);
                border-radius: 8px;
            }

            .ro-eyebrow {
                display: inline-flex;
                align-items: center;
                min-height: 22px;
                padding: 3px 9px;
                margin-bottom: 7px;
                border-radius: 999px;
                background: rgba(47, 111, 207, 0.1);
                color: var(--ro-blue);
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0;
                text-transform: uppercase;
            }

            .request-order-page .card,
            .ro-preview-card,
            .ro-table-shell {
                border: 1px solid var(--ro-border);
                border-radius: 8px;
            }

            .ro-overview-grid {
                display: grid;
                grid-template-columns: minmax(280px, 0.95fr) minmax(0, 1.55fr);
                gap: 16px;
            }

            .ro-preview-card {
                position: relative;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                min-height: 100%;
                padding: 20px;
                overflow: hidden;
                color: #fff;
                background:
                    linear-gradient(135deg, rgba(255, 255, 255, 0.14) 0%, rgba(255, 255, 255, 0.14) 18%, transparent 18%, transparent 100%),
                    linear-gradient(155deg, #263f8f 0%, #1f6f84 54%, #19324c 100%);
                box-shadow: 0 18px 38px rgba(31, 63, 126, 0.18);
            }

            .ro-preview-card::after {
                content: "";
                position: absolute;
                right: 0;
                bottom: 0;
                width: 58%;
                height: 44%;
                background: linear-gradient(135deg, transparent 0%, transparent 36%, rgba(255, 255, 255, 0.12) 36%, rgba(255, 255, 255, 0.12) 100%);
                pointer-events: none;
            }

            .ro-preview-copy,
            .ro-preview-metrics,
            .ro-preview-progress {
                position: relative;
                z-index: 1;
            }

            .ro-preview-kicker {
                display: inline-flex;
                align-items: center;
                min-height: 24px;
                padding: 4px 10px;
                margin-bottom: 14px;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.16);
                color: rgba(255, 255, 255, 0.92);
                font-size: 12px;
                font-weight: 800;
                letter-spacing: 0;
                text-transform: uppercase;
            }

            .ro-preview-copy h3 {
                max-width: 360px;
                color: #fff;
                font-size: 24px;
                font-weight: 800;
                line-height: 1.22;
            }

            .ro-preview-copy p {
                max-width: 380px;
                color: rgba(255, 255, 255, 0.78);
                line-height: 1.55;
            }

            .ro-preview-metrics {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 8px;
                margin: 24px 0 18px;
            }

            .ro-preview-metrics div {
                min-width: 0;
                padding: 12px;
                border: 1px solid rgba(255, 255, 255, 0.15);
                border-radius: 8px;
                background: rgba(255, 255, 255, 0.1);
            }

            .ro-preview-metrics span,
            .ro-preview-progress-label span {
                display: block;
                color: rgba(255, 255, 255, 0.72);
                font-size: 12px;
                font-weight: 700;
            }

            .ro-preview-metrics strong {
                display: block;
                margin-top: 3px;
                color: #fff;
                font-size: 24px;
                font-weight: 800;
                line-height: 1;
            }

            .ro-preview-progress-label {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                margin-bottom: 9px;
            }

            .ro-preview-progress-label strong {
                color: #fff;
                font-size: 18px;
                font-weight: 800;
            }

            .ro-progress-track,
            .ro-card-progress {
                position: relative;
                overflow: hidden;
                border-radius: 999px;
            }

            .ro-progress-track {
                height: 8px;
                background: rgba(255, 255, 255, 0.18);
            }

            .ro-progress-track span,
            .ro-card-progress span {
                display: block;
                height: 100%;
                border-radius: inherit;
                transition: width 0.25s ease;
            }

            .ro-progress-track span {
                background: linear-gradient(90deg, #ffffff, #87e0c4);
            }

            .ro-preview-watermark {
                position: absolute;
                right: 18px;
                top: 18px;
                color: rgba(255, 255, 255, 0.12);
                font-size: 82px;
                transform: rotate(-8deg);
                pointer-events: none;
            }

            .ro-stat-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 12px;
            }

            .ro-stat-card {
                --tone: var(--ro-blue);
                --tone-soft: rgba(47, 111, 207, 0.08);
                position: relative;
                overflow: hidden;
                background:
                    linear-gradient(145deg, #ffffff 0%, #ffffff 48%, var(--tone-soft) 100%);
                transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            }

            .ro-stat-card:hover {
                transform: translateY(-2px);
                border-color: var(--tone-soft);
                box-shadow: 0 12px 28px rgba(18, 38, 63, 0.08);
            }

            .ro-stat-card .card-body {
                position: relative;
                z-index: 1;
                padding: 15px;
            }

            .ro-stat-top {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
                margin-bottom: 14px;
            }

            .ro-stat-chip {
                display: inline-flex;
                align-items: center;
                min-height: 24px;
                padding: 3px 9px;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.72);
                color: var(--tone);
                font-size: 12px;
                font-weight: 800;
                white-space: nowrap;
                box-shadow: inset 0 0 0 1px var(--tone-soft);
            }

            .ro-stat-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 36px;
                width: 36px;
                height: 36px;
                border-radius: 8px;
                background: var(--tone-soft);
                color: var(--tone);
                font-size: 15px;
            }

            .ro-stat-title {
                font-weight: 700;
                line-height: 1.2;
            }

            .ro-stat-value-row {
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                gap: 10px;
                margin-bottom: 10px;
            }

            .ro-stat-value-row h3 {
                color: var(--ro-ink);
                font-size: 25px;
                line-height: 1;
            }

            .ro-stat-value-row small {
                color: var(--tone) !important;
                font-weight: 800;
            }

            .ro-card-progress {
                height: 5px;
                margin-bottom: 9px;
                background: rgba(18, 38, 63, 0.08);
            }

            .ro-card-progress span {
                background: var(--tone);
            }

            .ro-stat-card::before {
                content: "";
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 4px;
                background: var(--tone);
            }

            .stat-blue {
                --tone: var(--ro-blue);
                --tone-soft: rgba(47, 111, 207, 0.09);
            }

            .stat-slate {
                --tone: #5b6474;
                --tone-soft: rgba(91, 100, 116, 0.1);
            }

            .stat-amber {
                --tone: var(--ro-amber);
                --tone-soft: rgba(216, 144, 18, 0.13);
            }

            .stat-green {
                --tone: var(--ro-green);
                --tone-soft: rgba(21, 150, 95, 0.11);
            }

            .stat-teal {
                --tone: var(--ro-teal);
                --tone-soft: rgba(15, 143, 138, 0.12);
            }

            .stat-red {
                --tone: var(--ro-red);
                --tone-soft: rgba(217, 77, 95, 0.11);
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
                background: linear-gradient(180deg, #fbfcfe 0%, #ffffff 100%);
                border-bottom: 1px solid var(--ro-border);
            }

            .ro-toolbar-actions {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 8px;
                flex-wrap: wrap;
            }

            .ro-filter-control {
                position: relative;
                min-width: 168px;
            }

            .ro-filter-control i {
                position: absolute;
                top: 50%;
                left: 12px;
                z-index: 2;
                color: #8a94a6;
                font-size: 12px;
                transform: translateY(-50%);
                pointer-events: none;
            }

            .ro-filter-control .form-select {
                min-height: 34px;
                padding-left: 34px;
                border-color: var(--ro-border);
                background-color: #f8fafc;
                color: #344054;
                font-weight: 600;
            }

            .ro-filter-control .form-select:focus {
                border-color: rgba(47, 111, 207, 0.45);
                box-shadow: 0 0 0 0.15rem rgba(47, 111, 207, 0.12);
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

            .request-order-page .dataTables_length label,
            .request-order-page .dataTables_filter label {
                color: var(--ro-muted);
                font-size: 13px;
                font-weight: 600;
            }

            .request-order-page .dataTables_filter input,
            .request-order-page .dataTables_length select {
                border: 1px solid var(--ro-border);
                border-radius: 8px;
                background-color: #f8fafc;
            }

            .request-order-page .dataTables_scroll {
                width: 100%;
            }

            .request-order-page .dataTables_scrollHead {
                border-top: 1px solid rgba(18, 38, 63, 0.06);
                border-bottom: 1px solid rgba(18, 38, 63, 0.06);
            }

            .request-order-page table.dataTable thead th {
                background: #f8fafc;
                color: #4b5565;
                font-size: 12px;
                font-weight: 800;
                letter-spacing: 0;
                text-transform: uppercase;
                white-space: nowrap;
            }

            .request-order-page table.dataTable tbody td {
                color: #344054;
                vertical-align: middle;
            }

            #request-order-table tbody tr {
                transition: background-color 0.18s ease;
            }

            #request-order-table tbody tr:hover {
                background-color: rgba(47, 111, 207, 0.04);
            }

            #request-order-table .badge {
                min-height: 24px;
                padding: 5px 9px;
                border-radius: 999px;
                font-weight: 700;
            }

            #request-order-table .btn-group .btn {
                border-radius: 8px;
                font-weight: 700;
                box-shadow: 0 6px 14px rgba(47, 111, 207, 0.16);
            }

            .request-order-page .dropdown-menu {
                border: 1px solid var(--ro-border);
                border-radius: 8px;
                box-shadow: 0 12px 32px rgba(15, 23, 42, 0.12);
            }

            .request-order-page .dropdown-item {
                display: flex;
                align-items: center;
                min-height: 34px;
                gap: 4px;
                font-weight: 600;
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
                .ro-page-heading {
                    align-items: stretch;
                    flex-direction: column;
                }

                .ro-page-heading .btn {
                    align-self: flex-start;
                }

                .ro-overview-grid {
                    grid-template-columns: 1fr;
                }

                .ro-stat-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .ro-table-toolbar {
                    align-items: stretch;
                    flex-direction: column;
                }

                .ro-toolbar-actions {
                    justify-content: stretch;
                }

                .ro-filter-control {
                    width: 100%;
                }
            }

            @media (max-width: 767.98px) {
                .ro-preview-copy h3 {
                    font-size: 21px;
                }

                .ro-preview-metrics {
                    grid-template-columns: 1fr;
                }

                .ro-stat-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
    @endpush
@endsection

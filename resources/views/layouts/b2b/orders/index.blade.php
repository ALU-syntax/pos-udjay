@extends('layouts.app')

@php
    $number = fn ($value) => number_format((int) $value, 0, ',', '.');
@endphp

@section('content')
    @include('layouts.b2b.partials.assets')
    @include('layouts.b2b.partials.page-styles')
    @include('layouts.b2b.partials.datepicker')

    <div class="main-content ob-page">
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Kasir B2B</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-shopping-bag"></i></span>
                    Pesanan B2B
                </h1>
                <p class="ob-header__desc">Pesanan dari Kasir B2B di outlet yang bisa Anda akses. Penagihan tempo dicatat di menu Invoice.</p>
            </div>
            @can('read b2b/cashier')
                <a href="{{ route('b2b/cashier') }}" class="btn ob-btn ob-btn-primary">
                    <i class="fas fa-cash-register" aria-hidden="true"></i> Buka Kasir B2B
                </a>
            @endcan
        </div>

        <div class="ob-stats">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-calendar-check"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Pesanan bulan ini</p>
                    <p class="ob-stat__value">{{ $number($stats['month_count']) }}</p>
                    <p class="b2b-stat__sub">Rp {{ $number($stats['month_amount']) }}</p>
                </div>
            </div>
            <button type="button" class="ob-card ob-stat b2b-stat-filter" data-filter="status" data-value="confirmed" aria-pressed="false">
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-box-open"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Perlu diselesaikan</p>
                    <p class="ob-stat__value">{{ $number($stats['confirmed']) }}</p>
                    <p class="b2b-stat__sub">Barang belum diserahkan</p>
                </div>
            </button>
            <button type="button" class="ob-card ob-stat b2b-stat-filter" data-filter="payment" data-value="unpaid" aria-pressed="false">
                <span class="ob-stat__icon ob-stat__icon--success" aria-hidden="true"><i class="fas fa-file-invoice-dollar"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Tempo belum lunas</p>
                    <p class="ob-stat__value">{{ $number($stats['unpaid_count']) }}</p>
                    <p class="b2b-stat__sub">Sisa Rp {{ $number($stats['unpaid_amount']) }}</p>
                </div>
            </button>
            <button type="button" class="ob-card ob-stat b2b-stat-filter" data-filter="payment" data-value="overdue" aria-pressed="false">
                <span class="ob-stat__icon ob-stat__icon--danger" aria-hidden="true"><i class="fas fa-exclamation-circle"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Lewat jatuh tempo</p>
                    <p class="ob-stat__value {{ $stats['overdue_count'] ? 'text-danger' : '' }}">{{ $number($stats['overdue_count']) }}</p>
                    <p class="b2b-stat__sub">Sisa Rp {{ $number($stats['overdue_amount']) }}</p>
                </div>
            </button>
        </div>

        <div class="ob-card">
            <div class="b2b-card__head">
                <div>
                    <h2 class="b2b-card__title"><i class="fas fa-list" aria-hidden="true"></i> Daftar Pesanan</h2>
                    <p class="b2b-card__desc">Klik kartu di atas untuk menyaring cepat. Klik lagi untuk melepas.</p>
                </div>
                <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" id="filter-reset" hidden>
                    <i class="fas fa-times" aria-hidden="true"></i> Hapus filter
                </button>
            </div>

            <div class="b2b-filter-row">
                <div class="ob-field">
                    <label for="outlet-filter"><i class="fas fa-store" aria-hidden="true"></i> Outlet</label>
                    <select id="outlet-filter" class="form-select">
                        <option value="">Semua outlet</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ob-field">
                    <label for="status-filter"><i class="fas fa-filter" aria-hidden="true"></i> Status pesanan</label>
                    <select id="status-filter" class="form-select">
                        <option value="">Semua status</option>
                        <option value="confirmed">Dikonfirmasi</option>
                        <option value="completed">Selesai</option>
                        <option value="cancelled">Dibatalkan</option>
                    </select>
                </div>
                <div class="ob-field">
                    <label for="payment-filter"><i class="fas fa-wallet" aria-hidden="true"></i> Pembayaran</label>
                    <select id="payment-filter" class="form-select">
                        <option value="">Semua pembayaran</option>
                        <option value="unpaid">Tempo belum lunas</option>
                        <option value="overdue">Lewat jatuh tempo</option>
                        <option value="paid">Lunas</option>
                        <option value="immediate">Bayar langsung</option>
                    </select>
                </div>
                <div class="ob-field b2b-form">
                    <label for="period-filter"><i class="far fa-calendar" aria-hidden="true"></i> Tanggal pesanan</label>
                    <div class="b2b-range">
                        <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                        <input id="period-filter" type="text" class="form-control" readonly placeholder="Semua tanggal" autocomplete="off">
                        <button type="button" class="b2b-range__clear" id="period-clear" aria-label="Hapus filter tanggal" hidden><i class="fas fa-times" aria-hidden="true"></i></button>
                    </div>
                    <input type="hidden" id="date-from">
                    <input type="hidden" id="date-to">
                </div>
            </div>

            <div class="ob-table-wrap">
                {!! $dataTable->table(['class' => 'table w-100']) !!}
            </div>
        </div>
    </div>

    @push('css')
        <style>
            .b2b-stat-filter { width: 100%; border: 1px solid var(--ob-border); text-align: left; cursor: pointer; transition: border-color .15s ease, box-shadow .15s ease; }
            .b2b-stat-filter:hover { border-color: var(--ob-border-strong); }
            .b2b-stat-filter:focus-visible { outline: 2px solid var(--ob-primary); outline-offset: 2px; }
            .b2b-stat-filter[aria-pressed="true"] { border-color: var(--ob-primary); box-shadow: 0 0 0 1px var(--ob-primary); }

            .b2b-filter-row {
                display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem;
                padding: 1rem 1.25rem; border-bottom: 1px solid var(--ob-border); background: var(--ob-surface-2);
            }
            @media (max-width: 1199.98px) { .b2b-filter-row { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            @media (max-width: 575.98px) { .b2b-filter-row { grid-template-columns: minmax(0, 1fr); } }
            .b2b-filter-row .ob-field label { display: block; margin-bottom: .3rem; font-size: .72rem !important; font-weight: 600; color: var(--ob-muted) !important; }
            .b2b-filter-row .form-select {
                min-height: 42px; padding: 0 2.25rem 0 .85rem; border: 1px solid var(--ob-border-strong); border-radius: var(--ob-radius-sm);
                background-color: var(--ob-surface); font-size: .875rem; color: var(--ob-text);
            }
            .b2b-filter-row .form-select.is-set,
            .b2b-form .b2b-range .form-control[readonly].is-set { border-color: var(--ob-primary) !important; background-color: var(--ob-primary-soft) !important; }
            .b2b-filter-row .b2b-range .form-control[readonly] { padding-right: 2.5rem; border-color: var(--ob-border-strong) !important; font-weight: 500; }
            .b2b-filter-row .b2b-range .form-control[readonly]::placeholder { color: var(--ob-muted); font-weight: 500; }
            .b2b-range__clear {
                position: absolute; top: 50%; right: .45rem; transform: translateY(-50%); display: inline-grid; place-items: center;
                width: 28px; height: 28px; border: 0; border-radius: 8px; background: transparent; color: var(--ob-muted); font-size: .75rem;
            }
            .b2b-range__clear:hover { background: var(--ob-surface); color: var(--ob-danger); }

            /* Sel tabel */
            #b2b-order-table .ob-cell-meta .fa-store { font-size: .68rem; }
            #b2b-order-table td { vertical-align: middle; }
            #b2b-order-table .ob-cell-meta .fa-file-signature { font-size: .68rem; }
            #b2b-order-table tbody tr { transition: background-color .12s ease; }
            #b2b-order-table tbody tr:hover { background: var(--ob-surface-2); }
        </style>
    @endpush

    @push('js')
        {!! $dataTable->scripts() !!}
        <script>
            $(function () {
                const table = () => window.LaravelDataTables['b2b-order-table'];
                const selects = $('#outlet-filter, #status-filter, #payment-filter');
                const period = $('#period-filter');
                const from = $('#date-from');
                const to = $('#date-to');

                function syncState() {
                    selects.each(function () { $(this).toggleClass('is-set', this.value !== ''); });
                    period.toggleClass('is-set', from.val() !== '');
                    $('#period-clear').prop('hidden', from.val() === '');
                    $('.b2b-stat-filter').each(function () {
                        $(this).attr('aria-pressed', $(`#${this.dataset.filter}-filter`).val() === this.dataset.value);
                    });
                    $('#filter-reset').prop('hidden', !selects.toArray().some(el => el.value !== '') && from.val() === '');
                }
                const reload = () => { syncState(); table().ajax.reload(); };

                selects.on('change', reload);

                // Kartu ringkasan = filter cepat; klik lagi untuk melepas.
                $('.b2b-stat-filter').on('click', function () {
                    const select = $(`#${this.dataset.filter}-filter`);
                    select.val(select.val() === this.dataset.value ? '' : this.dataset.value);
                    reload();
                });

                // Rentang tanggal dengan tema yang sama seperti form kontrak.
                const shortMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                const label = date => `${date.format('DD')} ${shortMonths[date.month()]} ${date.year()}`;
                function setPeriod(start, end) {
                    if (!start) {
                        period.val('');
                        from.val('');
                        to.val('');
                    } else {
                        period.val(start.isSame(end, 'day') ? label(start) : `${label(start)}  –  ${label(end)}`);
                        from.val(start.format('YYYY-MM-DD'));
                        to.val(end.format('YYYY-MM-DD'));
                    }
                    reload();
                }

                if ($.fn.daterangepicker) {
                    const today = moment().startOf('day');
                    period.daterangepicker({
                        autoUpdateInput: false,
                        drops: 'auto',
                        opens: 'left',
                        showDropdowns: true,
                        linkedCalendars: false,
                        alwaysShowCalendars: true,
                        maxDate: today.clone().endOf('day'),
                        ranges: {
                            'Hari ini': [today.clone(), today.clone()],
                            '7 hari terakhir': [today.clone().subtract(6, 'days'), today.clone()],
                            'Bulan ini': [today.clone().startOf('month'), today.clone()],
                            'Bulan lalu': [today.clone().subtract(1, 'month').startOf('month'), today.clone().subtract(1, 'month').endOf('month')],
                        },
                        buttonClasses: 'btn btn-sm',
                        applyButtonClasses: 'btn-primary',
                        cancelButtonClasses: 'btn-light',
                        locale: {
                            format: 'DD/MM/YYYY',
                            separator: ' – ',
                            applyLabel: 'Terapkan',
                            cancelLabel: 'Batal',
                            customRangeLabel: 'Pilih sendiri',
                            daysOfWeek: ['Mg', 'Sn', 'Sl', 'Rb', 'Km', 'Jm', 'Sb'],
                            monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                            firstDay: 1,
                        },
                    });
                    const picker = period.data('daterangepicker');
                    picker.container.addClass('b2b-drp');

                    // Ringkasan di footer popup dalam bahasa Indonesia, bukan format bawaan DD/MM/YYYY.
                    function renderSelected() {
                        const box = picker.container.find('.drp-selected');
                        if (!picker.endDate) {
                            box.html(`<strong>${label(picker.startDate)}  –  …</strong><small>Pilih tanggal akhir</small>`);
                            return;
                        }
                        const days = picker.endDate.clone().startOf('day').diff(picker.startDate.clone().startOf('day'), 'days') + 1;
                        box.html(`<strong>${label(picker.startDate)}  –  ${label(picker.endDate)}</strong><small class="is-days">${days.toLocaleString('id-ID')} hari</small>`);
                    }
                    ['updateView', 'setEndDate'].forEach(name => {
                        const original = picker[name];
                        picker[name] = function () {
                            const result = original.apply(this, arguments);
                            renderSelected();
                            return result;
                        };
                    });

                    period.on('show.daterangepicker', () => { period.addClass('is-open'); renderSelected(); })
                        .on('hide.daterangepicker', () => period.removeClass('is-open'))
                        .on('apply.daterangepicker', (event, picker) => setPeriod(picker.startDate, picker.endDate));
                }

                $('#period-clear').on('click', () => setPeriod(null));
                $('#filter-reset').on('click', function () {
                    selects.val('');
                    setPeriod(null);
                });

                syncState();
            });
        </script>
    @endpush
@endsection

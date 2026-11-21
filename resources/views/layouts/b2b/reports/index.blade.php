@extends('layouts.app')

@section('content')
    @include('layouts.b2b.partials.assets')
    @include('layouts.b2b.partials.page-styles')
    @include('layouts.b2b.partials.list-filters')

    <div class="main-content ob-page b2b-report">
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Kasir B2B</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-chart-pie"></i></span>
                    Laporan B2B
                </h1>
                <p class="ob-header__desc">Ringkasan penjualan, kas, dan piutang customer B2B. Semua bagian mengikuti filter di bawah; angka piutang selalu per hari ini.</p>
            </div>
        </div>

        {{-- Filter bersama untuk semua bagian; menempel di atas saat halaman digulir. --}}
        <div class="ob-card b2b-report-filter">
            <div class="b2b-filter-row b2b-report-filter__row">
                <div class="ob-field b2b-form">
                    <label for="report-period"><i class="far fa-calendar" aria-hidden="true"></i> Periode</label>
                    <div class="b2b-range">
                        <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                        <input id="report-period" type="text" class="form-control" readonly autocomplete="off">
                    </div>
                </div>
                <div class="ob-field">
                    <label for="report-outlet"><i class="fas fa-store" aria-hidden="true"></i> Outlet</label>
                    <select id="report-outlet" class="form-select">
                        <option value="">Semua outlet</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected($filter->outletId === $outlet->id)>{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ob-field">
                    <label for="report-customer"><i class="fas fa-building" aria-hidden="true"></i> Customer</label>
                    <select id="report-customer" class="form-select" data-select2-url="{{ route('b2b/selectors/customers') }}" data-placeholder="Semua customer">
                        <option value=""></option>
                        @if ($customer)
                            <option value="{{ $customer->id }}" selected>{{ $customer->name }}</option>
                        @endif
                    </select>
                </div>
                <div class="b2b-report-filter__actions">
                    <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" id="report-reset" hidden>
                        <i class="fas fa-undo" aria-hidden="true"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        <section class="ob-stats b2b-report-kpis" data-report-section="summary" aria-live="polite" aria-busy="true">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-shopping-bag"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Penjualan</p>
                    <p class="ob-stat__value" data-kpi="sales"><span class="b2b-skeleton"></span></p>
                    <p class="b2b-stat__sub" data-kpi="sales-sub">&nbsp;</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-receipt"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Jumlah pesanan</p>
                    <p class="ob-stat__value" data-kpi="orders"><span class="b2b-skeleton"></span></p>
                    <p class="b2b-stat__sub" data-kpi="orders-sub">&nbsp;</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--success" aria-hidden="true"><i class="fas fa-hand-holding-usd"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Kas masuk bersih</p>
                    <p class="ob-stat__value" data-kpi="cash"><span class="b2b-skeleton"></span></p>
                    <p class="b2b-stat__sub" data-kpi="cash-sub">&nbsp;</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-file-invoice-dollar"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Piutang berjalan <span class="b2b-report-asof">per hari ini</span></p>
                    <p class="ob-stat__value" data-kpi="receivable"><span class="b2b-skeleton"></span></p>
                    <p class="b2b-stat__sub" data-kpi="receivable-sub">&nbsp;</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--danger" aria-hidden="true"><i class="fas fa-exclamation-circle"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Lewat jatuh tempo <span class="b2b-report-asof">per hari ini</span></p>
                    <p class="ob-stat__value" data-kpi="overdue"><span class="b2b-skeleton"></span></p>
                    <p class="b2b-stat__sub" data-kpi="overdue-sub">&nbsp;</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon b2b-stat__icon--muted" aria-hidden="true"><i class="fas fa-ban"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Pesanan dibatalkan</p>
                    <p class="ob-stat__value" data-kpi="cancelled"><span class="b2b-skeleton"></span></p>
                    <p class="b2b-stat__sub" data-kpi="cancelled-sub">&nbsp;</p>
                </div>
            </div>
            <div class="b2b-report-error" data-report-error hidden>
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <span data-report-error-text>Ringkasan gagal dimuat.</span>
                <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" data-report-retry>Coba lagi</button>
            </div>
        </section>
    </div>

    @push('css')
        <style>
            /* Filter menempel di bawah navbar Kaiadmin saat digulir. */
            .b2b-report-filter { position: sticky; top: calc(var(--b2b-navbar-height, 70px) + .5rem); z-index: 20; margin-bottom: 1.25rem; overflow: visible; }
            .b2b-report-filter .b2b-filter-row { border-bottom: 0; border-radius: var(--ob-radius); }
            .b2b-report-filter__row { grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr) minmax(0, 1.2fr) auto; align-items: end; }
            .b2b-report-filter__actions { display: flex; align-items: center; min-height: 42px; }
            @media (max-width: 1199.98px) {
                .b2b-report-filter { position: static; }
                .b2b-report-filter__row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            }
            @media (max-width: 575.98px) { .b2b-report-filter__row { grid-template-columns: minmax(0, 1fr); } }
            .b2b-report .b2b-range .form-control[readonly] { padding-right: .85rem; }

            .b2b-report .ob-stat__value { font-size: 1.15rem; font-variant-numeric: tabular-nums; }
            .b2b-report .ob-stat__label { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; }
            .b2b-report-asof { padding: .05rem .4rem; border-radius: 999px; background: var(--ob-border); font-size: .62rem; font-weight: 600; letter-spacing: .02em; color: var(--ob-text-2); text-transform: none; }
            .b2b-stat__icon--muted { background: var(--ob-border); color: var(--ob-text-2); }
            /* .ob-page di depan karena ui-styles (.ob-stats auto-fit) dicetak setelah stack css. */
            .ob-page .b2b-report-kpis { position: relative; margin-bottom: 1.25rem; grid-template-columns: repeat(3, minmax(0, 1fr)); }
            @media (max-width: 767.98px) { .ob-page .b2b-report-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            .b2b-report-kpis[aria-busy="true"] .ob-stat__value,
            .b2b-report-kpis[aria-busy="true"] .b2b-stat__sub { opacity: .45; }

            .b2b-skeleton { display: inline-block; width: 8rem; height: 1.1rem; border-radius: 6px; vertical-align: middle;
                background: linear-gradient(90deg, var(--ob-border) 25%, var(--ob-surface-2) 50%, var(--ob-border) 75%); background-size: 200% 100%;
                animation: b2b-skeleton 1.2s ease-in-out infinite; }
            @keyframes b2b-skeleton { from { background-position: 200% 0; } to { background-position: -200% 0; } }
            @media (prefers-reduced-motion: reduce) { .b2b-skeleton { animation: none; } }

            .b2b-report-error { grid-column: 1 / -1; display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; padding: .75rem 1rem;
                border: 1px solid var(--ob-danger-soft); border-radius: var(--ob-radius-sm); background: var(--ob-danger-soft); color: var(--ob-danger); font-size: .85rem; }
            .b2b-report-error[hidden] { display: none; }
        </style>
    @endpush

    @push('js')
        <script>
            /**
             * Laporan B2B: satu filter untuk semua bagian. Setiap bagian didaftarkan dengan B2bReport.section(nama, render)
             * dan mengambil datanya sendiri dari b2b/reports/data/{nama}; ganti filter memuat ulang semua bagian.
             */
            window.B2bReport = (function () {
                const dataUrl = @json(route('b2b/reports/data', ['section' => '__section__']));
                const defaults = @json(['from' => today()->startOfMonth()->toDateString(), 'to' => today()->toDateString()]);
                const state = Object.assign({ outlet_id: '', customer_id: '' }, @json($filter->toQuery()));
                const sections = {};
                const pending = {};

                const number = value => Number(value || 0).toLocaleString('id-ID');
                const rupiah = value => (value < 0 ? '−' : '') + 'Rp ' + number(Math.abs(value));

                function query() {
                    return new URLSearchParams(Object.fromEntries(Object.entries(state).filter(([, value]) => value !== '' && value != null)));
                }

                function isDefault() {
                    return state.from === defaults.from && state.to === defaults.to && !state.outlet_id && !state.customer_id;
                }

                function load(name) {
                    const section = sections[name];
                    const $box = $(`[data-report-section="${name}"]`);
                    pending[name]?.abort();
                    const controller = new AbortController();
                    pending[name] = controller;

                    $box.attr('aria-busy', 'true').find('[data-report-error]').prop('hidden', true);
                    fetch(`${dataUrl.replace('__section__', name)}?${query()}`, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        signal: controller.signal,
                    })
                        .then(async response => {
                            const body = await response.json().catch(() => ({}));
                            if (!response.ok) throw new Error(body.message || 'Data gagal dimuat.');
                            section.render(body.data, $box);
                            $box.attr('aria-busy', 'false');
                        })
                        .catch(error => {
                            if (error.name === 'AbortError') return;
                            $box.attr('aria-busy', 'false');
                            $box.find('[data-report-error-text]').text(error.message || 'Data gagal dimuat.');
                            $box.find('[data-report-error]').prop('hidden', false);
                        });
                }

                function reloadAll() {
                    window.history.replaceState(null, '', `${window.location.pathname}?${query()}`);
                    $('#report-reset').prop('hidden', isDefault());
                    Object.keys(sections).forEach(load);
                }

                function setFilter(patch) {
                    Object.assign(state, patch);
                    reloadAll();
                }

                $(document).on('click', '[data-report-retry]', function () {
                    load($(this).closest('[data-report-section]').data('report-section'));
                });

                return {
                    number, rupiah, state, setFilter, reloadAll,
                    section(name, render) { sections[name] = { render }; },
                };
            })();

            // Ringkasan (kartu KPI)
            B2bReport.section('summary', function (data, $box) {
                const { number, rupiah } = B2bReport;
                const set = (key, text) => $box.find(`[data-kpi="${key}"]`).text(text);

                set('sales', rupiah(data.sales.amount));
                set('sales-sub', `${number(data.sales.orders)} pesanan · ${number(data.sales.customers)} customer`);
                set('orders', number(data.sales.orders));
                set('orders-sub', `${number(data.sales.completed)} selesai · ${number(data.sales.confirmed)} dikonfirmasi`);
                set('cash', rupiah(data.cash.net));
                set('cash-sub', data.cash.refunded
                    ? `Masuk ${rupiah(data.cash.received)} · refund ${rupiah(data.cash.refunded)}`
                    : `${number(data.cash.received_count)} pembayaran diterima`);
                set('receivable', rupiah(data.receivable.amount));
                set('receivable-sub', `${number(data.receivable.invoices)} invoice · ${number(data.receivable.customers)} customer`);
                set('overdue', rupiah(data.overdue.amount));
                set('overdue-sub', `${number(data.overdue.invoices)} invoice · ${number(data.overdue.customers)} customer`);
                $box.find('[data-kpi="overdue"]').toggleClass('text-danger', data.overdue.invoices > 0);
                set('cancelled', rupiah(data.cancelled.amount));
                set('cancelled-sub', `${number(data.cancelled.orders)} pesanan`);
            });

            $(function () {
                const $period = $('#report-period');
                const showPeriod = () => $period.val(B2b.rangeLabel(moment(B2bReport.state.from), moment(B2bReport.state.to)));
                B2b.rangePicker($period, {
                    ranges: 'report',
                    opens: 'right',
                    onApply: (start, end) => {
                        B2bReport.setFilter({ from: start.format('YYYY-MM-DD'), to: end.format('YYYY-MM-DD') });
                        showPeriod();
                    },
                });
                const picker = $period.data('daterangepicker');
                if (picker) {
                    picker.setStartDate(moment(B2bReport.state.from));
                    picker.setEndDate(moment(B2bReport.state.to));
                }
                showPeriod();

                $('#report-outlet').on('change', function () { B2bReport.setFilter({ outlet_id: this.value }); });
                B2b.ajaxSelect(document.getElementById('report-customer'))
                    .on('change', function () { B2bReport.setFilter({ customer_id: this.value || '' }); });

                $('#report-reset').on('click', function () {
                    const today = moment().startOf('day');
                    $('#report-outlet').val('');
                    $('#report-customer').val(null).trigger('change.select2');
                    if (picker) { picker.setStartDate(today.clone().startOf('month')); picker.setEndDate(today); }
                    B2bReport.setFilter({ from: today.clone().startOf('month').format('YYYY-MM-DD'), to: today.format('YYYY-MM-DD'), outlet_id: '', customer_id: '' });
                    showPeriod();
                });

                B2bReport.reloadAll();
            });
        </script>
    @endpush
@endsection

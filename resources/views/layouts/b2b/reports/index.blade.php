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
            {{-- Kartu penjualan membuka daftar pesanan periode ini. --}}
            <button type="button" class="ob-card ob-stat b2b-report-kpi-link" data-open-orders title="Lihat daftar pesanan">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-shopping-bag"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Penjualan</p>
                    <p class="ob-stat__value" data-kpi="sales"><span class="b2b-skeleton"></span></p>
                    <p class="b2b-stat__sub" data-kpi="sales-sub">&nbsp;</p>
                </div>
                <i class="fas fa-chevron-right b2b-report-kpi-link__arrow" aria-hidden="true"></i>
            </button>
            <button type="button" class="ob-card ob-stat b2b-report-kpi-link" data-open-orders title="Lihat daftar pesanan">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-receipt"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Jumlah pesanan</p>
                    <p class="ob-stat__value" data-kpi="orders"><span class="b2b-skeleton"></span></p>
                    <p class="b2b-stat__sub" data-kpi="orders-sub">&nbsp;</p>
                </div>
                <i class="fas fa-chevron-right b2b-report-kpi-link__arrow" aria-hidden="true"></i>
            </button>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--success" aria-hidden="true"><i class="fas fa-hand-holding-usd"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Kas masuk bersih</p>
                    <p class="ob-stat__value" data-kpi="cash"><span class="b2b-skeleton"></span></p>
                    <p class="b2b-stat__sub" data-kpi="cash-sub">&nbsp;</p>
                </div>
            </div>
            {{-- Kartu piutang membuka daftar invoice belum lunas (semua / yang lewat jatuh tempo). --}}
            <button type="button" class="ob-card ob-stat b2b-report-kpi-link" data-open-invoices title="Lihat invoice belum lunas">
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-file-invoice-dollar"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Piutang berjalan <span class="b2b-report-asof">per hari ini</span></p>
                    <p class="ob-stat__value" data-kpi="receivable"><span class="b2b-skeleton"></span></p>
                    <p class="b2b-stat__sub" data-kpi="receivable-sub">&nbsp;</p>
                </div>
                <i class="fas fa-chevron-right b2b-report-kpi-link__arrow" aria-hidden="true"></i>
            </button>
            <button type="button" class="ob-card ob-stat b2b-report-kpi-link" data-open-invoices="overdue" title="Lihat invoice lewat jatuh tempo">
                <span class="ob-stat__icon ob-stat__icon--danger" aria-hidden="true"><i class="fas fa-exclamation-circle"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Lewat jatuh tempo <span class="b2b-report-asof">per hari ini</span></p>
                    <p class="ob-stat__value" data-kpi="overdue"><span class="b2b-skeleton"></span></p>
                    <p class="b2b-stat__sub" data-kpi="overdue-sub">&nbsp;</p>
                </div>
                <i class="fas fa-chevron-right b2b-report-kpi-link__arrow" aria-hidden="true"></i>
            </button>
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

        @include('layouts.b2b.reports.partials.trend')
        @include('layouts.b2b.reports.partials.breakdown')
        @include('layouts.b2b.reports.partials.aging')
    </div>

    @include('layouts.b2b.reports.partials.list-modal')

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

            /* Kartu KPI yang bisa diklik */
            .ob-page .b2b-report-kpi-link { position: relative; width: 100%; border: 1px solid var(--ob-border); text-align: left; cursor: pointer; transition: border-color .15s ease, box-shadow .15s ease; }
            .ob-page .b2b-report-kpi-link:hover { border-color: var(--ob-primary); }
            .ob-page .b2b-report-kpi-link:focus-visible { outline: 2px solid var(--ob-primary); outline-offset: 2px; }
            .b2b-report-kpi-link__arrow { position: absolute; top: 1rem; right: 1rem; font-size: .7rem; color: var(--ob-muted); }
            .b2b-report-kpi-link:hover .b2b-report-kpi-link__arrow { color: var(--ob-primary); }

            /* Kartu bagian laporan (chart) */
            .b2b-report-card { margin-bottom: 1.25rem; }
            .b2b-report-card[aria-busy="true"] .b2b-report-chart { opacity: .45; }
            .b2b-report-card .b2b-report-error { margin-top: .75rem; }
            .b2b-report-chart { min-height: 220px; transition: opacity .15s ease; }
            .b2b-report-empty { display: grid; place-items: center; min-height: 240px; padding: 1rem; border: 1px dashed var(--ob-border-strong); border-radius: var(--ob-radius-sm); color: var(--ob-muted); font-size: .875rem; text-align: center; }
            .b2b-report-empty[hidden] { display: none; }
            /* Breadcrumb drilldown */
            .b2b-report-crumbs { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; margin-top: .25rem; font-size: .8rem; color: var(--ob-muted); }
            .b2b-report-crumbs[hidden] { display: none; }
            .b2b-report-crumbs i { font-size: .6rem; }
            .b2b-report-crumbs button { padding: 0; border: 0; background: none; color: var(--ob-primary); font-weight: 600; }
            .b2b-report-crumbs button:hover { text-decoration: underline; }
            .b2b-report-crumbs strong { color: var(--ob-text); }
            /* Pilihan pengelompokan (Hari / Minggu / Bulan) */
            .b2b-report-toggle { display: inline-flex; padding: 3px; border: 1px solid var(--ob-border-strong); border-radius: 10px; background: var(--ob-surface-2); }
            .b2b-report-toggle[hidden] { display: none; }
            .b2b-report-toggle button { min-height: 32px; padding: 0 .85rem; border: 0; border-radius: 7px; background: transparent; color: var(--ob-text-2); font-size: .78rem; font-weight: 600; }
            .b2b-report-toggle button[aria-pressed="true"],
            .b2b-report-toggle button[aria-selected="true"] { background: var(--ob-surface); color: var(--ob-primary); box-shadow: 0 1px 3px rgba(15, 23, 42, .12); }
            .b2b-report-toggle button:disabled { opacity: .4; cursor: not-allowed; }
            /* Tooltip chart */
            .b2b-report-tip { padding: .55rem .7rem; font-size: .78rem; line-height: 1.5; }
            .b2b-report-tip strong { display: block; color: var(--ob-text); }
            .b2b-report-tip span { color: var(--ob-muted); }
        </style>
    @endpush

    {{-- prepend: inti B2bReport harus terdefinisi sebelum script bagian (partial) yang mendaftarkan diri. --}}
    @prepend('js')
        <script>
            /**
             * Laporan B2B: satu filter untuk semua bagian. Setiap bagian didaftarkan dengan B2bReport.section(nama, render, params)
             * dan mengambil datanya sendiri dari b2b/reports/data/{nama}; ganti filter memuat ulang semua bagian.
             * Drilldown dan modal memakai B2bReport.fetch(nama, override) dengan filter yang sama + rentang/customer pilihan.
             */
            window.B2bReport = (function () {
                const dataUrl = @json(route('b2b/reports/data', ['section' => '__section__']));
                const defaults = @json(['from' => today()->startOfMonth()->toDateString(), 'to' => today()->toDateString()]);
                const state = Object.assign({ outlet_id: '', customer_id: '' }, @json($filter->toQuery()));
                const sections = {};
                const pending = {};

                const number = value => Number(value || 0).toLocaleString('id-ID');
                const rupiah = value => (value < 0 ? '−' : '') + 'Rp ' + number(Math.abs(value));
                // Sumbu chart: Rp 1,2 jt / Rp 350 rb
                const compactRupiah = value => 'Rp ' + new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 1 }).format(value || 0);
                const escape = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));

                function query(extra = {}) {
                    const params = Object.assign({}, state, extra);
                    return new URLSearchParams(Object.fromEntries(Object.entries(params).filter(([, value]) => value !== '' && value != null)));
                }

                function isDefault() {
                    return state.from === defaults.from && state.to === defaults.to && !state.outlet_id && !state.customer_id;
                }

                /** Ambil data satu bagian dengan filter aktif + override (mis. rentang batang yang diklik). */
                function fetchData(name, extra = {}, signal = undefined) {
                    return fetch(`${dataUrl.replace('__section__', name)}?${query(extra)}`, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        signal,
                    }).then(async response => {
                        const body = await response.json().catch(() => ({}));
                        if (!response.ok) throw new Error(body.message || 'Data gagal dimuat.');
                        return body.data;
                    });
                }

                function showError($box, error) {
                    $box.attr('aria-busy', 'false');
                    $box.find('[data-report-error-text]').first().text(error.message || 'Data gagal dimuat.');
                    $box.find('[data-report-error]').first().prop('hidden', false);
                }

                function load(name) {
                    const section = sections[name];
                    const $box = $(`[data-report-section="${name}"]`);
                    pending[name]?.abort();
                    const controller = new AbortController();
                    pending[name] = controller;

                    $box.attr('aria-busy', 'true').find('[data-report-error]').prop('hidden', true);
                    fetchData(name, section.params(), controller.signal)
                        .then(data => {
                            section.render(data, $box);
                            $box.attr('aria-busy', 'false');
                        })
                        .catch(error => { if (error.name !== 'AbortError') showError($box, error); });
                }

                function reloadAll() {
                    window.history.replaceState(null, '', `${window.location.pathname}?${query()}`);
                    $('#report-reset').prop('hidden', isDefault());
                    Object.keys(sections).forEach(load);
                }

                function setFilter(patch) {
                    Object.assign(state, patch);
                    $(document).trigger('b2b-report:filter-changed');
                    reloadAll();
                }

                /**
                 * Keterangan filter untuk judul modal, mis. "1 Nov 2026 – 21 Nov 2026 · Outlet A · PT X · Kopi Susu".
                 * labels = nama pilihan drilldown; outlet/customer dari filter halaman hanya disebut kalau tidak ditimpa drilldown.
                 * period: false untuk angka per hari ini (piutang) → "Per 22 Nov 2026 · ...".
                 */
                function describe(extra = {}, labels = [], { period = true } = {}) {
                    const params = Object.assign({}, state, extra);
                    const parts = [period ? B2b.rangeLabel(moment(params.from), moment(params.to)) : `Per ${B2b.dateLabel(moment())}`];
                    if (state.outlet_id && !extra.outlet_id) parts.push($('#report-outlet option:selected').text());
                    if (state.customer_id && !extra.customer_id) {
                        parts.push($('#report-customer').select2('data')[0]?.name || $('#report-customer option:selected').text());
                    }
                    return parts.concat(labels).join(' · ');
                }

                /** Isi tooltip chart: judul, nominal, jumlah pesanan / invoice (+ qty untuk produk). */
                function tip(title, amount, count, qty = null, unit = 'pesanan') {
                    const detail = `${number(count)} ${unit}` + (qty != null ? ` · ${number(qty)} item` : '');
                    return `<div class="b2b-report-tip"><strong>${escape(title)}</strong>${rupiah(amount)}<br><span>${detail}</span></div>`;
                }

                /**
                 * Chart peringkat (batang horizontal) untuk drilldown. rows: [{ label, amount, count, unit, qty?, muted? }]
                 * dari rankRows; baris muted (mis. "Lainnya") berwarna abu-abu. Mengganti chart lama di elemen yang sama.
                 */
                function rankChart(el, previous, rows, onSelect, { name = 'Penjualan', color = '#1572e8' } = {}) {
                    previous?.destroy();
                    if (typeof ApexCharts === 'undefined') {
                        $(el).html('<div class="b2b-report-empty">Chart belum siap.</div>');
                        return null;
                    }
                    const select = index => rows[index] && !rows[index].muted && onSelect(rows[index], index);
                    const chart = new ApexCharts(el, {
                        chart: {
                            type: 'bar', height: Math.max(220, rows.length * 42 + 50), fontFamily: 'inherit',
                            toolbar: { show: false }, zoom: { enabled: false }, animations: { enabled: true, speed: 300 },
                            events: { dataPointSelection: (event, context, config) => select(config.dataPointIndex) },
                        },
                        series: [{ name, data: rows.map(row => row.amount) }],
                        colors: rows.map(row => row.muted ? '#94a3b8' : color),
                        plotOptions: { bar: { horizontal: true, distributed: true, borderRadius: 4, barHeight: '62%', dataLabels: { position: 'top' } } },
                        dataLabels: { enabled: true, formatter: compactRupiah, offsetX: 34, style: { colors: ['#334155'], fontSize: '11px', fontWeight: 600 } },
                        legend: { show: false },
                        grid: { borderColor: '#eef2f7', strokeDashArray: 4 },
                        states: { hover: { filter: { type: 'darken', value: .9 } }, active: { filter: { type: 'none' } } },
                        xaxis: { categories: rows.map(row => row.label), labels: { formatter: compactRupiah, style: { colors: '#64748b', fontSize: '11px' } } },
                        yaxis: { labels: { maxWidth: 220, style: { colors: '#0f172a', fontSize: '12px' } } },
                        tooltip: { custom: ({ dataPointIndex }) => {
                            const row = rows[dataPointIndex];
                            return tip(row.label, row.amount, row.count, row.qty, row.unit);
                        } },
                    });
                    chart.render();
                    return chart;
                }

                /**
                 * Baris chart peringkat dari respons breakdown / debtors (10 teratas + "Lainnya").
                 * count = nama field jumlah transaksi di respons, unit = sebutannya di tooltip.
                 */
                function rankRows(data, noun, { count = 'orders', unit = 'pesanan' } = {}) {
                    const rows = data.items.map(item => ({ label: item.label, amount: item.amount, count: item[count], unit, qty: item.qty, item }));
                    if (data.others) {
                        rows.push({ label: `Lainnya (${number(data.others.count)} ${noun})`, amount: data.others.amount, count: data.others[count], unit, muted: true });
                    }
                    return rows;
                }

                $(document).on('click', '[data-report-retry]', function () {
                    load($(this).closest('[data-report-section]').data('report-section'));
                });

                return {
                    number, rupiah, compactRupiah, escape, state, setFilter, reloadAll, load, showError, describe, tip, rankChart, rankRows,
                    fetch: fetchData,
                    section(name, render, params = () => ({})) { sections[name] = { render, params }; },
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

                $('[data-open-orders]').on('click', () => B2bReport.ordersModal({ title: 'Daftar pesanan' }));
                $('[data-open-invoices]').on('click', function () {
                    const overdue = this.dataset.openInvoices === 'overdue';
                    B2bReport.invoicesModal(overdue
                        ? { title: 'Invoice lewat jatuh tempo', params: { bucket: 'overdue' } }
                        : { title: 'Invoice belum lunas' });
                });

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
    @endprepend
@endsection

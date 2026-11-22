{{--
    Bagian D Laporan B2B: pembayaran & kas di periode, per metode / outlet penerima / kasir.
    Donut = uang masuk; tabel = masuk − refund = bersih per kelompok (untuk mencocokkan kas outlet / kasir).
    Klik irisan atau nama kelompok → modal mutasi kas kelompok itu. Outlet kosong = pusat (pelunasan di luar kasir).
--}}
<section class="ob-card b2b-report-card" data-report-section="cashflow" aria-busy="true">
    <div class="b2b-card__head">
        <div class="min-w-0">
            <h2 class="b2b-card__title"><i class="fas fa-wallet" aria-hidden="true"></i> Pembayaran &amp; kas</h2>
            <p class="b2b-card__desc" data-cash-desc>&nbsp;</p>
        </div>
        <div class="b2b-actions">
            <div class="b2b-report-toggle" role="tablist" aria-label="Kelompokkan kas per" data-cash-tabs>
                <button type="button" role="tab" data-tab="method" aria-selected="true">Metode</button>
                <button type="button" role="tab" data-tab="outlet" aria-selected="false">Outlet penerima</button>
                <button type="button" role="tab" data-tab="cashier" aria-selected="false">Kasir</button>
            </div>
            <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" data-cash-mutations>
                <i class="fas fa-list" aria-hidden="true"></i> Lihat mutasi
            </button>
        </div>
    </div>
    <div class="b2b-card__body">
        <div class="b2b-report-cash" data-cash-body>
            <div class="b2b-report-chart b2b-report-cash__chart" id="report-cash-chart"></div>
            <div class="b2b-report-table-wrap">
                <table class="b2b-report-table">
                    <thead>
                        <tr>
                            <th scope="col" data-cash-head>Metode</th>
                            <th scope="col" class="text-end">Masuk</th>
                            <th scope="col" class="text-end">Refund</th>
                            <th scope="col" class="text-end">Bersih</th>
                        </tr>
                    </thead>
                    <tbody data-cash-rows></tbody>
                    <tfoot data-cash-foot></tfoot>
                </table>
            </div>
        </div>
        <div class="b2b-report-empty" data-cash-empty hidden>Belum ada pembayaran atau refund di periode ini.</div>
        <div class="b2b-report-error" data-report-error hidden>
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <span data-report-error-text>Pembayaran &amp; kas gagal dimuat.</span>
            <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" data-report-retry>Coba lagi</button>
        </div>
    </div>
</section>

@push('css')
    <style>
        .b2b-report-cash { display: grid; grid-template-columns: minmax(220px, .8fr) minmax(0, 1.4fr); gap: 1rem 1.5rem; align-items: center; }
        .b2b-report-cash[hidden] { display: none; }
        @media (max-width: 991.98px) { .b2b-report-cash { grid-template-columns: minmax(0, 1fr); } }
        .b2b-report-cash__out { color: var(--ob-danger); }
        .b2b-report-cash__net { font-weight: 700; color: var(--ob-text); }
        .b2b-report-cash__name { display: grid; grid-template-columns: auto minmax(0, 1fr); gap: 0 .55rem; align-items: center; width: 100%; padding: 0; border: 0; background: none; text-align: left; color: var(--ob-text); }
        .b2b-report-cash__name:not(:disabled):hover strong { color: var(--ob-primary); text-decoration: underline; }
        .b2b-report-cash__name:focus-visible { outline: 2px solid var(--ob-primary); outline-offset: 2px; border-radius: 4px; }
        .b2b-report-cash__name:disabled { cursor: default; }
        .b2b-report-cash__name strong { font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .b2b-report-cash__name small { grid-column: 2; font-size: .74rem; color: var(--ob-muted); }
        .b2b-report-cash__dot { grid-row: span 2; width: .65rem; height: .65rem; border-radius: 50%; }
    </style>
@endpush

@push('js')
    <script>
        (function () {
            const $box = $('[data-report-section="cashflow"]');
            const chartEl = document.getElementById('report-cash-chart');
            const COLORS = ['#1572e8', '#31ce36', '#ffad46', '#6861ce', '#48abf7', '#f25961', '#2bb5a8'];
            const MUTED = '#94a3b8';
            const HEAD = { method: 'Metode', outlet: 'Outlet penerima', cashier: 'Kasir' };
            const NOUN = { method: 'metode', outlet: 'outlet', cashier: 'kasir' };

            let tab = 'method';
            let chart = null;
            let rows = [];

            function openMutations(row = null) {
                B2bReport.mutationsModal(row
                    ? { title: `Mutasi kas ${row.label}`, params: { by: tab, key: row.key }, labels: [row.label] }
                    : { title: 'Mutasi kas' });
            }

            function render(data) {
                const { number, rupiah, escape } = B2bReport;
                const totals = data.totals;
                $box.find('[data-cash-tabs] button').each(function () {
                    this.setAttribute('aria-selected', String(this.dataset.tab === tab));
                });
                $box.find('[data-cash-head]').text(HEAD[tab]);

                rows = data.items.map((item, index) => Object.assign({}, item, { color: COLORS[index % COLORS.length] }));
                if (data.others) {
                    rows.push(Object.assign({ label: `Lainnya (${number(data.others.count)} ${NOUN[tab]})`, muted: true, color: MUTED }, data.others));
                }
                const empty = !rows.length;
                $box.find('[data-cash-empty]').prop('hidden', !empty);
                $box.find('[data-cash-body]').prop('hidden', empty);
                $box.find('[data-cash-desc]').text(empty
                    ? 'Belum ada pembayaran atau refund di periode ini.'
                    : `Masuk ${rupiah(totals.received)} dari ${number(totals.received_count)} pembayaran`
                        + (totals.refund_count ? ` · refund ${rupiah(totals.refunded)}` : '')
                        + ` · bersih ${rupiah(totals.net)}. Klik ${NOUN[tab]} untuk melihat mutasinya.`);
                if (empty) { chart?.destroy(); chart = null; return; }

                const count = (value, noun) => `${number(value)} ${noun}`;
                $box.find('[data-cash-rows]').html(rows.map((row, index) => `<tr>
                    <td>
                        <button type="button" class="b2b-report-cash__name" data-cash-row="${index}" ${row.muted ? 'disabled' : ''}>
                            <span class="b2b-report-cash__dot" style="background:${row.color}" aria-hidden="true"></span>
                            <strong>${escape(row.label)}</strong>
                            <small>${escape(row.sub ? row.sub + ' · ' : '')}${count(row.received_count, 'pembayaran')}${row.refund_count ? ' · ' + count(row.refund_count, 'refund') : ''}</small>
                        </button>
                    </td>
                    <td class="text-end">${rupiah(row.received)}</td>
                    <td class="text-end ${row.refunded ? 'b2b-report-cash__out' : ''}">${row.refunded ? '−' + rupiah(row.refunded) : '-'}</td>
                    <td class="text-end b2b-report-cash__net">${rupiah(row.net)}</td>
                </tr>`).join(''));
                $box.find('[data-cash-foot]').html(rows.length > 1 ? `<tr>
                    <td>Total</td>
                    <td class="text-end">${rupiah(totals.received)}</td>
                    <td class="text-end ${totals.refunded ? 'b2b-report-cash__out' : ''}">${totals.refunded ? '−' + rupiah(totals.refunded) : '-'}</td>
                    <td class="text-end">${rupiah(totals.net)}</td>
                </tr>` : '');

                chart?.destroy();
                chart = null;
                // Donut hanya untuk uang masuk; kalau periode ini hanya ada refund, cukup tabelnya.
                $(chartEl).prop('hidden', !totals.received);
                if (!totals.received) return;
                if (typeof ApexCharts === 'undefined') { $(chartEl).html('<div class="b2b-report-empty">Chart belum siap.</div>'); return; }
                chart = new ApexCharts(chartEl, {
                    chart: {
                        type: 'donut', height: 260, fontFamily: 'inherit', animations: { enabled: true, speed: 300 },
                        events: { dataPointSelection: (event, context, config) => {
                            const row = rows[config.dataPointIndex];
                            if (row && !row.muted) openMutations(row);
                        } },
                    },
                    series: rows.map(row => row.received),
                    labels: rows.map(row => row.label),
                    colors: rows.map(row => row.color),
                    legend: { show: false },
                    dataLabels: { enabled: false },
                    stroke: { width: 2 },
                    states: { hover: { filter: { type: 'darken', value: .9 } }, active: { filter: { type: 'none' } } },
                    plotOptions: { pie: { donut: { size: '68%', labels: {
                        show: true,
                        value: { fontSize: '15px', fontWeight: 700, formatter: value => B2bReport.compactRupiah(Number(value)) },
                        total: { show: true, label: 'Uang masuk', fontSize: '12px', color: '#64748b', formatter: () => B2bReport.compactRupiah(totals.received) },
                    } } } },
                    // Pie/donut: ApexCharts mewarnai latar tooltip sesuai irisan; matikan supaya isi tooltip tetap terbaca.
                    tooltip: { fillSeriesColor: false, theme: 'light', custom: ({ seriesIndex }) => {
                        const row = rows[seriesIndex];
                        return `<div class="b2b-report-tip"><strong>${escape(row.label)}</strong>Masuk ${rupiah(row.received)}<br>`
                            + `<span>${count(row.received_count, 'pembayaran')}${row.refunded ? ` · refund ${rupiah(row.refunded)}` : ''}</span></div>`;
                    } },
                });
                chart.render();
            }

            $box.on('click', '[data-cash-row]', function () { openMutations(rows[Number(this.dataset.cashRow)]); });
            $box.on('click', '[data-cash-mutations]', () => openMutations());
            $box.on('click', '[data-cash-tabs] button', function () {
                if (this.dataset.tab === tab) return;
                tab = this.dataset.tab;
                B2bReport.load('cashflow');
            });

            B2bReport.section('cashflow', render, () => ({ by: tab }));
        })();
    </script>
@endpush

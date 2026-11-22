{{--
    Bagian C Laporan B2B: umur piutang per hari ini (tidak ikut periode, tetap ikut outlet / customer).
    Drilldown: kelompok umur → customer dengan sisa tagihan terbesar → modal invoice belum lunas.
    Daftar kelompok di samping chart = keterangan warna sekaligus tombol drilldown yang bisa dipakai dengan keyboard.
--}}
<section class="ob-card b2b-report-card" data-report-section="aging" aria-busy="true">
    <div class="b2b-card__head">
        <div class="min-w-0">
            <h2 class="b2b-card__title"><i class="fas fa-hourglass-half" aria-hidden="true"></i> Umur piutang <span class="b2b-report-asof">per hari ini</span></h2>
            <nav class="b2b-report-crumbs" data-aging-crumbs aria-label="Posisi drilldown" hidden></nav>
            <p class="b2b-card__desc" data-aging-desc>&nbsp;</p>
        </div>
        <div class="b2b-actions">
            <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" data-aging-back hidden>
                <i class="fas fa-arrow-left" aria-hidden="true"></i> Kembali
            </button>
            <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" data-aging-invoices>
                <i class="fas fa-list" aria-hidden="true"></i> Lihat invoice
            </button>
        </div>
    </div>
    <div class="b2b-card__body">
        <div class="b2b-report-aging" data-aging-body>
            <div class="b2b-report-chart" id="report-aging-chart"></div>
            <ul class="b2b-report-aging__legend" data-aging-legend></ul>
        </div>
        <div class="b2b-report-empty" data-aging-empty hidden>Tidak ada piutang yang belum lunas.</div>
        <div class="b2b-report-error" data-report-error hidden>
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <span data-report-error-text>Umur piutang gagal dimuat.</span>
            <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" data-report-retry>Coba lagi</button>
        </div>
    </div>
</section>

@push('css')
    <style>
        .b2b-report-aging { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(240px, 1fr); gap: 1rem 1.5rem; align-items: center; }
        .b2b-report-aging[hidden] { display: none; }
        .b2b-report-aging.is-drilled { grid-template-columns: minmax(0, 1fr); }
        .b2b-report-aging.is-drilled .b2b-report-aging__legend { display: none; }
        @media (max-width: 991.98px) { .b2b-report-aging { grid-template-columns: minmax(0, 1fr); } }
        .b2b-report-aging__legend { display: grid; gap: .35rem; margin: 0; padding: 0; list-style: none; }
        .b2b-report-aging__legend button { display: grid; grid-template-columns: auto minmax(0, 1fr) auto; gap: .15rem .6rem; align-items: center; width: 100%;
            padding: .55rem .7rem; border: 1px solid var(--ob-border); border-radius: var(--ob-radius-sm); background: var(--ob-surface); text-align: left; transition: border-color .15s ease; }
        .b2b-report-aging__legend button:hover:not(:disabled) { border-color: var(--ob-primary); }
        .b2b-report-aging__legend button:focus-visible { outline: 2px solid var(--ob-primary); outline-offset: 2px; }
        .b2b-report-aging__legend button:disabled { opacity: .55; cursor: default; }
        .b2b-report-aging__dot { grid-row: span 2; width: .7rem; height: .7rem; border-radius: 50%; }
        .b2b-report-aging__name { font-size: .82rem; font-weight: 600; color: var(--ob-text); }
        .b2b-report-aging__amount { font-size: .85rem; font-weight: 700; color: var(--ob-text); font-variant-numeric: tabular-nums; text-align: right; }
        .b2b-report-aging__meta { font-size: .74rem; color: var(--ob-muted); }
        .b2b-report-aging__share { font-size: .74rem; color: var(--ob-muted); text-align: right; font-variant-numeric: tabular-nums; }
    </style>
@endpush

@push('js')
    <script>
        (function () {
            const $box = $('[data-report-section="aging"]');
            const chartEl = document.getElementById('report-aging-chart');
            // Hijau (belum jatuh tempo) → merah tua (> 90 hari).
            const COLORS = { current: '#31ce36', '1-30': '#ffad46', '31-60': '#fb8c00', '61-90': '#f25961', '90+': '#b71c1c' };

            let root = null;     // respons aging (tingkat awal)
            let bucket = null;   // kelompok umur yang sedang dibuka: { key, label }
            let chart = null;
            let request = null;

            const params = () => (bucket ? { bucket: bucket.key } : {});
            const pct = (part, total) => (total ? Math.round(part / total * 1000) / 10 : 0).toLocaleString('id-ID') + '%';

            function setDrilled(drilled) {
                $box.find('[data-aging-back]').prop('hidden', !drilled);
                $box.find('[data-aging-body]').toggleClass('is-drilled', drilled);
                const $crumbs = $box.find('[data-aging-crumbs]').empty().prop('hidden', !drilled);
                if (drilled) {
                    $crumbs.append($('<button type="button" data-aging-root>').text('Semua umur'))
                        .append('<i class="fas fa-chevron-right" aria-hidden="true"></i>')
                        .append($('<strong>').text(bucket.label));
                }
            }

            function showEmpty(empty) {
                $box.find('[data-aging-empty]').prop('hidden', !empty);
                $box.find('[data-aging-body]').prop('hidden', empty);
                if (empty) { chart?.destroy(); chart = null; }
            }

            function renderRoot(data) {
                bucket = null;
                setDrilled(false);
                const { number, rupiah } = B2bReport;
                const total = data.total;
                $box.find('[data-aging-desc]').text(total.invoices
                    ? `Sisa tagihan ${rupiah(total.amount)} dari ${number(total.invoices)} invoice · ${number(total.customers)} customer. Tidak mengikuti periode. Klik kelompok umur untuk melihat customer.`
                    : 'Tidak mengikuti periode; hanya outlet dan customer.');
                showEmpty(!total.invoices);
                if (!total.invoices) return;

                $box.find('[data-aging-legend]').html(data.buckets.map(item => `<li>
                    <button type="button" data-aging-bucket="${item.key}" ${item.invoices ? '' : 'disabled'}>
                        <span class="b2b-report-aging__dot" style="background:${COLORS[item.key]}" aria-hidden="true"></span>
                        <span class="b2b-report-aging__name">${B2bReport.escape(item.label)}</span>
                        <span class="b2b-report-aging__amount">${rupiah(item.amount)}</span>
                        <span class="b2b-report-aging__meta">${number(item.invoices)} invoice · ${number(item.customers)} customer</span>
                        <span class="b2b-report-aging__share">${pct(item.amount, total.amount)}</span>
                    </button>
                </li>`).join(''));

                chart?.destroy();
                if (typeof ApexCharts === 'undefined') { chart = null; $(chartEl).html('<div class="b2b-report-empty">Chart belum siap.</div>'); return; }
                chart = new ApexCharts(chartEl, {
                    chart: {
                        type: 'bar', height: 260, fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false },
                        animations: { enabled: true, speed: 300 },
                        events: { dataPointSelection: (event, context, config) => select(data.buckets[config.dataPointIndex]) },
                    },
                    series: [{ name: 'Sisa tagihan', data: data.buckets.map(item => item.amount) }],
                    colors: data.buckets.map(item => COLORS[item.key]),
                    plotOptions: { bar: { distributed: true, borderRadius: 4, columnWidth: '55%', dataLabels: { position: 'top' } } },
                    dataLabels: { enabled: true, formatter: value => value ? B2bReport.compactRupiah(value) : '', offsetY: -18, style: { colors: ['#334155'], fontSize: '11px', fontWeight: 600 } },
                    legend: { show: false },
                    grid: { borderColor: '#eef2f7', strokeDashArray: 4 },
                    states: { hover: { filter: { type: 'darken', value: .9 } }, active: { filter: { type: 'none' } } },
                    xaxis: { categories: data.buckets.map(item => item.label), labels: { style: { colors: '#64748b', fontSize: '11px' } } },
                    yaxis: { labels: { formatter: B2bReport.compactRupiah, style: { colors: '#64748b', fontSize: '11px' } } },
                    tooltip: { custom: ({ dataPointIndex }) => {
                        const item = data.buckets[dataPointIndex];
                        return `<div class="b2b-report-tip"><strong>${B2bReport.escape(item.label)}</strong>${rupiah(item.amount)}<br>`
                            + `<span>${number(item.invoices)} invoice · ${number(item.customers)} customer</span></div>`;
                    } },
                });
                chart.render();
            }

            function renderDebtors(data) {
                setDrilled(true);
                $box.find('[data-aging-desc]').text(`Sisa tagihan ${B2bReport.rupiah(data.total)}. Klik customer untuk melihat invoicenya.`);
                const rows = B2bReport.rankRows(data, 'customer', { count: 'invoices', unit: 'invoice' });
                showEmpty(!rows.length);
                if (!rows.length) return;
                chart = B2bReport.rankChart(chartEl, chart, rows, row => openInvoices(row.item), { name: 'Sisa tagihan', color: COLORS[bucket.key] });
            }

            function select(item) {
                if (!item || !item.invoices) return;
                bucket = { key: item.key, label: item.label };
                request?.abort();
                request = new AbortController();
                $box.attr('aria-busy', 'true').find('[data-report-error]').prop('hidden', true);
                B2bReport.fetch('debtors', params(), request.signal)
                    .then(data => { $box.attr('aria-busy', 'false'); renderDebtors(data); })
                    .catch(error => { if (error.name !== 'AbortError') B2bReport.showError($box, error); });
            }

            function back() {
                request?.abort();
                $box.attr('aria-busy', 'false').find('[data-report-error]').prop('hidden', true);
                if (root) renderRoot(root); else B2bReport.load('aging');
            }

            function openInvoices(customer = null) {
                const labels = (customer ? [customer.label] : []).concat(bucket ? [bucket.label] : []);
                B2bReport.invoicesModal({
                    title: customer ? `Invoice ${customer.label}` : (bucket ? `Invoice ${bucket.label}` : 'Invoice belum lunas'),
                    params: Object.assign(params(), customer ? { customer_id: customer.key } : {}),
                    labels,
                });
            }

            $box.on('click', '[data-aging-bucket]', function () {
                select(root?.buckets.find(item => item.key === this.dataset.agingBucket));
            });
            $box.on('click', '[data-aging-back], [data-aging-root]', back);
            $box.on('click', '[data-aging-invoices]', () => openInvoices());

            B2bReport.section('aging', data => {
                request?.abort();
                root = data;
                renderRoot(data);
            });
        })();
    </script>
@endpush

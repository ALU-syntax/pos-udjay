{{--
    Bagian A Laporan B2B: tren penjualan per hari / minggu / bulan.
    Drilldown: klik batang → penjualan per customer pada rentang itu → klik customer → modal daftar pesanan.
--}}
<section class="ob-card b2b-report-card" data-report-section="trend" aria-busy="true">
    <div class="b2b-card__head">
        <div class="min-w-0">
            <h2 class="b2b-card__title"><i class="fas fa-chart-bar" aria-hidden="true"></i> Tren penjualan</h2>
            <nav class="b2b-report-crumbs" data-trend-crumbs aria-label="Posisi drilldown" hidden>
                <button type="button" data-trend-back>Semua periode</button>
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
                <strong data-trend-crumb></strong>
            </nav>
            <p class="b2b-card__desc" data-trend-desc>&nbsp;</p>
        </div>
        <div class="b2b-actions">
            <div class="b2b-report-toggle" role="group" aria-label="Kelompokkan per" data-trend-groups>
                <button type="button" data-group="day" aria-pressed="false">Hari</button>
                <button type="button" data-group="week" aria-pressed="false">Minggu</button>
                <button type="button" data-group="month" aria-pressed="false">Bulan</button>
            </div>
            <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" data-trend-back hidden>
                <i class="fas fa-arrow-left" aria-hidden="true"></i> Kembali
            </button>
            <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" data-trend-orders hidden>
                <i class="fas fa-list" aria-hidden="true"></i> Lihat pesanan
            </button>
        </div>
    </div>
    <div class="b2b-card__body">
        <div class="b2b-report-chart" id="report-trend-chart"></div>
        <div class="b2b-report-empty" data-trend-empty hidden>Belum ada penjualan di periode ini.</div>
        <div class="b2b-report-error" data-report-error hidden>
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <span data-report-error-text>Tren penjualan gagal dimuat.</span>
            <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" data-report-retry>Coba lagi</button>
        </div>
    </div>
</section>

@push('js')
    <script>
        (function () {
            const $box = $('[data-report-section="trend"]');
            const chartEl = document.getElementById('report-trend-chart');
            const primary = '#1572e8';
            let chart = null;
            let trend = null;       // data tren terakhir (untuk kembali dari drilldown tanpa memuat ulang)
            let bucket = null;      // batang yang sedang di-drilldown
            let group = null;       // pilihan Hari/Minggu/Bulan; null = otomatis dari panjang periode
            let request = null;

            function baseOptions(onSelect) {
                return {
                    chart: {
                        type: 'bar', height: 320, fontFamily: 'inherit', toolbar: { show: false }, zoom: { enabled: false },
                        animations: { enabled: true, speed: 300 },
                        events: {
                            dataPointSelection: (event, context, config) => onSelect(config.dataPointIndex),
                            // Klik di label sumbu juga ikut membuka drilldown (batang kecil sulit diketuk di tablet).
                            xAxisLabelClick: (event, context, config) => config.labelIndex >= 0 && onSelect(config.labelIndex),
                        },
                    },
                    colors: [primary],
                    dataLabels: { enabled: false },
                    legend: { show: false },
                    grid: { borderColor: '#eef2f7', strokeDashArray: 4 },
                    states: { hover: { filter: { type: 'darken', value: .9 } }, active: { filter: { type: 'none' } } },
                };
            }

            function draw(options) {
                chart?.destroy();
                chart = null;
                if (typeof ApexCharts === 'undefined') {
                    $(chartEl).html('<div class="b2b-report-empty">Chart belum siap.</div>');
                    return;
                }
                chart = new ApexCharts(chartEl, options);
                chart.render();
            }

            function setDrillUi(drilled) {
                $box.find('[data-trend-crumbs], [data-trend-back], [data-trend-orders]').prop('hidden', !drilled);
                $box.find('[data-trend-groups]').prop('hidden', drilled);
            }

            function showEmpty(empty, text) {
                $box.find('[data-trend-empty]').text(text).prop('hidden', !empty);
                $(chartEl).prop('hidden', empty);
                if (empty) { chart?.destroy(); chart = null; }
            }

            // Tingkat 1: tren penjualan
            function renderTrend(data) {
                trend = data;
                bucket = null;
                setDrillUi(false);
                $box.find('[data-trend-groups] button').each(function () {
                    this.disabled = !data.groups.includes(this.dataset.group);
                    this.setAttribute('aria-pressed', String(this.dataset.group === data.group));
                    this.title = this.disabled ? 'Periode terlalu panjang untuk dikelompokkan per ' + this.textContent.toLowerCase() : '';
                });
                $box.find('[data-trend-desc]').text(data.total
                    ? `Total ${B2bReport.rupiah(data.total)}. Klik batang untuk melihat penjualan per customer.`
                    : 'Belum ada penjualan di periode ini.');

                showEmpty(!data.total, 'Belum ada penjualan di periode ini.');
                if (!data.total) return;

                const many = data.buckets.length > 16;
                draw(Object.assign(baseOptions(index => drillCustomers(data.buckets[index])), {
                    series: [{ name: 'Penjualan', data: data.buckets.map(item => item.amount) }],
                    plotOptions: { bar: { borderRadius: 4, columnWidth: many ? '75%' : '55%' } },
                    xaxis: {
                        categories: data.buckets.map(item => item.label),
                        axisBorder: { show: false }, axisTicks: { show: false },
                        labels: { rotate: -45, rotateAlways: many, hideOverlappingLabels: true, style: { colors: '#64748b', fontSize: '11px' } },
                        tooltip: { enabled: false },
                    },
                    yaxis: { labels: { formatter: B2bReport.compactRupiah, style: { colors: '#64748b', fontSize: '11px' } } },
                    tooltip: { custom: ({ dataPointIndex }) => {
                        const item = data.buckets[dataPointIndex];
                        return B2bReport.tip(item.title, item.amount, item.orders);
                    } },
                }));
            }

            // Tingkat 2: penjualan per customer pada batang yang diklik
            function drillCustomers(item) {
                if (!item || !item.orders) return;
                bucket = item;
                setDrillUi(true);
                $box.find('[data-trend-crumb]').text(item.title);
                $box.find('[data-trend-desc]').text(`${B2bReport.rupiah(item.amount)} dari ${B2bReport.number(item.orders)} pesanan. Klik customer untuk melihat pesanannya.`);
                $box.attr('aria-busy', 'true').find('[data-report-error]').prop('hidden', true);

                request?.abort();
                request = new AbortController();
                B2bReport.fetch('breakdown', { by: 'customer', from: item.from, to: item.to }, request.signal)
                    .then(data => {
                        $box.attr('aria-busy', 'false');
                        renderCustomers(data);
                    })
                    .catch(error => { if (error.name !== 'AbortError') B2bReport.showError($box, error); });
            }

            function renderCustomers(data) {
                const rows = B2bReport.rankRows(data, 'customer');
                showEmpty(!rows.length, 'Tidak ada penjualan pada rentang ini.');
                if (!rows.length) return;
                chart = B2bReport.rankChart(chartEl, chart, rows, row => openOrders(row.item));
            }

            // Tingkat 3: modal daftar pesanan (satu customer, atau semua pesanan pada batang)
            function openOrders(customer) {
                B2bReport.ordersModal({
                    title: customer ? `Pesanan ${customer.label}` : 'Daftar pesanan',
                    params: Object.assign({ from: bucket.from, to: bucket.to }, customer ? { customer_id: customer.key } : {}),
                    labels: customer ? [customer.label] : [],
                });
            }

            $box.on('click', '[data-trend-back]', () => {
                request?.abort();
                $box.attr('aria-busy', 'false').find('[data-report-error]').prop('hidden', true);
                renderTrend(trend);
            });
            $box.on('click', '[data-trend-orders]', () => openOrders(undefined));
            $box.on('click', '[data-trend-groups] button', function () {
                if (this.disabled || this.getAttribute('aria-pressed') === 'true') return;
                group = this.dataset.group;
                B2bReport.load('trend');
            });

            // Ganti filter: kembali ke pengelompokan otomatis sesuai panjang periode baru.
            $(document).on('b2b-report:filter-changed', () => { group = null; });

            B2bReport.section('trend', (data) => {
                request?.abort();
                renderTrend(data);
            }, () => ({ group }));
        })();
    </script>
@endpush

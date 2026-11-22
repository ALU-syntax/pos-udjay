{{--
    Bagian B Laporan B2B: penjualan per Customer / Outlet / Produk / Kategori (10 teratas + Lainnya).
    Drilldown per tab (tingkat terakhir membuka modal daftar pesanan):
      Customer → produk yang dibeli · Outlet → customer · Produk → varian → customer · Kategori → produk.
--}}
<section class="ob-card b2b-report-card" data-report-section="breakdown" aria-busy="true">
    <div class="b2b-card__head">
        <div class="min-w-0">
            <h2 class="b2b-card__title"><i class="fas fa-layer-group" aria-hidden="true"></i> Penjualan per</h2>
            <nav class="b2b-report-crumbs" data-breakdown-crumbs aria-label="Posisi drilldown" hidden></nav>
            <p class="b2b-card__desc" data-breakdown-desc>&nbsp;</p>
        </div>
        <div class="b2b-actions">
            <div class="b2b-report-toggle" role="tablist" aria-label="Kelompokkan penjualan per" data-breakdown-tabs>
                <button type="button" role="tab" data-tab="customer" aria-selected="true">Customer</button>
                <button type="button" role="tab" data-tab="outlet" aria-selected="false">Outlet</button>
                <button type="button" role="tab" data-tab="product" aria-selected="false">Produk</button>
                <button type="button" role="tab" data-tab="category" aria-selected="false">Kategori</button>
            </div>
            <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" data-breakdown-back hidden>
                <i class="fas fa-arrow-left" aria-hidden="true"></i> Kembali
            </button>
            <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" data-breakdown-orders hidden>
                <i class="fas fa-list" aria-hidden="true"></i> Lihat pesanan
            </button>
        </div>
    </div>
    <div class="b2b-card__body">
        <div class="b2b-report-chart" id="report-breakdown-chart"></div>
        <div class="b2b-report-empty" data-breakdown-empty hidden>Belum ada penjualan di periode ini.</div>
        <div class="b2b-report-error" data-report-error hidden>
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <span data-report-error-text>Penjualan per kelompok gagal dimuat.</span>
            <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" data-report-retry>Coba lagi</button>
        </div>
    </div>
</section>

@push('js')
    <script>
        (function () {
            const $box = $('[data-report-section="breakdown"]');
            const chartEl = document.getElementById('report-breakdown-chart');

            // Urutan pengelompokan per tab; tingkat terakhir diklik → modal daftar pesanan.
            const TABS = {
                customer: ['customer', 'product'],
                outlet: ['outlet', 'customer'],
                product: ['product', 'variant', 'customer'],
                category: ['category', 'product'],
            };
            const NOUN = { customer: 'customer', outlet: 'outlet', product: 'produk', variant: 'varian', category: 'kategori' };
            const ROOT = { customer: 'Semua customer', outlet: 'Semua outlet', product: 'Semua produk', category: 'Semua kategori' };
            // Parameter filter yang ditambahkan saat satu baris diklik.
            const PARAM = {
                customer: item => ({ customer_id: item.key }),
                outlet: item => ({ outlet_id: item.key }),
                product: item => ({ product: item.key }),
                variant: item => ({ variant: item.key }),
                category: item => ({ category_id: item.key }),
            };

            let tab = 'customer';
            let path = [];      // pilihan drilldown: [{ by, item }]
            let cache = [];     // data per tingkat, untuk kembali tanpa memuat ulang
            let chart = null;
            let request = null;

            const steps = () => TABS[tab];
            const level = () => path.length;
            const params = () => Object.assign({}, ...path.map(step => PARAM[step.by](step.item)));
            const labels = () => path.map(step => step.item.label);

            function renderCrumbs() {
                const $crumbs = $box.find('[data-breakdown-crumbs]').empty().prop('hidden', !path.length);
                if (!path.length) return;
                const crumbs = [ROOT[tab]].concat(labels());
                crumbs.forEach((text, index) => {
                    if (index) $crumbs.append('<i class="fas fa-chevron-right" aria-hidden="true"></i>');
                    $crumbs.append(index === crumbs.length - 1
                        ? $('<strong>').text(text)
                        : $('<button type="button">').text(text).attr('data-breakdown-to', index));
                });
            }

            function render(data) {
                const by = steps()[level()];
                const last = level() === steps().length - 1;
                const drilled = path.length > 0;
                $box.find('[data-breakdown-back], [data-breakdown-orders]').prop('hidden', !drilled);
                $box.find('[data-breakdown-tabs]').prop('hidden', drilled);
                $box.find('[data-breakdown-tabs] button').each(function () {
                    this.setAttribute('aria-selected', String(this.dataset.tab === tab));
                });
                renderCrumbs();

                const nextBy = steps()[level() + 1];
                const next = last ? 'pesanannya' : (nextBy === 'product' ? 'produk yang dibeli' : NOUN[nextBy]);
                $box.find('[data-breakdown-desc]').text(data.total
                    ? `Total ${B2bReport.rupiah(data.total)}. Klik ${NOUN[by]} untuk melihat ${next}.`
                    : 'Belum ada penjualan di periode ini.');

                const rows = B2bReport.rankRows(data, NOUN[by]);
                $box.find('[data-breakdown-empty]').prop('hidden', !!rows.length);
                $(chartEl).prop('hidden', !rows.length);
                if (!rows.length) { chart?.destroy(); chart = null; return; }
                chart = B2bReport.rankChart(chartEl, chart, rows, row => select(by, row.item));
            }

            function select(by, item) {
                path.push({ by, item });
                if (level() === steps().length) {
                    openOrders();
                    path.pop();
                    return;
                }
                fetchLevel();
            }

            function fetchLevel() {
                request?.abort();
                request = new AbortController();
                $box.attr('aria-busy', 'true').find('[data-report-error]').prop('hidden', true);
                const depth = level();
                B2bReport.fetch('breakdown', Object.assign({ by: steps()[depth] }, params()), request.signal)
                    .then(data => {
                        $box.attr('aria-busy', 'false');
                        cache[depth] = data;
                        render(data);
                    })
                    .catch(error => { if (error.name !== 'AbortError') B2bReport.showError($box, error); });
            }

            function goTo(depth) {
                request?.abort();
                path = path.slice(0, depth);
                $box.attr('aria-busy', 'false').find('[data-report-error]').prop('hidden', true);
                if (cache[depth]) render(cache[depth]); else fetchLevel();
            }

            function openOrders() {
                B2bReport.ordersModal({
                    title: path.length ? `Pesanan ${labels().join(' · ')}` : 'Daftar pesanan',
                    params: params(),
                    labels: labels(),
                });
            }

            $box.on('click', '[data-breakdown-back]', () => goTo(level() - 1));
            $box.on('click', '[data-breakdown-to]', function () { goTo(Number(this.dataset.breakdownTo)); });
            $box.on('click', '[data-breakdown-orders]', openOrders);
            $box.on('click', '[data-breakdown-tabs] button', function () {
                if (this.dataset.tab === tab) return;
                tab = this.dataset.tab;
                B2bReport.load('breakdown');
            });

            B2bReport.section('breakdown', data => {
                request?.abort();
                path = [];
                cache = [data];
                render(data);
            }, () => ({ by: steps()[0] }));
        })();
    </script>
@endpush

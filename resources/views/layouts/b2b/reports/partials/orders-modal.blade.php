{{--
    Modal daftar pesanan Laporan B2B (tingkat terakhir drilldown dan kartu KPI penjualan).
    JS: B2bReport.ordersModal({ title, params: { from, to, customer_id, customer_name } }); params menimpa filter aktif.
    Baris pesanan menjadi link ke detail pesanan kalau user punya akses menu Pesanan.
--}}
<div class="modal fade b2b-modal" id="report-orders-modal" tabindex="-1" aria-labelledby="report-orders-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content ob-page">
            <div class="modal-header">
                <div class="min-w-0">
                    <h2 class="b2b-modal__title" id="report-orders-title">Daftar pesanan</h2>
                    <p class="b2b-modal__sub" data-orders-sub></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="b2b-report-orders__total" data-orders-total></div>
                <div class="ob-table-wrap b2b-report-orders__wrap">
                    <table class="table w-100 b2b-report-orders">
                        <thead>
                            <tr>
                                <th scope="col">Pesanan</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody data-orders-rows></tbody>
                    </table>
                </div>
                <div class="b2b-report-empty" data-orders-empty hidden>Tidak ada pesanan.</div>
                <div class="b2b-report-error" data-report-error hidden>
                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                    <span data-report-error-text>Daftar pesanan gagal dimuat.</span>
                    <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" data-orders-retry>Coba lagi</button>
                </div>
            </div>
            <div class="modal-footer b2b-report-orders__foot">
                <span class="b2b-report-orders__page" data-orders-page></span>
                <div class="b2b-actions">
                    <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" data-orders-nav="-1" aria-label="Halaman sebelumnya"><i class="fas fa-chevron-left" aria-hidden="true"></i> Sebelumnya</button>
                    <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" data-orders-nav="1" aria-label="Halaman berikutnya">Berikutnya <i class="fas fa-chevron-right" aria-hidden="true"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('css')
    <style>
        .b2b-report-orders__total { display: flex; flex-wrap: wrap; gap: .25rem 1rem; padding: .7rem .9rem; border: 1px solid var(--ob-border); border-radius: 12px; background: var(--ob-surface-2); font-size: .85rem; color: var(--ob-muted); }
        .b2b-report-orders__total strong { color: var(--ob-text); font-variant-numeric: tabular-nums; }
        .b2b-report-orders__wrap { margin: 0; overflow-x: auto; }
        /* Tablet: modal-lg baru berlaku di ≥ 992px, jadi dilebarkan sendiri supaya kolom Total tidak terpotong. */
        @media (min-width: 576px) { #report-orders-modal .modal-dialog { max-width: min(800px, calc(100% - 2rem)); } }
        #report-orders-modal .modal-content[aria-busy="true"] .b2b-report-orders__wrap { opacity: .45; }
        /* .ob-page di depan supaya menang dari gaya tabel Kaiadmin */
        .ob-page .b2b-report-orders { margin: 0; font-size: .84rem; }
        .ob-page .b2b-report-orders th { padding: .5rem .6rem !important; font-size: .68rem !important; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--ob-muted); white-space: nowrap; }
        .ob-page .b2b-report-orders td { padding: .6rem !important; vertical-align: middle; }
        .b2b-report-orders .b2b-report-orders__num { font-weight: 700; color: var(--ob-text); white-space: nowrap; }
        .b2b-report-orders a.b2b-report-orders__num { color: var(--ob-primary); }
        .b2b-report-orders .ob-cell-meta { display: block; font-size: .74rem; color: var(--ob-muted); white-space: nowrap; }
        .b2b-report-orders .text-end { font-variant-numeric: tabular-nums; white-space: nowrap; font-weight: 700; }
        .b2b-report-orders__foot { justify-content: space-between; }
        .b2b-report-orders__foot .ob-btn { min-height: 38px !important; }
        .b2b-report-orders__page { font-size: .8rem; color: var(--ob-muted); }
    </style>
@endpush

@push('js')
    <script>
        (function () {
            const orderUrl = @json(auth()->user()->can('read b2b/orders') ? route('b2b/orders/show', ['order' => '__id__']) : null);
            const $modal = $('#report-orders-modal');
            const $content = $modal.find('.modal-content');
            let params = {};
            let page = 1;
            let controller = null;

            function row(order) {
                const esc = B2bReport.escape;
                const number = orderUrl
                    ? `<a class="b2b-report-orders__num" href="${orderUrl.replace('__id__', order.id)}" target="_blank" rel="noopener">${esc(order.order_number)}</a>`
                    : `<span class="b2b-report-orders__num">${esc(order.order_number)}</span>`;
                return `<tr>
                    <td>${number}<span class="ob-cell-meta">${esc(order.date)}</span></td>
                    <td>${esc(order.customer || '-')}<span class="ob-cell-meta"><i class="fas fa-store" aria-hidden="true"></i> ${esc(order.outlet || '-')}</span></td>
                    <td><span class="badge badge-${esc(order.status_variant)}">${esc(order.status)}</span><span class="ob-cell-meta">${esc(order.payment_type)}</span></td>
                    <td class="text-end">${B2bReport.rupiah(order.amount)}</td>
                </tr>`;
            }

            function load() {
                controller?.abort();
                controller = new AbortController();
                $content.attr('aria-busy', 'true').find('[data-report-error]').prop('hidden', true);
                B2bReport.fetch('orders', Object.assign({}, params, { customer_name: undefined, page }), controller.signal)
                    .then(data => {
                        $content.attr('aria-busy', 'false');
                        $modal.find('[data-orders-total]').html(
                            `<span><strong>${B2bReport.number(data.totals.orders)}</strong> pesanan</span><span>Total <strong>${B2bReport.rupiah(data.totals.amount)}</strong></span>`
                        );
                        $modal.find('[data-orders-rows]').html(data.items.map(row).join(''));
                        $modal.find('.b2b-report-orders__wrap').prop('hidden', !data.items.length);
                        $modal.find('[data-orders-empty]').prop('hidden', !!data.items.length);
                        page = data.page;
                        $modal.find('[data-orders-page]').text(data.last_page > 1 ? `Halaman ${data.page} dari ${data.last_page}` : '');
                        $modal.find('[data-orders-nav="-1"]').prop('disabled', data.page <= 1);
                        $modal.find('[data-orders-nav="1"]').prop('disabled', data.page >= data.last_page);
                        $modal.find('.b2b-report-orders__foot').toggleClass('d-none', data.last_page <= 1);
                    })
                    .catch(error => { if (error.name !== 'AbortError') B2bReport.showError($content, error); });
            }

            $modal.on('click', '[data-orders-nav]', function () {
                page += Number(this.dataset.ordersNav);
                load();
            });
            $modal.on('click', '[data-orders-retry]', load);

            B2bReport.ordersModal = function ({ title = 'Daftar pesanan', params: extra = {} } = {}) {
                params = extra;
                page = 1;
                $modal.find('#report-orders-title').text(title);
                $modal.find('[data-orders-sub]').text(B2bReport.describe(extra));
                $modal.find('[data-orders-rows]').empty();
                $modal.find('[data-orders-total]').html('<span class="b2b-skeleton"></span>');
                bootstrap.Modal.getOrCreateInstance($modal[0]).show();
                load();
            };
        })();
    </script>
@endpush

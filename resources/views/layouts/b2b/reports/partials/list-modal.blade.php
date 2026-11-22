{{--
    Modal daftar transaksi Laporan B2B (tingkat terakhir drilldown dan kartu KPI). Satu modal untuk semua daftar:
      B2bReport.ordersModal({ title, params: { from, to, customer_id, product, ... }, labels: ['PT X', ...] });
      B2bReport.invoicesModal({ title, params: { customer_id, bucket }, labels: ['PT X', '31–60 hari'] });
      B2bReport.mutationsModal({ title, params: { by: 'outlet', key: 0 }, labels: ['Pusat'] });
    params menimpa filter aktif, labels = nama pilihan drilldown untuk keterangan di bawah judul.
    Pesanan: dengan filter produk / varian / kategori, kolom Total berganti "Nilai item" (nilai barang itu saja).
    Invoice: piutang per hari ini (tidak ikut periode), jatuh tempo terlama dulu.
    Mutasi kas: pembayaran (+) dan refund (−) di periode, terbaru dulu.
    Nomor pesanan / invoice menjadi link ke detail pesanan kalau user punya akses menu Pesanan (detail invoice ada di sana).
--}}
<div class="modal fade b2b-modal" id="report-list-modal" tabindex="-1" aria-labelledby="report-list-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content ob-page">
            <div class="modal-header">
                <div class="min-w-0">
                    <h2 class="b2b-modal__title" id="report-list-title">Daftar pesanan</h2>
                    <p class="b2b-modal__sub" data-list-sub></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="b2b-report-list__total" data-list-total></div>
                <div class="ob-table-wrap b2b-report-list__wrap">
                    <table class="table w-100 b2b-report-list">
                        <thead><tr data-list-head></tr></thead>
                        <tbody data-list-rows></tbody>
                    </table>
                </div>
                <div class="b2b-report-empty" data-list-empty hidden></div>
                <div class="b2b-report-error" data-report-error hidden>
                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                    <span data-report-error-text>Daftar gagal dimuat.</span>
                    <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" data-list-retry>Coba lagi</button>
                </div>
            </div>
            <div class="modal-footer b2b-report-list__foot">
                <span class="b2b-report-list__page" data-list-page></span>
                <div class="b2b-actions">
                    <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" data-list-nav="-1" aria-label="Halaman sebelumnya"><i class="fas fa-chevron-left" aria-hidden="true"></i> Sebelumnya</button>
                    <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" data-list-nav="1" aria-label="Halaman berikutnya">Berikutnya <i class="fas fa-chevron-right" aria-hidden="true"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('css')
    <style>
        .b2b-report-list__total { display: flex; flex-wrap: wrap; gap: .25rem 1rem; padding: .7rem .9rem; border: 1px solid var(--ob-border); border-radius: 12px; background: var(--ob-surface-2); font-size: .85rem; color: var(--ob-muted); }
        .b2b-report-list__total strong { color: var(--ob-text); font-variant-numeric: tabular-nums; }
        .b2b-report-list__wrap { margin: 0; overflow-x: auto; }
        /* Tablet: modal-lg baru berlaku di ≥ 992px, jadi dilebarkan sendiri supaya kolom nominal tidak terpotong. */
        @media (min-width: 576px) { #report-list-modal .modal-dialog { max-width: min(800px, calc(100% - 2rem)); } }
        #report-list-modal .modal-content[aria-busy="true"] .b2b-report-list__wrap { opacity: .45; }
        /* .ob-page di depan supaya menang dari gaya tabel Kaiadmin */
        .ob-page .b2b-report-list { margin: 0; font-size: .84rem; }
        .ob-page .b2b-report-list th { padding: .5rem .6rem !important; font-size: .68rem !important; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--ob-muted); white-space: nowrap; }
        .ob-page .b2b-report-list td { padding: .6rem !important; vertical-align: middle; }
        .b2b-report-list .b2b-report-list__num { font-weight: 700; color: var(--ob-text); white-space: nowrap; }
        .b2b-report-list a.b2b-report-list__num { color: var(--ob-primary); }
        .b2b-report-list .ob-cell-meta { display: block; font-size: .74rem; color: var(--ob-muted); white-space: nowrap; }
        .b2b-report-list .ob-cell-meta.text-danger { color: var(--ob-danger) !important; font-weight: 600; }
        .b2b-report-list .text-end { font-variant-numeric: tabular-nums; white-space: nowrap; font-weight: 700; }
        .b2b-report-list .text-end .ob-cell-meta { font-weight: 400; }
        .b2b-report-list .b2b-report-list__out { color: var(--ob-danger); }
        .b2b-report-list__foot { justify-content: space-between; }
        .b2b-report-list__foot .ob-btn { min-height: 38px !important; }
        .b2b-report-list__page { font-size: .8rem; color: var(--ob-muted); }
    </style>
@endpush

@push('js')
    <script>
        (function () {
            const orderUrl = @json(auth()->user()->can('read b2b/orders') ? route('b2b/orders/show', ['order' => '__id__']) : null);
            const $modal = $('#report-list-modal');
            const $content = $modal.find('.modal-content');
            let current = null;     // { section, params, row, totals, head }
            let page = 1;
            let controller = null;

            const esc = value => B2bReport.escape(value);
            const meta = (text, cls = '') => `<span class="ob-cell-meta ${cls}">${text}</span>`;
            // Nomor dokumen, jadi link ke detail pesanan kalau user punya akses.
            const docNumber = (orderId, text) => orderUrl
                ? `<a class="b2b-report-list__num" href="${orderUrl.replace('__id__', orderId)}" target="_blank" rel="noopener">${esc(text)}</a>`
                : `<span class="b2b-report-list__num">${esc(text)}</span>`;

            function load() {
                controller?.abort();
                controller = new AbortController();
                $content.attr('aria-busy', 'true').find('[data-report-error]').prop('hidden', true);
                B2bReport.fetch(current.section, Object.assign({}, current.params, { page }), controller.signal)
                    .then(data => {
                        $content.attr('aria-busy', 'false');
                        $modal.find('[data-list-total]').html(current.totals(data));
                        $modal.find('[data-list-head]').html(current.head(data)
                            .map(col => `<th scope="col"${col.end ? ' class="text-end"' : ''}>${esc(col.label)}</th>`).join(''));
                        $modal.find('[data-list-rows]').html(data.items.map(current.row).join(''));
                        $modal.find('.b2b-report-list__wrap').prop('hidden', !data.items.length);
                        $modal.find('[data-list-empty]').prop('hidden', !!data.items.length);
                        page = data.page;
                        $modal.find('[data-list-page]').text(data.last_page > 1 ? `Halaman ${data.page} dari ${data.last_page}` : '');
                        $modal.find('[data-list-nav="-1"]').prop('disabled', data.page <= 1);
                        $modal.find('[data-list-nav="1"]').prop('disabled', data.page >= data.last_page);
                        $modal.find('.b2b-report-list__foot').toggleClass('d-none', data.last_page <= 1);
                    })
                    .catch(error => { if (error.name !== 'AbortError') B2bReport.showError($content, error); });
            }

            function open(options) {
                current = options;
                page = 1;
                $modal.find('#report-list-title').text(options.title);
                $modal.find('[data-list-sub]').text(options.sub);
                $modal.find('[data-list-empty]').text(options.empty).prop('hidden', true);
                $modal.find('[data-list-rows], [data-list-head]').empty();
                $modal.find('[data-list-total]').html('<span class="b2b-skeleton"></span>');
                bootstrap.Modal.getOrCreateInstance($modal[0]).show();
                load();
            }

            $modal.on('click', '[data-list-nav]', function () {
                page += Number(this.dataset.listNav);
                load();
            });
            $modal.on('click', '[data-list-retry]', load);

            /** Pesanan penjualan (dikonfirmasi + selesai) di periode aktif. */
            B2bReport.ordersModal = function ({ title = 'Daftar pesanan', params = {}, labels = [] } = {}) {
                open({
                    section: 'orders',
                    params,
                    title,
                    sub: B2bReport.describe(params, labels),
                    empty: 'Tidak ada pesanan.',
                    head: data => [
                        { label: 'Pesanan' }, { label: 'Customer' }, { label: 'Status' },
                        { label: data.item_filter ? 'Nilai item' : 'Total', end: true },
                    ],
                    totals: data => `<span><strong>${B2bReport.number(data.totals.orders)}</strong> pesanan</span>`
                        + `<span>${data.item_filter ? 'Nilai item' : 'Total'} <strong>${B2bReport.rupiah(data.totals.amount)}</strong></span>`,
                    row: order => `<tr>
                        <td>${docNumber(order.id, order.order_number)}${meta(esc(order.date))}</td>
                        <td>${esc(order.customer || '-')}${meta(`<i class="fas fa-store" aria-hidden="true"></i> ${esc(order.outlet || '-')}`)}</td>
                        <td><span class="badge badge-${esc(order.status_variant)}">${esc(order.status)}</span>${meta(esc(order.payment_type))}</td>
                        <td class="text-end">${B2bReport.rupiah(order.amount)}${order.amount !== order.order_total
                            ? meta(`dari ${B2bReport.rupiah(order.order_total)}`) : ''}</td>
                    </tr>`,
                });
            };

            /** Keterangan umur satu invoice: "Lewat 12 hari" / "Jatuh tempo hari ini" / "3 hari lagi". */
            function dueText(days) {
                if (days > 0) return meta(`Lewat ${B2bReport.number(days)} hari`, 'text-danger');
                return meta(days === 0 ? 'Jatuh tempo hari ini' : `${B2bReport.number(-days)} hari lagi`);
            }

            /** Invoice belum lunas per hari ini; params.bucket = kelompok umur (current, 1-30, ..., overdue). */
            B2bReport.invoicesModal = function ({ title = 'Invoice belum lunas', params = {}, labels = [] } = {}) {
                open({
                    section: 'invoices',
                    params,
                    title,
                    sub: B2bReport.describe(params, labels, { period: false }),
                    empty: 'Tidak ada invoice yang belum lunas.',
                    head: () => [{ label: 'Invoice' }, { label: 'Customer' }, { label: 'Jatuh tempo' }, { label: 'Sisa tagihan', end: true }],
                    totals: data => `<span><strong>${B2bReport.number(data.totals.invoices)}</strong> invoice</span>`
                        + (data.totals.customers > 1 ? `<span><strong>${B2bReport.number(data.totals.customers)}</strong> customer</span>` : '')
                        + `<span>Sisa tagihan <strong>${B2bReport.rupiah(data.totals.amount)}</strong></span>`,
                    row: invoice => `<tr>
                        <td>${docNumber(invoice.order_id, invoice.invoice_number)}${meta(`Terbit ${esc(invoice.issue_date)} · ${esc(invoice.order_number || '-')}`)}</td>
                        <td>${esc(invoice.customer || '-')}${meta(`<i class="fas fa-store" aria-hidden="true"></i> ${esc(invoice.outlet || '-')}`)}</td>
                        <td>${esc(invoice.due_date)}${dueText(invoice.days_overdue)}</td>
                        <td class="text-end">${B2bReport.rupiah(invoice.outstanding)}${invoice.paid
                            ? meta(`dari ${B2bReport.rupiah(invoice.total)}`) : ''}</td>
                    </tr>`,
                });
            };

            /** Mutasi kas di periode; params.by + params.key = satu kelompok rekap kas (metode / outlet penerima / kasir). */
            B2bReport.mutationsModal = function ({ title = 'Mutasi kas', params = {}, labels = [] } = {}) {
                const { number, rupiah } = B2bReport;
                open({
                    section: 'mutations',
                    params,
                    title,
                    sub: B2bReport.describe(params, labels),
                    empty: 'Tidak ada pembayaran atau refund di periode ini.',
                    head: () => [{ label: 'Transaksi' }, { label: 'Customer' }, { label: 'Metode' }, { label: 'Nominal', end: true }],
                    totals: data => `<span>Masuk <strong>${rupiah(data.totals.received)}</strong> (${number(data.totals.received_count)})</span>`
                        + (data.totals.refund_count ? `<span>Refund <strong>${rupiah(data.totals.refunded)}</strong> (${number(data.totals.refund_count)})</span>` : '')
                        + `<span>Bersih <strong>${rupiah(data.totals.net)}</strong></span>`,
                    row: item => {
                        const refund = item.type === 'refund';
                        const label = refund
                            ? `<span class="b2b-report-list__num b2b-report-list__out">Refund</span>${meta(`${esc(item.date)} · atas ${esc(item.number || '-')}`)}`
                            : `<span class="b2b-report-list__num">${esc(item.number)}</span>${meta(esc(item.date) + (item.refunded ? ' · sudah direfund' : ''))}`;
                        const invoice = item.invoice_number
                            ? (orderUrl ? `<a href="${orderUrl.replace('__id__', item.order_id)}" target="_blank" rel="noopener">${esc(item.invoice_number)}</a>` : esc(item.invoice_number))
                            : '-';
                        const where = [item.outlet, item.cashier].filter(Boolean).map(esc).join(' · ');
                        return `<tr>
                            <td>${label}</td>
                            <td>${esc(item.customer || '-')}${meta(invoice)}</td>
                            <td>${esc(item.method || '-')}${meta(where || '-')}</td>
                            <td class="text-end${refund ? ' b2b-report-list__out' : ''}">${rupiah(item.amount)}</td>
                        </tr>`;
                    },
                });
            };
        })();
    </script>
@endpush

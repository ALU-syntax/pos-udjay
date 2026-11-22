{{--
    Bagian E Laporan B2B: kontrak berjalan yang berakhir dalam 30 hari, per hari ini (tidak ikut periode dan outlet,
    karena kontrak per customer; filter customer tetap berlaku). Penjualan selama kontrak mengikuti filter outlet.
    Kontrak tanpa lanjutan diberi tombol Perpanjang (form kontrak baru dengan harga disalin) bila user boleh membuat kontrak.
--}}
@php
    $contractUrl = auth()->user()->can('read b2b/contracts') ? route('b2b/contracts/show', ['contract' => '__id__']) : null;
    $renewUrl = auth()->user()->can('create b2b/contracts') ? route('b2b/contracts/create', ['copy_from' => '__id__']) : null;
@endphp
<section class="ob-card b2b-report-card" data-report-section="contracts" aria-busy="true">
    <div class="b2b-card__head">
        <div class="min-w-0">
            <h2 class="b2b-card__title"><i class="fas fa-file-signature" aria-hidden="true"></i> Kontrak hampir berakhir <span class="b2b-report-asof">per hari ini</span></h2>
            <p class="b2b-card__desc" data-contracts-desc>&nbsp;</p>
        </div>
    </div>
    <div class="b2b-card__body">
        <div class="b2b-report-table-wrap" data-contracts-body hidden>
            <table class="b2b-report-table b2b-report-contracts">
                <thead>
                    <tr>
                        <th scope="col">Customer</th>
                        <th scope="col">Kontrak</th>
                        <th scope="col">Berakhir</th>
                        <th scope="col">Kontrak lanjutan</th>
                        <th scope="col" class="text-end" title="Pesanan dikonfirmasi + selesai sejak kontrak mulai, mengikuti filter outlet">Penjualan kontrak</th>
                    </tr>
                </thead>
                <tbody data-contracts-rows></tbody>
            </table>
        </div>
        <div class="b2b-report-empty b2b-report-empty--short" data-contracts-empty hidden></div>
        <div class="b2b-report-error" data-report-error hidden>
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <span data-report-error-text>Kontrak hampir berakhir gagal dimuat.</span>
            <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" data-report-retry>Coba lagi</button>
        </div>
    </div>
</section>

@push('css')
    <style>
        .b2b-report-card[aria-busy="true"] [data-contracts-body] { opacity: .45; }
        .b2b-report-empty--short { min-height: 120px; }
        .b2b-report-contracts strong { font-weight: 600; color: var(--ob-text); }
        .b2b-report-contracts a { font-weight: 600; color: var(--ob-primary); }
        .b2b-report-contracts .ob-cell-meta.is-danger { color: var(--ob-danger); font-weight: 600; }
        .b2b-report-contracts .ob-cell-meta.is-warning { color: #c77700; font-weight: 600; }
        .b2b-report-contracts__next { display: inline-flex; align-items: center; gap: .35rem; color: var(--ob-success, #2e9e3e); font-weight: 600; white-space: nowrap; }
        .b2b-report-contracts__none { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem .6rem; color: var(--ob-muted); white-space: nowrap; }
        .b2b-report-contracts__none .ob-btn { min-height: 30px !important; padding: 0 .65rem !important; font-size: .76rem !important; }
    </style>
@endpush

@push('js')
    <script>
        (function () {
            const contractUrl = @json($contractUrl);
            const renewUrl = @json($renewUrl);
            const $box = $('[data-report-section="contracts"]');

            const esc = value => B2bReport.escape(value);
            const meta = (text, cls = '') => `<span class="ob-cell-meta ${cls}">${text}</span>`;
            const link = (url, id, text) => url ? `<a href="${url.replace('__id__', id)}" target="_blank" rel="noopener">${esc(text)}</a>` : `<strong>${esc(text)}</strong>`;

            // ≤ 7 hari merah, selebihnya oranye.
            function daysLeft(days) {
                const text = days === 0 ? 'Berakhir hari ini' : `${B2bReport.number(days)} hari lagi`;
                return meta(text, days <= 7 ? 'is-danger' : 'is-warning');
            }

            function successor(item) {
                if (item.successor) {
                    return `<span class="b2b-report-contracts__next"><i class="fas fa-check-circle" aria-hidden="true"></i> Sudah ada</span>`
                        + meta(link(contractUrl, item.successor.id, item.successor.contract_number))
                        + meta(`Mulai ${esc(item.successor.start_date)}`);
                }
                const renew = renewUrl
                    ? `<a class="btn ob-btn ob-btn-outline ob-btn-sm" href="${renewUrl.replace('__id__', item.id)}" target="_blank" rel="noopener"><i class="fas fa-redo" aria-hidden="true"></i> Perpanjang</a>`
                    : '';
                return `<span class="b2b-report-contracts__none">Belum ada ${renew}</span>`;
            }

            B2bReport.section('contracts', data => {
                const { number, rupiah } = B2bReport;
                $box.find('[data-contracts-desc]').text(data.total
                    ? `${number(data.total)} kontrak berakhir dalam ${data.days} hari ke depan, ${number(data.without_successor)} belum punya kontrak lanjutan. Tidak mengikuti periode dan outlet; penjualan kontrak = sejak kontrak mulai, mengikuti outlet.`
                    : `Kontrak berjalan yang berakhir dalam ${data.days} hari ke depan. Tidak mengikuti periode dan outlet.`);
                $box.find('[data-contracts-empty]').text(`Tidak ada kontrak yang berakhir dalam ${data.days} hari ke depan.`).prop('hidden', !!data.total);
                $box.find('[data-contracts-body]').prop('hidden', !data.total);
                $box.find('[data-contracts-rows]').html(data.items.map(item => `<tr>
                    <td><strong>${esc(item.customer || '-')}</strong>${meta(esc(item.customer_code || ''))}</td>
                    <td>${link(contractUrl, item.id, item.contract_number)}${meta(`Mulai ${esc(item.start_date)}`)}</td>
                    <td class="text-nowrap">${esc(item.end_date)}${daysLeft(item.days_left)}</td>
                    <td>${successor(item)}</td>
                    <td class="text-end"><strong>${rupiah(item.sales.amount)}</strong>${meta(`${number(item.sales.orders)} pesanan`)}</td>
                </tr>`).join(''));
            });
        })();
    </script>
@endpush

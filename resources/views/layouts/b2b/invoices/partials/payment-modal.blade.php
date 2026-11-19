{{--
    Dialog "Catat Pelunasan" invoice tempo. Dipakai di menu Invoice dan detail pesanan.
    Butuh: $paymentMethods (PaymentService::methodOptions), $outlets (outlet milik user).
    Pemicu: elemen dengan [data-pay-invoice] + data-url, data-number, data-customer, data-total, data-paid,
    data-outstanding, data-issue (Y-m-d), data-outlet. Setelah tersimpan: muat ulang DataTable invoice + kartu ringkasan kalau ada, kalau tidak reload halaman.
--}}
@can('pay b2b/invoices')
    @include('layouts.b2b.partials.datepicker')

    <div class="modal fade b2b-modal" id="pay-modal" tabindex="-1" aria-labelledby="pay-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <form class="modal-content ob-page b2b-form" id="pay-form" novalidate>
                @csrf
                <div class="modal-header">
                    <div>
                        <h2 class="b2b-modal__title" id="pay-title">Catat Pelunasan</h2>
                        <p class="b2b-modal__sub" id="pay-sub"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="b2b-modal__total">
                        <div class="b2b-payments__row b2b-muted"><span>Total tagihan</span><span id="pay-total"></span></div>
                        <div class="b2b-payments__row b2b-muted"><span>Sudah dibayar</span><span id="pay-paid"></span></div>
                        <div class="b2b-payments__row b2b-pay-outstanding"><span>Sisa tagihan</span><strong id="pay-outstanding"></strong></div>
                    </div>

                    @if (empty($paymentMethods))
                        <div class="ob-notice mb-0" role="alert"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i><span>Belum ada metode pembayaran aktif. Tambahkan dulu di menu master pembayaran.</span></div>
                    @else
                        <div class="b2b-field">
                            <label for="pay-amount-display" class="form-label">Nominal dibayar <span class="b2b-required">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input id="pay-amount-display" type="text" inputmode="numeric" class="form-control b2b-pay-amount" autocomplete="off">
                            </div>
                            <input type="hidden" name="amount" id="pay-amount">
                            <div class="b2b-quick-days" role="group" aria-label="Pilih cepat nominal">
                                <button type="button" data-pay-fill="full">Lunasi sisa</button>
                            </div>
                            <small class="b2b-help" id="pay-amount-hint"></small>
                        </div>

                        <div class="b2b-field">
                            <label for="pay-date-display" class="form-label">Tanggal bayar <span class="b2b-required">*</span></label>
                            <div class="b2b-range">
                                <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                                <input id="pay-date-display" type="text" class="form-control" readonly>
                                <span class="b2b-range__days" id="pay-date-tag"></span>
                            </div>
                            <input type="hidden" name="paid_date" id="pay-date">
                        </div>

                        <div class="b2b-field">
                            <span class="form-label">Metode <span class="b2b-required">*</span></span>
                            <div class="b2b-segment {{ count($paymentMethods) === 3 ? 'b2b-segment--3' : '' }}" role="radiogroup" aria-label="Metode pembayaran">
                                @foreach ($paymentMethods as $category)
                                    <label><input type="radio" name="category_payment_id" value="{{ $category['id'] }}"> <span>{{ $category['name'] }}</span></label>
                                @endforeach
                            </div>
                        </div>
                        <div class="b2b-field" id="pay-method-field" hidden>
                            <span class="form-label">Jenis <span class="b2b-required">*</span></span>
                            <div class="b2b-quick-days b2b-pay__sub" id="pay-methods" role="group" aria-label="Jenis pembayaran"></div>
                            <input type="hidden" name="payment_id" id="pay-method-id">
                        </div>

                        <div class="b2b-field">
                            <label for="pay-outlet" class="form-label">Diterima di</label>
                            <select id="pay-outlet" name="outlet_id" class="form-control">
                                <option value="">Rekening pusat (tanpa outlet)</option>
                                @foreach ($outlets as $outlet)
                                    <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                                @endforeach
                            </select>
                            <small class="b2b-help">Pilih outlet bila dibayar di outlet (tunai/EDC/QRIS). Transfer ke rekening perusahaan: rekening pusat.</small>
                        </div>

                        <div class="b2b-field">
                            <label for="pay-reference" class="form-label">No. referensi <span class="b2b-optional">(opsional)</span></label>
                            <input id="pay-reference" name="reference" type="text" class="form-control" maxlength="100" autocomplete="off" placeholder="Kode approval EDC / no. referensi transfer">
                        </div>
                        <div class="b2b-field">
                            <label for="pay-attachment" class="form-label">Bukti bayar <span class="b2b-optional">(opsional · PDF/JPG/PNG, maks 5 MB)</span></label>
                            <input id="pay-attachment" name="attachment" type="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="b2b-field">
                            <label for="pay-notes" class="form-label">Catatan <span class="b2b-optional">(opsional)</span></label>
                            <textarea id="pay-notes" name="notes" class="form-control" rows="2" maxlength="500"></textarea>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn ob-btn ob-btn-outline" data-bs-dismiss="modal">Kembali</button>
                    <button type="submit" class="btn ob-btn ob-btn-primary flex-grow-1" @disabled(empty($paymentMethods))>
                        <i class="fas fa-check-circle me-1" aria-hidden="true"></i> <span id="pay-submit-label">Simpan Pembayaran</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('css')
        <style>
            .b2b-pay-outstanding { margin-top: .5rem; padding-top: .5rem; border-top: 1px dashed var(--ob-border-strong); font-weight: 600; color: var(--ob-text); }
            .b2b-pay-outstanding strong { font-size: 1.1rem; }
            .b2b-modal .input-group-text { min-height: 44px; border-color: var(--ob-border-strong); background: var(--ob-surface-2); color: var(--ob-muted); font-weight: 600; }
            .b2b-modal .b2b-pay-amount { min-height: 44px; font-size: 1rem; font-weight: 700; font-variant-numeric: tabular-nums; }
        </style>
    @endpush

    @push('js')
        <script>
            $(function () {
                const modalEl = document.getElementById('pay-modal');
                if (!modalEl) return;
                document.body.appendChild(modalEl);
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                const $form = $('#pay-form');
                const methods = new Map(@json($paymentMethods).map(category => [category.id, category]));
                const TODAY = @json(today()->toDateString());
                const shortMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                const number = value => Number(value || 0).toLocaleString('id-ID');
                const parse = value => Number(String(value || '').replace(/\D/g, '')) || 0;
                let current = null; // data invoice yang sedang dibayar

                // ---------- Tanggal bayar (date picker seragam, pilih satu tanggal) ----------
                const $dateDisplay = $('#pay-date-display');
                let picker = null;
                if ($dateDisplay.length && $.fn.daterangepicker) {
                    $dateDisplay.daterangepicker({
                        singleDatePicker: true, autoApply: true, autoUpdateInput: false, parentEl: '#pay-modal', drops: 'auto',
                        maxDate: moment(TODAY, 'YYYY-MM-DD'),
                        locale: {
                            format: 'DD/MM/YYYY',
                            daysOfWeek: ['Mg', 'Sn', 'Sl', 'Rb', 'Km', 'Jm', 'Sb'],
                            monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                            firstDay: 1,
                        },
                    });
                    picker = $dateDisplay.data('daterangepicker');
                    picker.container.addClass('b2b-drp');
                    $dateDisplay
                        .on('show.daterangepicker', () => $dateDisplay.addClass('is-open'))
                        .on('hide.daterangepicker', () => $dateDisplay.removeClass('is-open'))
                        .on('apply.daterangepicker', (event, p) => setDate(p.startDate.format('YYYY-MM-DD')));
                    $(modalEl).on('hide.bs.modal', () => picker.hide());
                }
                function setDate(iso) {
                    $('#pay-date').val(iso);
                    const [y, m, d] = iso.split('-').map(Number);
                    $dateDisplay.val(`${String(d).padStart(2, '0')} ${shortMonths[m - 1]} ${y}`);
                    $('#pay-date-tag').text(iso === TODAY ? 'hari ini' : '');
                    if (picker) { picker.setStartDate(moment(iso, 'YYYY-MM-DD')); picker.setEndDate(moment(iso, 'YYYY-MM-DD')); }
                }

                // ---------- Nominal ----------
                function setAmount(value) {
                    $('#pay-amount-display').val(value ? number(value) : '');
                    renderAmount();
                }
                function renderAmount() {
                    const amount = parse($('#pay-amount-display').val());
                    $('#pay-amount').val(amount || '');
                    const outstanding = current ? current.outstanding : 0;
                    $('[data-pay-fill="full"]').attr('aria-pressed', String(!!amount && amount === outstanding));
                    $('#pay-amount-hint').text(!amount ? '' : amount > outstanding
                        ? 'Melebihi sisa tagihan.'
                        : (amount === outstanding ? 'Invoice akan lunas.' : `Dibayar sebagian, sisa Rp ${number(outstanding - amount)}.`))
                        .toggleClass('text-danger', amount > outstanding);
                    $('#pay-submit-label').text(amount ? `Simpan Pembayaran · Rp ${number(amount)}` : 'Simpan Pembayaran');
                }
                $('#pay-amount-display').on('input', function () { const v = parse(this.value); this.value = v ? number(v) : ''; renderAmount(); });
                $form.on('click', '[data-pay-fill="full"]', () => setAmount(current?.outstanding));

                // ---------- Metode + jenis ----------
                function renderMethods() {
                    const category = methods.get(Number($form.find('[name="category_payment_id"]:checked').val()));
                    const list = category?.methods || [];
                    let selected = Number($('#pay-method-id').val()) || null;
                    if (!list.some(m => m.id === selected)) selected = list.length === 1 ? list[0].id : null;
                    $('#pay-method-id').val(selected || '');
                    $('#pay-method-field').prop('hidden', !list.length);
                    $('#pay-methods').html(list.map(m =>
                        `<button type="button" data-method="${m.id}" aria-pressed="${m.id === selected}">${B2b.escape(m.name)}</button>`).join(''));
                }
                $form.on('change', '[name="category_payment_id"]', renderMethods);
                $('#pay-methods').on('click', 'button', function () { $('#pay-method-id').val(this.dataset.method); renderMethods(); });

                // ---------- Buka dialog ----------
                $(document).on('click', '[data-pay-invoice]', function (event) {
                    event.preventDefault();
                    const d = this.dataset;
                    current = { url: d.url, outstanding: Number(d.outstanding) };
                    $form.attr('action', d.url)[0].reset();
                    B2b.clearErrors($form);
                    $('#pay-sub').text(`${d.number} · ${d.customer}`);
                    $('#pay-total').text(B2b.rupiah(d.total));
                    $('#pay-paid').text(B2b.rupiah(d.paid));
                    $('#pay-outstanding').text(B2b.rupiah(d.outstanding));
                    $('#pay-method-id').val('');
                    renderMethods();
                    $('#pay-outlet').val($(`#pay-outlet option[value="${d.outlet}"]`).length ? d.outlet : '');
                    if (picker) picker.minDate = moment(d.issue, 'YYYY-MM-DD');
                    setDate(TODAY);
                    setAmount(current.outstanding);
                    modal.show();
                });

                // ---------- Simpan ----------
                $form.on('submit', function (event) {
                    event.preventDefault();
                    const amount = parse($('#pay-amount-display').val());
                    const category = methods.get(Number($form.find('[name="category_payment_id"]:checked').val()));
                    if (!amount || amount > current.outstanding) { $('#pay-amount-display').trigger('focus'); return showToast('error', `Nominal harus antara Rp 1 dan sisa tagihan Rp ${number(current.outstanding)}.`); }
                    if (!category) return showToast('error', 'Pilih metode pembayaran.');
                    if (category.methods.length && !$('#pay-method-id').val()) return showToast('error', `Pilih jenis ${category.name}.`);
                    B2b.submit(this, {
                        onSuccess: () => {
                            modal.hide();
                            const table = window.LaravelDataTables?.['b2b-invoice-table'];
                            if (!table) return window.location.reload();
                            table.ajax.reload(null, false);
                            // Angka kartu ringkasan ikut diperbarui tanpa reload (filter tetap).
                            $('.ob-stats').load(`${window.location.pathname} .ob-stats > *`, () => $(document).trigger('b2b:stats-refreshed'));
                        },
                    });
                });
            });
        </script>
    @endpush
@endcan

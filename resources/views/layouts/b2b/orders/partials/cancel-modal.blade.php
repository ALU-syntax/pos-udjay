{{-- Batal pesanan lunas: pembayaran di-reverse dan pengembalian dana dicatat. Butuh $order, $invoice, $payments, $paymentMethods, $rupiah. --}}
    @if ($order->cancelPermission() && auth()->user()->can($order->cancelPermission()))
        @php $firstPayment = $payments->firstWhere('status', 'posted'); @endphp
        {{-- Batal pesanan lunas: pembayaran di-reverse dan pengembalian dana dicatat. Dipindah ke <body> saat halaman dimuat. --}}
        <div class="modal fade b2b-modal" id="cancel-modal" tabindex="-1" aria-labelledby="cancel-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <form class="modal-content ob-page b2b-form" id="cancel-form" action="{{ route('b2b/orders/cancel', $order->id) }}" novalidate>
                    @csrf
                    <div class="modal-header">
                        <div>
                            <h2 class="b2b-modal__title" id="cancel-title">Batalkan Pesanan yang Sudah Dibayar</h2>
                            <p class="b2b-modal__sub">{{ $order->order_number }} · {{ $order->customer?->name }}</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="b2b-modal__total">
                            @foreach ($payments->where('status', 'posted') as $payment)
                                <div class="b2b-payments__row b2b-muted"><span>{{ $payment->method_name }} · {{ $payment->payment_number }}</span><span>{{ $rupiah($payment->amount) }}</span></div>
                            @endforeach
                            <div class="b2b-payments__row b2b-refund-total"><span>Dikembalikan ke customer</span><strong>{{ $rupiah($invoice->paid_amount) }}</strong></div>
                        </div>
                        <div class="ob-notice mb-0" role="note">
                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                            <span>Pesanan dibatalkan, stok dikembalikan, invoice dibatalkan, dan pembayaran dicatat sebagai pengembalian dana. Pastikan uangnya sudah dikembalikan ke customer.</span>
                        </div>

                        @if (empty($paymentMethods))
                            <div class="ob-notice mb-0" role="alert"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i><span>Belum ada metode pembayaran aktif di master pembayaran.</span></div>
                        @else
                            <div class="b2b-field">
                                <span class="form-label">Metode pengembalian <span class="b2b-required">*</span></span>
                                <div class="b2b-segment {{ count($paymentMethods) === 3 ? 'b2b-segment--3' : '' }}" role="radiogroup" aria-label="Metode pengembalian dana">
                                    @foreach ($paymentMethods as $category)
                                        <label><input type="radio" name="refund[category_payment_id]" value="{{ $category['id'] }}" @checked($firstPayment?->category_payment_id === $category['id'])> <span>{{ $category['name'] }}</span></label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="b2b-field" id="refund-sub-field" hidden>
                                <span class="form-label">Jenis <span class="b2b-required">*</span></span>
                                <div class="b2b-quick-days b2b-pay__sub" id="refund-sub" role="group" aria-label="Jenis metode pengembalian"></div>
                                <input type="hidden" name="refund[payment_id]" id="refund-payment-id" value="{{ $firstPayment?->payment_id }}">
                            </div>
                            <div class="b2b-field">
                                <label for="refund-reference" class="form-label">No. referensi <span class="b2b-optional">(opsional)</span></label>
                                <input id="refund-reference" name="refund[reference]" type="text" class="form-control" maxlength="100" autocomplete="off" placeholder="Mis. no. referensi transfer pengembalian">
                            </div>
                        @endif
                        <div class="b2b-field">
                            <label for="cancel-reason" class="form-label">Alasan pembatalan <span class="b2b-required">*</span></label>
                            <textarea id="cancel-reason" name="reason" class="form-control" rows="2" maxlength="255" required placeholder="Mis. acara customer dibatalkan"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn ob-btn ob-btn-outline" data-bs-dismiss="modal">Kembali</button>
                        <button type="submit" class="btn ob-btn ob-btn-danger flex-grow-1" @disabled(empty($paymentMethods))><i class="fas fa-undo me-1" aria-hidden="true"></i> Batalkan &amp; Catat Pengembalian</button>
                    </div>
                </form>
            </div>
        </div>

        @push('js')
            <script>
                $(function () {
                    const modal = document.getElementById('cancel-modal');
                    document.body.appendChild(modal);
                    const methods = new Map(@json($paymentMethods).map(category => [category.id, category]));
                    const $form = $('#cancel-form');
                    const $paymentId = $('#refund-payment-id');

                    // Sub metode (mis. EDC → DEBIT/QRIS); otomatis terpilih kalau hanya satu.
                    function renderSub() {
                        const category = methods.get(Number($form.find('[name="refund[category_payment_id]"]:checked').val()));
                        const list = category?.methods || [];
                        let selected = Number($paymentId.val()) || null;
                        if (!list.some(m => m.id === selected)) selected = list.length === 1 ? list[0].id : null;
                        $paymentId.val(selected || '');
                        $('#refund-sub-field').prop('hidden', !list.length);
                        $('#refund-sub').html(list.map(m =>
                            `<button type="button" data-method="${m.id}" aria-pressed="${m.id === selected}">${B2b.escape(m.name)}</button>`).join(''));
                    }
                    $form.on('change', '[name="refund[category_payment_id]"]', renderSub);
                    $('#refund-sub').on('click', 'button', function () { $paymentId.val(this.dataset.method); renderSub(); });
                    renderSub();

                    $form.on('submit', function (event) {
                        event.preventDefault();
                        const category = methods.get(Number($form.find('[name="refund[category_payment_id]"]:checked').val()));
                        if (!category) return showToast('error', 'Pilih metode pengembalian dana.');
                        if (category.methods.length && !$paymentId.val()) return showToast('error', `Pilih jenis ${category.name}.`);
                        if (!$('#cancel-reason').val().trim()) { $('#cancel-reason').trigger('focus'); return showToast('error', 'Alasan pembatalan wajib diisi.'); }
                        B2b.submit(this, { onSuccess: () => window.location.reload() });
                    });
                });
            </script>
        @endpush
    @endif

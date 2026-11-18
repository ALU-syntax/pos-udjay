@extends('layouts.app')

@php
    $invoice = $order->invoice;
    $immediate = $order->payment_type === \App\Models\B2b\Order::PAYMENT_IMMEDIATE;
    $paid = $invoice && $invoice->paid_amount > 0;
    $rupiah = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
    $activityLabels = [
        'order.placed' => 'Pesanan dibuat',
        'order.completed' => 'Pesanan diselesaikan',
        'order.cancelled' => 'Pesanan dibatalkan',
        'payment.received' => 'Pembayaran diterima',
        'payment.reversed' => 'Pembayaran dibatalkan, dana dikembalikan',
    ];
@endphp

@section('content')
    @include('layouts.b2b.partials.assets')

    <div class="main-content ob-page">
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow"><a href="{{ route('b2b/orders') }}">Pesanan B2B</a> · {{ $order->outlet?->name }}</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-shopping-bag"></i></span>
                    {{ $order->order_number }}
                    <span class="badge badge-{{ $order->statusVariant() }} ms-2">{{ $order->statusLabel() }}</span>
                </h1>
                <p class="ob-header__desc">
                    {{ $order->order_date->translatedFormat('l, d M Y H:i') }} · Kasir {{ $order->cashier?->name ?? '-' }}
                </p>
            </div>
            <div class="b2b-actions">
                @if ($invoice)
                    <a href="{{ route('b2b/invoices/print', $invoice->id) }}" target="_blank" class="btn ob-btn ob-btn-outline"><i class="fas fa-print" aria-hidden="true"></i> Cetak Invoice</a>
                @endif
                @if ($order->status === 'confirmed')
                    @can('cancel b2b/orders')
                        @if ($paid)
                            {{-- Sudah dibayar: batal wajib mencatat pengembalian dana --}}
                            <button type="button" class="btn ob-btn ob-btn-danger" data-bs-toggle="modal" data-bs-target="#cancel-modal">
                                <i class="fas fa-ban" aria-hidden="true"></i> Batalkan</button>
                        @else
                            <a href="{{ route('b2b/orders/cancel', $order->id) }}" class="btn ob-btn ob-btn-danger" data-b2b-confirm data-reason="Alasan pembatalan"
                                data-title="Batalkan pesanan?" data-text="Stok dikembalikan dan invoice dibatalkan." data-confirm-text="Ya, batalkan">
                                <i class="fas fa-ban" aria-hidden="true"></i> Batalkan</a>
                        @endif
                    @endcan
                    @can('complete b2b/orders')
                        <a href="{{ route('b2b/orders/complete', $order->id) }}" class="btn ob-btn ob-btn-primary" data-b2b-confirm data-icon="question"
                            data-title="Tandai pesanan selesai?" data-text="Gunakan setelah barang diserahkan/diterima customer." data-confirm-text="Ya, selesai">
                            <i class="fas fa-check" aria-hidden="true"></i> Selesai</a>
                    @endcan
                @endif
            </div>
        </div>

        @if ($order->status === 'cancelled')
            <div class="ob-notice mb-3" role="note">
                <i class="fas fa-ban" aria-hidden="true"></i>
                <span>Dibatalkan {{ $order->cancelled_at?->translatedFormat('d M Y H:i') }}{{ $order->canceller ? ' oleh '.$order->canceller->name : '' }}: {{ $order->cancel_reason }}</span>
            </div>
        @endif
        @if ($order->items->contains('stock_insufficient', true))
            <div class="ob-notice mb-3" role="note">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <span>Sebagian item melebihi stok saat pesanan dibuat, sehingga stok varian menjadi minus. Periksa stok di outlet.</span>
            </div>
        @endif

        <div class="b2b-grid-2">
            <div class="ob-card">
                <h2 class="ob-section-title"><i class="fas fa-box" aria-hidden="true"></i> Item ({{ $order->items->count() }})</h2>
                <div class="table-responsive">
                    <table class="b2b-table">
                        <thead><tr><th>Produk</th><th class="text-end">Qty</th><th class="text-end">Harga</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $item->displayName() }}</div>
                                        @if ($item->b2b_contract_item_id)
                                            <div class="b2b-muted">Harga kontrak · normal Rp {{ number_format($item->base_price, 0, ',', '.') }}</div>
                                        @endif
                                        @if ($item->stock_insufficient)
                                            <div class="small text-warning fw-bold"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Stok kurang saat transaksi</div>
                                        @endif
                                        @if ($item->notes)
                                            <div class="b2b-muted"><i class="far fa-sticky-note" aria-hidden="true"></i> {{ $item->notes }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end">{{ number_format($item->qty, 0, ',', '.') }}</td>
                                    <td class="text-end b2b-money">Rp {{ number_format($item->final_price, 0, ',', '.') }}</td>
                                    <td class="text-end b2b-money fw-bold">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="ob-summary mt-3 mb-0">
                    <div class="ob-summary__row"><div>Subtotal</div><div>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</div></div>
                    <div class="ob-summary__row ob-summary__row--total"><div>Total</div><div>Rp {{ number_format($order->grand_total, 0, ',', '.') }}</div></div>
                </div>
            </div>

            <div>
                <div class="ob-card mb-3">
                    <h2 class="ob-section-title"><i class="fas fa-building" aria-hidden="true"></i> Customer</h2>
                    <dl class="ob-meta" style="grid-template-columns: 1fr;">
                        <div class="ob-meta__item">
                            <dt>Customer</dt>
                            <dd>
                                @can('read b2b/customers')
                                    <a href="{{ route('b2b/customers/show', $order->b2b_customer_id) }}">{{ $order->customer?->name }}</a>
                                @else
                                    {{ $order->customer?->name }}
                                @endcan
                                <div class="b2b-muted">{{ $order->customer?->code }}</div>
                            </dd>
                        </div>
                        <div class="ob-meta__item">
                            <dt>Pembayaran</dt>
                            <dd>
                                {{ $order->paymentTypeLabel() }}
                                @unless ($immediate)
                                    <div class="b2b-muted">Jatuh tempo {{ $order->due_date?->translatedFormat('d M Y') }}</div>
                                @endunless
                            </dd>
                        </div>
                        <div class="ob-meta__item"><dt>Harga</dt><dd>{{ $order->contract ? 'Kontrak '.$order->contract->contract_number : 'Harga normal' }}</dd></div>
                        <div class="ob-meta__item">
                            <dt>Pengiriman</dt>
                            <dd>
                                {{ $order->deliveryLabel() }}
                                @if ($order->shipping_address_snapshot)
                                    <div class="b2b-muted">{{ $order->shipping_address_snapshot['full_address'] ?? '' }}</div>
                                @endif
                            </dd>
                        </div>
                        <div class="ob-meta__item"><dt>Catatan</dt><dd>{{ $order->notes }}</dd></div>
                    </dl>
                </div>

                @if ($invoice)
                    <div class="ob-card mb-3">
                        <h2 class="ob-section-title"><i class="fas fa-file-invoice" aria-hidden="true"></i> Invoice</h2>
                        <dl class="ob-meta" style="grid-template-columns: 1fr 1fr;">
                            <div class="ob-meta__item" style="grid-column: 1 / -1;"><dt>Nomor</dt><dd>{{ $invoice->invoice_number }} <span class="badge badge-{{ $invoice->statusVariant() }} ms-1">{{ $invoice->statusLabel() }}</span></dd></div>
                            <div class="ob-meta__item"><dt>Tanggal</dt><dd>{{ $invoice->issue_date->translatedFormat('d M Y') }}</dd></div>
                            @if ($immediate)
                                <div class="ob-meta__item"><dt>Dibayar</dt><dd>{{ $rupiah($invoice->paid_amount) }}</dd></div>
                            @else
                                <div class="ob-meta__item"><dt>Jatuh tempo</dt><dd>{{ $invoice->due_date->translatedFormat('d M Y') }}</dd></div>
                            @endif
                            <div class="ob-meta__item"><dt>Total tagihan</dt><dd>{{ $rupiah($invoice->total_to_pay) }}</dd></div>
                            <div class="ob-meta__item"><dt>Sisa tagihan</dt><dd>{{ $rupiah($invoice->outstandingAmount()) }}</dd></div>
                        </dl>

                        <h3 class="b2b-subtitle">Pembayaran</h3>
                        @if ($payments->isEmpty())
                            <p class="b2b-muted mb-0">{{ $invoice->status === 'void' ? 'Tidak ada pembayaran.' : 'Belum ada pembayaran.' }}</p>
                        @else
                            <ul class="b2b-list b2b-payments">
                                @foreach ($payments as $payment)
                                    <li class="{{ $payment->status === 'reversed' ? 'is-reversed' : '' }}">
                                        <div class="b2b-payments__row">
                                            <span class="fw-bold">{{ $payment->method_name }}</span>
                                            <span class="b2b-money fw-bold">{{ $rupiah($payment->amount) }}</span>
                                        </div>
                                        <div class="b2b-muted">
                                            {{ $payment->payment_number }} · {{ $payment->paid_at->translatedFormat('d M Y H:i') }} · {{ $payment->receiver?->name ?? '-' }}
                                        </div>
                                        @if ($payment->reference)
                                            <div class="b2b-muted">Ref. {{ $payment->reference }}</div>
                                        @endif
                                        @if ($payment->status === 'reversed')
                                            <span class="badge badge-danger mt-1">Dibatalkan {{ $payment->reversed_at?->translatedFormat('d M Y H:i') }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if ($invoice->refunds->isNotEmpty())
                            <h3 class="b2b-subtitle">Pengembalian dana</h3>
                            <ul class="b2b-list b2b-payments">
                                @foreach ($invoice->refunds as $refund)
                                    <li>
                                        <div class="b2b-payments__row">
                                            <span class="fw-bold">{{ $refund->method_name }}</span>
                                            <span class="b2b-money fw-bold text-danger">− {{ $rupiah($refund->amount) }}</span>
                                        </div>
                                        <div class="b2b-muted">{{ $refund->refunded_at->translatedFormat('d M Y H:i') }} · {{ $refund->refunder?->name ?? '-' }}</div>
                                        @if ($refund->reference)
                                            <div class="b2b-muted">Ref. {{ $refund->reference }}</div>
                                        @endif
                                        <div class="b2b-muted">Alasan: {{ $refund->reason }}</div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif

                <div class="ob-card">
                    <h2 class="ob-section-title"><i class="fas fa-history" aria-hidden="true"></i> Riwayat</h2>
                    <ul class="b2b-list">
                        @forelse ($activities as $activity)
                            <li>
                                <div class="fw-bold">{{ $activityLabels[$activity->action] ?? $activity->action }}</div>
                                <div class="b2b-muted">{{ $activity->created_at?->translatedFormat('d M Y H:i') }} · {{ $activity->user?->name ?? 'Sistem' }}</div>
                                @if (str_starts_with($activity->action, 'payment.') && !empty($activity->changes['amount']))
                                    <div class="b2b-muted">
                                        {{ $rupiah($activity->changes['amount']) }} · {{ $activity->changes['method'] ?? $activity->changes['refund_method'] ?? '' }}
                                    </div>
                                @endif
                                @if (!empty($activity->changes['reason']))
                                    <div class="b2b-muted">Alasan: {{ $activity->changes['reason'] }}</div>
                                @endif
                            </li>
                        @empty
                            <li class="b2b-muted">Belum ada riwayat.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        @if ($order->status === 'confirmed' && $paid)
            @can('cancel b2b/orders')
                @php $firstPayment = $payments->firstWhere('status', 'posted'); @endphp
                {{-- Batal pesanan lunas: pembayaran di-reverse dan pengembalian dana dicatat. Dipindah ke <body> saat halaman dimuat. --}}
                <div class="modal fade b2b-modal" id="cancel-modal" tabindex="-1" aria-labelledby="cancel-title" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                        <form class="modal-content ob-page b2b-form" id="cancel-form" action="{{ route('b2b/orders/cancel', $order->id) }}" novalidate>
                            @csrf
                            <div class="modal-header">
                                <div>
                                    <h2 class="b2b-modal__title" id="cancel-title">Batalkan Pesanan Lunas</h2>
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
            @endcan
        @endif
    </div>
@endsection

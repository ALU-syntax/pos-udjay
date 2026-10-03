@extends('layouts.app')

@section('content')
    <div class="main-content order-table-page">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h2 class="h4 mb-1 font-weight-bold"><i class="fa fa-receipt me-2"></i>Order {{ $order->order_no }}</h2>
                <p class="text-muted mb-0">{{ $order->outlet?->name }} &middot; {{ $order->table?->name ?? $order->table?->code }} &middot; {{ $order->created_at?->format('d M Y H:i') }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('order-table/orders') }}" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-1"></i>Kembali</a>
                @can('serve order-table/orders')
                    <button type="button" class="btn btn-primary action-serve" {{ $nodeEnabled ? '' : 'disabled' }} title="{{ $nodeEnabled ? 'Tandai disajikan' : 'Integrasi Node belum aktif' }}"><i class="fa fa-check me-1"></i>Tandai Disajikan</button>
                @endcan
                @can('cancel order-table/orders')
                    <button type="button" class="btn btn-outline-danger action-cancel" {{ $nodeEnabled ? '' : 'disabled' }} title="{{ $nodeEnabled ? 'Batalkan order' : 'Integrasi Node belum aktif' }}"><i class="fa fa-times me-1"></i>Batalkan</button>
                @endcan
            </div>
        </div>

        @unless ($nodeEnabled)
            <div class="alert alert-warning border-0 shadow-sm"><i class="fa fa-plug me-2"></i>Aksi status dinonaktifkan sampai integrasi layanan Node diaktifkan. Semua perubahan status harus melalui layanan Node.</div>
        @endunless

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-white"><h5 class="mb-0">Item Pesanan</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead><tr><th>Produk</th><th>Varian</th><th class="text-end">Harga</th><th class="text-center">Qty</th><th class="text-end">Modifier</th><th class="text-end">Subtotal</th><th>Status</th><th>Catatan</th></tr></thead>
                                <tbody>
                                    @forelse ($order->items as $item)
                                        <tr>
                                            <td>{{ $item->product_name }}</td>
                                            <td>{{ $item->variant_name ?? '-' }}</td>
                                            <td class="text-end">Rp {{ number_format((int) $item->unit_price, 0, ',', '.') }}</td>
                                            <td class="text-center">{{ $item->qty }}</td>
                                            <td class="text-end">
                                                @if ($item->modifiers->isNotEmpty())
                                                    <ul class="list-unstyled mb-0 small">
                                                        @foreach ($item->modifiers as $modifier)
                                                            <li>{{ $modifier->name }} @if ((int) $modifier->harga > 0)(Rp {{ number_format((int) $modifier->harga, 0, ',', '.') }})@endif</li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-end">Rp {{ number_format((int) $item->line_total, 0, ',', '.') }}</td>
                                            <td><span class="badge bg-secondary">{{ $item->status }}</span></td>
                                            <td>{{ $item->notes ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="text-center text-muted">Tidak ada item.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-white"><h5 class="mb-0">Timeline Status</h5></div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            @forelse ($order->statusLogs->sortBy('created_at') as $log)
                                <li class="d-flex justify-content-between border-bottom py-2">
                                    <span>{{ $log->from_status ?? '-' }} &rarr; <strong>{{ $log->to_status }}</strong> <span class="text-muted">({{ $log->actor }})</span> @if ($log->note)<span class="text-muted">&middot; {{ $log->note }}</span>@endif</span>
                                    <span class="text-muted small">{{ $log->created_at?->format('d M Y H:i:s') }}</span>
                                </li>
                            @empty
                                <li class="text-muted">Belum ada log status.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white"><h5 class="mb-0">Pembayaran</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead><tr><th>Metode</th><th>Referensi</th><th class="text-end">Nominal</th><th>Status</th><th>Kedaluwarsa</th><th>Dibayar</th></tr></thead>
                                <tbody>
                                    @forelse ($order->payments as $payment)
                                        <tr>
                                            <td>{{ $payment->method }}</td>
                                            <td>{{ \App\Support\OrderTable\Mask::gatewayRef($payment->gateway_ref) }}</td>
                                            <td class="text-end">Rp {{ number_format((int) $payment->amount, 0, ',', '.') }}</td>
                                            <td><span class="badge bg-secondary">{{ $payment->status }}</span></td>
                                            <td>{{ $payment->expires_at?->format('d M Y H:i') ?? '-' }}</td>
                                            <td>{{ $payment->paid_at?->format('d M Y H:i') ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-muted">Belum ada data pembayaran.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-white"><h5 class="mb-0">Ringkasan</h5></div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-6">Status</dt><dd class="col-6 text-end">{{ $order->status }}</dd>
                            <dt class="col-6">Mode Bayar</dt><dd class="col-6 text-end">{{ $order->payment_mode }}</dd>
                            <dt class="col-6">Status Bayar</dt><dd class="col-6 text-end">{{ $order->payment_status }}</dd>
                            <dt class="col-6">Subtotal</dt><dd class="col-6 text-end">Rp {{ number_format((int) $order->subtotal, 0, ',', '.') }}</dd>
                            <dt class="col-6">Diskon Item</dt><dd class="col-6 text-end">Rp {{ number_format((int) $order->discount_item_total, 0, ',', '.') }}</dd>
                            <dt class="col-6">Voucher</dt><dd class="col-6 text-end">Rp {{ number_format((int) $order->voucher_discount, 0, ',', '.') }}</dd>
                            <dt class="col-6">Modifier</dt><dd class="col-6 text-end">Rp {{ number_format((int) $order->modifier_total, 0, ',', '.') }}</dd>
                            <dt class="col-6">Pajak</dt><dd class="col-6 text-end">Rp {{ number_format((int) $order->tax_total, 0, ',', '.') }}</dd>
                            <dt class="col-6">Pembulatan</dt><dd class="col-6 text-end">Rp {{ number_format((int) $order->rounding, 0, ',', '.') }}</dd>
                            <dt class="col-6 fw-bold">Grand Total</dt><dd class="col-6 text-end fw-bold">Rp {{ number_format((int) $order->grand_total, 0, ',', '.') }}</dd>
                        </dl>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-white"><h5 class="mb-0">Bridge POS</h5></div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-6">Status</dt><dd class="col-6 text-end">{{ $order->pos_bridge_status }}</dd>
                            <dt class="col-6">POS Trx ID</dt><dd class="col-6 text-end">{{ $order->pos_transaction_id ?? '-' }}</dd>
                        </dl>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white"><h5 class="mb-0">Audit Geofence</h5></div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-6">Flag</dt><dd class="col-6 text-end">{{ $order->geofence_flag }}</dd>
                            <dt class="col-6">Latitude</dt><dd class="col-6 text-end">{{ $order->lat ?? '-' }}</dd>
                            <dt class="col-6">Longitude</dt><dd class="col-6 text-end">{{ $order->lng ?? '-' }}</dd>
                            <dt class="col-6">Accuracy</dt><dd class="col-6 text-end">{{ $order->accuracy ?? '-' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('js')
        <script>
            $(function () {
                const serveUrl = @json(route('order-table/orders/serve', $order->id));
                const cancelUrl = @json(route('order-table/orders/cancel', $order->id));
                const token = @json(csrf_token());

                $('.action-serve').on('click', function () {
                    Swal.fire({ title: 'Tandai disajikan?', text: 'Permintaan diteruskan ke layanan order.', icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, tandai disajikan', cancelButtonText: 'Batal' })
                        .then(r => { if (!r.isConfirmed) return; $.post(serveUrl, { _token: token }).done(res => showToast(res.status || 'success', res.message)).fail(xhr => showToast('error', xhr.responseJSON?.message || 'Gagal mengirim permintaan')); });
                });

                $('.action-cancel').on('click', function () {
                    Swal.fire({ title: 'Batalkan order?', input: 'text', inputLabel: 'Alasan pembatalan', inputValidator: v => (!v ? 'Alasan wajib diisi' : undefined), icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, batalkan', cancelButtonText: 'Batal' })
                        .then(r => { if (!r.isConfirmed) return; $.post(cancelUrl, { _token: token, reason: r.value }).done(res => showToast(res.status || 'success', res.message)).fail(xhr => showToast('error', xhr.responseJSON?.message || 'Gagal mengirim permintaan')); });
                });
            });
        </script>
    @endpush
    @push('css')<style>.order-table-page .card{border-radius:10px}</style>@endpush
@endsection

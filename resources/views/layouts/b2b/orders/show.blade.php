@extends('layouts.app')

@php
    $invoice = $order->invoice;
    $activityLabels = [
        'order.placed' => 'Pesanan dibuat',
        'order.completed' => 'Pesanan diselesaikan',
        'order.cancelled' => 'Pesanan dibatalkan',
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
                        <a href="{{ route('b2b/orders/cancel', $order->id) }}" class="btn ob-btn ob-btn-danger" data-b2b-confirm data-reason="Alasan pembatalan"
                            data-title="Batalkan pesanan?" data-text="Stok dikembalikan dan invoice dibatalkan. Tidak bisa dibatalkan kalau invoice sudah dibayar." data-confirm-text="Ya, batalkan">
                            <i class="fas fa-ban" aria-hidden="true"></i> Batalkan</a>
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
                                        @if ($item->discount_per_unit > 0)
                                            <div class="b2b-muted">Normal Rp {{ number_format($item->base_price, 0, ',', '.') }} · diskon Rp {{ number_format($item->discount_per_unit, 0, ',', '.') }}/unit</div>
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
                    <div class="ob-summary__row"><div>Subtotal (harga normal)</div><div>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</div></div>
                    <div class="ob-summary__row"><div>Diskon kontrak</div><div>− Rp {{ number_format($order->discount_total, 0, ',', '.') }}</div></div>
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
                        <div class="ob-meta__item"><dt>Jatuh tempo</dt><dd>{{ $order->due_date?->translatedFormat('d M Y') }}</dd></div>
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
                            <div class="ob-meta__item"><dt>Jatuh tempo</dt><dd>{{ $invoice->due_date->translatedFormat('d M Y') }}</dd></div>
                            <div class="ob-meta__item"><dt>Total tagihan</dt><dd>Rp {{ number_format($invoice->total_to_pay, 0, ',', '.') }}</dd></div>
                            <div class="ob-meta__item"><dt>Sisa tagihan</dt><dd>Rp {{ number_format($invoice->outstandingAmount(), 0, ',', '.') }}</dd></div>
                        </dl>
                        <p class="b2b-muted mb-0">Pencatatan pembayaran menyusul setelah metode pembayaran B2B diputuskan.</p>
                    </div>
                @endif

                <div class="ob-card">
                    <h2 class="ob-section-title"><i class="fas fa-history" aria-hidden="true"></i> Riwayat</h2>
                    <ul class="b2b-list">
                        @forelse ($activities as $activity)
                            <li>
                                <div class="fw-bold">{{ $activityLabels[$activity->action] ?? $activity->action }}</div>
                                <div class="b2b-muted">{{ $activity->created_at?->translatedFormat('d M Y H:i') }} · {{ $activity->user?->name ?? 'Sistem' }}</div>
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
    </div>
@endsection

@extends('layouts.app')

@section('content')
    @include('layouts.reports.partials.ui-styles')

    @php
        $labels = App\Support\OrderTable\OrderLabels::class;
        $rupiah = fn ($amount) => 'Rp '.number_format((int) $amount, 0, ',', '.');
        $tableName = $order->table?->name ?? $order->table?->code;
    @endphp
    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">
                    <a href="{{ route('order-table/orders') }}" class="ob-back"><i class="fas fa-arrow-left" aria-hidden="true"></i> Monitor Order</a>
                </div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-receipt"></i></span>
                    {{ $order->order_no }}
                </h1>
                <p class="ob-header__desc">
                    @if ($order->atas_nama)
                        Atas nama <strong>{{ $order->atas_nama }}</strong> &middot;
                    @endif
                    {{ $order->outlet?->name ?? '-' }}
                    @if ($tableName)
                        &middot; {{ $tableName }}
                    @endif
                    &middot; {{ $order->created_at?->format('d M Y H:i') }}
                </p>
                <div class="ob-order-badges">
                    {!! $labels::badge($labels::ORDER, $order->status) !!}
                    {!! $labels::badge($labels::PAYMENT, $order->payment_status) !!}
                    {!! $labels::badge($labels::PAYMENT_MODE, $order->payment_mode) !!}
                </div>
            </div>
            <div class="ob-header__actions">
                @can('serve order-table/orders')
                    <button type="button" class="btn ob-btn ob-btn-primary action-serve" @disabled(! $nodeEnabled)
                        title="{{ $nodeEnabled ? 'Tandai disajikan' : 'Integrasi Node belum aktif' }}">
                        <i class="fas fa-check" aria-hidden="true"></i> Tandai Disajikan
                    </button>
                @endcan
                @can('cancel order-table/orders')
                    <button type="button" class="btn ob-btn ob-btn-danger action-cancel" @disabled(! $nodeEnabled)
                        title="{{ $nodeEnabled ? 'Batalkan order' : 'Integrasi Node belum aktif' }}">
                        <i class="fas fa-times" aria-hidden="true"></i> Batalkan
                    </button>
                @endcan
            </div>
        </div>

        @unless ($nodeEnabled)
            <div class="ob-notice" role="note">
                <i class="fas fa-plug" aria-hidden="true"></i>
                <span>Aksi status dinonaktifkan sampai integrasi layanan Node diaktifkan. Semua perubahan status harus melalui layanan Node.</span>
            </div>
        @endunless

        @if ($order->cancel_reason || $order->notes)
            <div class="ob-card ob-order-notes">
                @if ($order->cancel_reason)
                    <div class="ob-order-note ob-order-note--danger">
                        <i class="fas fa-times-circle" aria-hidden="true"></i>
                        <div><span class="ob-order-note__label">Alasan batal</span>{{ $order->cancel_reason }}</div>
                    </div>
                @endif
                @if ($order->notes)
                    <div class="ob-order-note">
                        <i class="fas fa-sticky-note" aria-hidden="true"></i>
                        <div><span class="ob-order-note__label">Catatan pelanggan</span>{{ $order->notes }}</div>
                    </div>
                @endif
            </div>
        @endif

        <div class="row g-3">
            <div class="col-lg-8">
                {{-- Item --}}
                <div class="ob-card">
                    <div class="ob-tabs-bar">
                        <h2 class="ob-section-title mb-0"><i class="fas fa-mug-hot" aria-hidden="true"></i> Item Pesanan</h2>
                        <span class="ob-count">{{ $order->items->count() }}</span>
                    </div>
                    <div class="ob-detail-scroll">
                        <table class="table ob-detail-table">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-end">Harga</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Subtotal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($order->items as $item)
                                    <tr>
                                        <td>
                                            <div class="ob-item-name">{{ $item->product_name }}</div>
                                            @if ($item->variant_name)
                                                <div class="ob-cell-meta">Varian: {{ $item->variant_name }}</div>
                                            @endif
                                            @foreach ($item->modifiers as $modifier)
                                                <div class="ob-cell-meta">+ {{ $modifier->name }}@if ((int) $modifier->harga > 0) ({{ $rupiah($modifier->harga) }})@endif</div>
                                            @endforeach
                                            @if ($item->notes)
                                                <div class="ob-item-note"><i class="far fa-comment-dots" aria-hidden="true"></i> {{ $item->notes }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end ob-num">{{ $rupiah($item->unit_price) }}</td>
                                        <td class="text-center ob-num">{{ $item->qty }}</td>
                                        <td class="text-end ob-num fw-bold">{{ $rupiah($item->line_total) }}</td>
                                        <td>{!! $labels::badge($labels::ITEM, $item->status) !!}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="ob-detail-empty">Tidak ada item.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Timeline --}}
                <div class="ob-card">
                    <div class="ob-tabs-bar">
                        <h2 class="ob-section-title mb-0"><i class="fas fa-stream" aria-hidden="true"></i> Timeline Status</h2>
                    </div>
                    <ol class="ob-timeline">
                        @forelse ($order->statusLogs->sortBy('created_at') as $log)
                            <li class="ob-timeline__item">
                                <div class="ob-timeline__body">
                                    <div>
                                        @if ($log->from_status)
                                            <span class="text-muted">{{ $labels::label($labels::ORDER, $log->from_status) }}</span>
                                            <i class="fas fa-long-arrow-alt-right text-muted mx-1" aria-label="menjadi"></i>
                                        @endif
                                        <strong>{{ $labels::label($labels::ORDER, $log->to_status) }}</strong>
                                        <span class="ob-cell-meta">oleh {{ $labels::label($labels::ACTOR, $log->actor) }}</span>
                                    </div>
                                    @if ($log->note)
                                        <div class="ob-cell-meta">{{ $log->note }}</div>
                                    @endif
                                </div>
                                <time class="ob-timeline__time" datetime="{{ $log->created_at?->toIso8601String() }}">{{ $log->created_at?->format('d M Y H:i:s') }}</time>
                            </li>
                        @empty
                            <li class="ob-detail-empty">Belum ada log status.</li>
                        @endforelse
                    </ol>
                </div>

                {{-- Pembayaran --}}
                <div class="ob-card">
                    <div class="ob-tabs-bar">
                        <h2 class="ob-section-title mb-0"><i class="fas fa-wallet" aria-hidden="true"></i> Pembayaran</h2>
                    </div>
                    <div class="ob-detail-scroll">
                        <table class="table ob-detail-table">
                            <thead>
                                <tr>
                                    <th>Metode</th>
                                    <th>Referensi</th>
                                    <th class="text-end">Nominal</th>
                                    <th>Status</th>
                                    <th>Kedaluwarsa</th>
                                    <th>Dibayar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($order->payments as $payment)
                                    <tr>
                                        <td>{{ $labels::label($labels::PAYMENT_MODE, $payment->method) }}</td>
                                        <td class="ob-mono">{{ \App\Support\OrderTable\Mask::gatewayRef($payment->gateway_ref) }}</td>
                                        <td class="text-end ob-num fw-bold">{{ $rupiah($payment->amount) }}</td>
                                        <td>{!! $labels::badge($labels::PAYMENT, $payment->status) !!}</td>
                                        <td class="ob-num">{{ $payment->expires_at?->format('d M Y H:i') ?? '-' }}</td>
                                        <td class="ob-num">{{ $payment->paid_at?->format('d M Y H:i') ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="ob-detail-empty">Belum ada data pembayaran.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                {{-- Ringkasan --}}
                <div class="ob-card">
                    <div class="ob-tabs-bar">
                        <h2 class="ob-section-title mb-0"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i> Ringkasan</h2>
                    </div>
                    <dl class="ob-summary">
                        <div><dt>Subtotal</dt><dd>{{ $rupiah($order->subtotal) }}</dd></div>
                        <div><dt>Modifier</dt><dd>{{ $rupiah($order->modifier_total) }}</dd></div>
                        <div><dt>Diskon item</dt><dd>{{ (int) $order->discount_item_total > 0 ? '− ' : '' }}{{ $rupiah($order->discount_item_total) }}</dd></div>
                        <div>
                            <dt>Voucher @if ($order->voucher)<span class="ob-cell-meta">({{ strtoupper($order->voucher->code) }})</span>@endif</dt>
                            <dd>{{ (int) $order->voucher_discount > 0 ? '− ' : '' }}{{ $rupiah($order->voucher_discount) }}</dd>
                        </div>
                        <div><dt>Pajak</dt><dd>{{ $rupiah($order->tax_total) }}</dd></div>
                        <div><dt>Pembulatan</dt><dd>{{ $rupiah($order->rounding) }}</dd></div>
                        <div class="ob-summary__total"><dt>Grand Total</dt><dd>{{ $rupiah($order->grand_total) }}</dd></div>
                    </dl>
                </div>

                {{-- Bridge --}}
                <div class="ob-card">
                    <div class="ob-tabs-bar">
                        <h2 class="ob-section-title mb-0"><i class="fas fa-exchange-alt" aria-hidden="true"></i> Bridge POS</h2>
                    </div>
                    <dl class="ob-summary">
                        <div><dt>Status</dt><dd>{!! $labels::badge($labels::BRIDGE, $order->pos_bridge_status) !!}</dd></div>
                        <div><dt>POS Trx ID</dt><dd class="ob-mono">{{ $order->pos_transaction_id ?? '-' }}</dd></div>
                    </dl>
                </div>

                {{-- Geofence --}}
                <div class="ob-card">
                    <div class="ob-tabs-bar">
                        <h2 class="ob-section-title mb-0"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> Audit Geofence</h2>
                    </div>
                    <dl class="ob-summary">
                        <div><dt>Lokasi</dt><dd>{!! $labels::badge($labels::GEOFENCE, $order->geofence_flag) !!}</dd></div>
                        <div><dt>Latitude</dt><dd class="ob-mono">{{ $order->lat ?? '-' }}</dd></div>
                        <div><dt>Longitude</dt><dd class="ob-mono">{{ $order->lng ?? '-' }}</dd></div>
                        <div><dt>Akurasi</dt><dd class="ob-mono">{{ $order->accuracy ?? '-' }}</dd></div>
                    </dl>
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
                    Swal.fire({ title: 'Batalkan order?', input: 'text', inputLabel: 'Alasan pembatalan', inputAttributes: { maxlength: 160 }, inputValidator: v => (!v || v.trim().length < 3 ? 'Alasan minimal 3 karakter' : undefined), icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, batalkan', cancelButtonText: 'Batal' })
                        .then(r => { if (!r.isConfirmed) return; $.post(cancelUrl, { _token: token, reason: r.value }).done(res => showToast(res.status || 'success', res.message)).fail(xhr => showToast('error', xhr.responseJSON?.message || 'Gagal mengirim permintaan')); });
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            .ob-back {
                display: inline-flex;
                align-items: center;
                gap: .4rem;
                color: inherit;
                text-decoration: none;
            }

            .ob-back:hover {
                color: var(--ob-primary);
            }

            .ob-order-badges {
                display: flex;
                flex-wrap: wrap;
                gap: .4rem;
                margin-top: .6rem;
            }

            .ob-header__actions {
                display: flex;
                flex-wrap: wrap;
                gap: .5rem;
            }

            .ob-header__actions .ob-btn:disabled {
                opacity: .55;
                cursor: not-allowed;
            }

            /* Catatan pelanggan & alasan batal */
            .ob-order-notes {
                display: flex;
                flex-direction: column;
                gap: .75rem;
                padding: 1rem 1.25rem;
            }

            .ob-order-note {
                display: flex;
                gap: .65rem;
                font-size: .875rem;
                color: var(--ob-text);
            }

            .ob-order-note > i {
                margin-top: .2rem;
                color: var(--ob-muted);
            }

            .ob-order-note--danger,
            .ob-order-note--danger > i {
                color: var(--ob-danger);
            }

            .ob-order-note__label {
                display: block;
                font-size: .72rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: .05em;
                color: var(--ob-muted);
            }

            /* Tabel detail (bukan DataTables) */
            .ob-detail-scroll {
                overflow-x: auto;
            }

            .ob-page .ob-detail-table {
                margin: 0;
                font-size: .875rem;
            }

            .ob-page .ob-detail-table thead th {
                padding: .7rem 1.25rem;
                border-bottom: 1px solid var(--ob-border);
                background: var(--ob-surface-2);
                font-size: .72rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: .05em;
                color: var(--ob-muted);
                white-space: nowrap;
            }

            .ob-page .ob-detail-table tbody td {
                padding: .85rem 1.25rem;
                border-bottom: 1px solid var(--ob-border);
                vertical-align: top;
                color: var(--ob-text);
            }

            .ob-page .ob-detail-table tbody tr:last-child td {
                border-bottom: 0;
            }

            .ob-detail-empty {
                padding: 1.25rem !important;
                text-align: center;
                color: var(--ob-muted) !important;
            }

            .ob-num {
                white-space: nowrap;
                font-variant-numeric: tabular-nums;
            }

            .ob-mono {
                font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                font-size: .8rem;
            }

            .ob-item-name {
                font-weight: 600;
            }

            .ob-item-note {
                margin-top: .3rem;
                font-size: .78rem;
                font-style: italic;
                color: var(--ob-text-2);
            }

            /* Timeline */
            .ob-timeline {
                margin: 0;
                padding: .5rem 1.25rem 1rem;
                list-style: none;
            }

            .ob-timeline__item {
                position: relative;
                display: flex;
                flex-wrap: wrap;
                justify-content: space-between;
                gap: .25rem 1rem;
                padding: .65rem 0 .65rem 1.4rem;
                font-size: .875rem;
            }

            .ob-timeline__item::before {
                content: "";
                position: absolute;
                left: 3px;
                top: 1.05rem;
                width: 9px;
                height: 9px;
                border-radius: 50%;
                background: var(--ob-primary);
                box-shadow: 0 0 0 3px var(--ob-primary-soft);
            }

            .ob-timeline__item:not(:last-child)::after {
                content: "";
                position: absolute;
                left: 7px;
                top: 1.7rem;
                bottom: -.4rem;
                width: 1px;
                background: var(--ob-border);
            }

            .ob-timeline__time {
                font-size: .78rem;
                color: var(--ob-muted);
                white-space: nowrap;
                font-variant-numeric: tabular-nums;
            }

            /* Ringkasan key-value */
            .ob-summary {
                margin: 0;
                padding: .5rem 1.25rem 1rem;
            }

            .ob-summary > div {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 1rem;
                padding: .5rem 0;
                font-size: .875rem;
            }

            .ob-summary dt {
                font-weight: 500;
                color: var(--ob-text-2);
            }

            .ob-summary dd {
                margin: 0;
                text-align: right;
                color: var(--ob-text);
                font-variant-numeric: tabular-nums;
            }

            .ob-summary__total {
                margin-top: .35rem;
                border-top: 1px dashed var(--ob-border-strong);
                padding-top: .85rem !important;
            }

            .ob-summary__total dt,
            .ob-summary__total dd {
                font-size: 1rem;
                font-weight: 700;
                color: var(--ob-text);
            }

            @media (max-width: 575.98px) {
                .ob-header__actions,
                .ob-header__actions .ob-btn {
                    width: 100%;
                    justify-content: center;
                }
            }
        </style>
    @endpush
@endsection

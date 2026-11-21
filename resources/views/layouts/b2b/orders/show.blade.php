@extends('layouts.app')

@php
    $invoice = $order->invoice;
    $customer = $order->customer;
    $immediate = $order->payment_type === \App\Models\B2b\Order::PAYMENT_IMMEDIATE;
    $cancelled = $order->status === \App\Models\B2b\Order::STATUS_CANCELLED;
    $paid = $invoice && $invoice->paid_amount > 0;
    $canCancel = $order->cancelPermission() && auth()->user()->can($order->cancelPermission());
    $rupiah = fn ($value) => 'Rp '.number_format((int) $value, 0, ',', '.');
    $outstanding = $invoice?->outstandingAmount() ?? 0;
    $overdue = $invoice && $invoice->displayStatus() === \App\Models\B2b\Invoice::STATUS_OVERDUE;
    $paidPercent = $invoice && $invoice->total_to_pay > 0 ? min(100, round($invoice->paid_amount / $invoice->total_to_pay * 100)) : 0;
    $refunded = $invoice ? (int) $invoice->refunds->sum('amount') : 0;
    $postedPayment = $payments->firstWhere('status', 'posted');
    $dueDays = $invoice ? today()->diffInDays($invoice->due_date, false) : null;
    $activityLabels = [
        'order.placed' => 'Pesanan dibuat',
        'order.completed' => 'Pesanan diselesaikan',
        'order.cancelled' => 'Pesanan dibatalkan',
        'payment.received' => 'Pembayaran diterima',
        'payment.reversed' => 'Pembayaran dibatalkan, dana dikembalikan',
    ];
    $activityStyles = [
        'order.placed' => ['primary', 'fa-shopping-bag'],
        'order.completed' => ['success', 'fa-check'],
        'order.cancelled' => ['danger', 'fa-ban'],
        'payment.received' => ['success', 'fa-hand-holding-usd'],
        'payment.reversed' => ['danger', 'fa-undo'],
    ];
@endphp

@section('content')
    @include('layouts.b2b.partials.assets')
    @include('layouts.b2b.partials.page-styles')

    <div class="main-content ob-page">
        <div class="ob-header">
            <div class="b2b-profile">
                <span class="b2b-doc-icon b2b-doc-icon--lg b2b-doc-icon--{{ $order->statusVariant() }}" aria-hidden="true"><i class="fas fa-shopping-bag"></i></span>
                <div>
                    <div class="ob-header__eyebrow">
                        <a href="{{ route('b2b/orders') }}">Pesanan B2B</a><span class="b2b-crumb-sep" aria-hidden="true">/</span>Detail
                    </div>
                    <h1 class="ob-header__title b2b-mono">{{ $order->order_number }}</h1>
                    <div class="b2b-profile__meta">
                        <span class="badge badge-{{ $order->statusVariant() }}">{{ $order->statusLabel() }}</span>
                        <span>{{ $order->order_date->translatedFormat('l, d M Y H:i') }}</span>
                        <span aria-hidden="true">·</span>
                        <span><i class="fas fa-store me-1 text-muted" aria-hidden="true"></i>{{ $order->outlet?->name }}</span>
                        <span aria-hidden="true">·</span>
                        <span>Kasir {{ $order->cashier?->name ?? '-' }}</span>
                    </div>
                </div>
            </div>
            <div class="b2b-actions">
                @if ($invoice)
                    <a href="{{ route('b2b/invoices/print', $invoice->id) }}" target="_blank" class="btn ob-btn ob-btn-outline"><i class="fas fa-print" aria-hidden="true"></i> Cetak Invoice</a>
                    <a href="{{ route('b2b/invoices/excel', $invoice->id) }}" class="btn ob-btn ob-btn-outline" title="Unduh invoice sebagai Excel"><i class="fas fa-file-excel" aria-hidden="true"></i> Excel</a>
                @endif
                {{-- Pesanan selesai tetap bisa dibatalkan untuk koreksi salah input, tapi butuh permission cancel-completed --}}
                @if ($canCancel)
                    @if ($paid)
                        {{-- Sudah dibayar: batal wajib mencatat pengembalian dana --}}
                        <button type="button" class="btn ob-btn ob-btn-outline b2b-btn-danger-outline" data-bs-toggle="modal" data-bs-target="#cancel-modal">
                            <i class="fas fa-ban" aria-hidden="true"></i> Batalkan</button>
                    @else
                        <a href="{{ route('b2b/orders/cancel', $order->id) }}" class="btn ob-btn ob-btn-outline b2b-btn-danger-outline" data-b2b-confirm data-reason="Alasan pembatalan"
                            data-title="Batalkan pesanan?" data-text="Stok dikembalikan dan invoice dibatalkan." data-confirm-text="Ya, batalkan">
                            <i class="fas fa-ban" aria-hidden="true"></i> Batalkan</a>
                    @endif
                @endif
                @if ($order->status === 'confirmed')
                    @can('complete b2b/orders')
                        <a href="{{ route('b2b/orders/complete', $order->id) }}" class="btn ob-btn ob-btn-primary" data-b2b-confirm data-icon="question"
                            data-title="Tandai pesanan selesai?" data-text="Gunakan setelah barang diserahkan/diterima customer." data-confirm-text="Ya, selesai">
                            <i class="fas fa-check" aria-hidden="true"></i> Tandai Selesai</a>
                    @endcan
                @endif
            </div>
        </div>

        @if ($cancelled)
            <div class="b2b-alert b2b-alert--danger" role="note">
                <i class="fas fa-ban" aria-hidden="true"></i>
                <div>
                    <strong>Pesanan dibatalkan {{ $order->cancelled_at?->translatedFormat('d M Y, H:i') }}{{ $order->canceller ? ' oleh '.$order->canceller->name : '' }}</strong>
                    Alasan: {{ $order->cancel_reason }}. Stok sudah dikembalikan dan invoice dibatalkan{{ $refunded ? ', dana '.$rupiah($refunded).' dicatat dikembalikan' : '' }}.
                </div>
            </div>
        @endif
        @if (! $cancelled && $order->items->contains('stock_insufficient', true))
            <div class="b2b-alert b2b-alert--warning" role="note">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <div>
                    <strong>Sebagian item melebihi stok saat pesanan dibuat</strong>
                    Stok varian menjadi minus. Periksa stok di outlet {{ $order->outlet?->name }}.
                </div>
            </div>
        @endif

        <div class="ob-stats">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-receipt"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Total pesanan</p>
                    <p class="ob-stat__value">{{ $rupiah($order->grand_total) }}</p>
                    <p class="b2b-stat__sub">{{ number_format($order->items->count(), 0, ',', '.') }} produk · {{ number_format($order->items->sum('qty'), 0, ',', '.') }} qty</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--success" aria-hidden="true"><i class="fas fa-wallet"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Sudah dibayar</p>
                    <p class="ob-stat__value">{{ $rupiah($invoice?->paid_amount) }}</p>
                    <p class="b2b-stat__sub">
                        @if ($refunded)
                            {{ $rupiah($refunded) }} dikembalikan
                        @elseif ($postedPayment)
                            {{ $payments->where('status', 'posted')->count() }} pembayaran · {{ $postedPayment->method_name }}
                        @else
                            Belum ada pembayaran
                        @endif
                    </p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--{{ $overdue ? 'danger' : 'warning' }}" aria-hidden="true"><i class="fas fa-file-invoice-dollar"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Sisa tagihan</p>
                    <p class="ob-stat__value {{ $overdue ? 'text-danger' : '' }}">{{ $rupiah($outstanding) }}</p>
                    <p class="b2b-stat__sub">{{ $invoice ? $invoice->statusLabel() : 'Tanpa invoice' }}</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--{{ $overdue ? 'danger' : 'primary' }}" aria-hidden="true"><i class="fas fa-calendar-day"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">{{ $immediate ? 'Pembayaran' : 'Jatuh tempo' }}</p>
                    @if ($cancelled)
                        <p class="ob-stat__value">—</p>
                        <p class="b2b-stat__sub">Pesanan dibatalkan</p>
                    @elseif ($immediate)
                        <p class="ob-stat__value">Bayar langsung</p>
                        <p class="b2b-stat__sub">Dibayar saat pesanan dibuat</p>
                    @else
                        <p class="ob-stat__value">{{ $order->due_date?->translatedFormat('d M Y') }}</p>
                        <p class="b2b-stat__sub {{ $overdue ? 'text-danger fw-semibold' : '' }}">
                            @if (!$invoice || $outstanding === 0)
                                Tempo
                            @elseif ($overdue)
                                Telat {{ abs($dueDays) }} hari
                            @elseif ($dueDays === 0)
                                Jatuh tempo hari ini
                            @else
                                {{ $dueDays }} hari lagi
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="b2b-order-grid">
            <div class="b2b-order-grid__items">
                <div class="ob-card">
                    <div class="b2b-card__head">
                        <h2 class="b2b-card__title"><i class="fas fa-box" aria-hidden="true"></i> Item Pesanan <span class="b2b-count">{{ $order->items->count() }}</span></h2>
                        <span class="b2b-card__desc m-0">
                            @if ($order->contract)
                                <i class="fas fa-file-signature me-1" aria-hidden="true"></i>Harga kontrak {{ $order->contract->contract_number }}
                            @else
                                Harga normal
                            @endif
                        </span>
                    </div>
                    <div class="table-responsive">
                        <table class="b2b-table b2b-table--card b2b-items">
                            <thead><tr><th>Produk</th><th class="text-end b2b-col-wide">Qty</th><th class="text-end b2b-col-wide">Harga</th><th class="text-end">Total</th></tr></thead>
                            <tbody>
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td>
                                            <div class="b2b-items__name">{{ $item->displayName() }}</div>
                                            <div class="b2b-items__unit">{{ number_format($item->qty, 0, ',', '.') }} × {{ $rupiah($item->final_price) }}</div>
                                            <div class="b2b-items__meta">
                                                @if ($item->b2b_contract_item_id)
                                                    <span class="b2b-tag b2b-tag--primary">Harga kontrak</span>
                                                    @if ($item->base_price != $item->final_price)
                                                        <span>Normal <s>{{ $rupiah($item->base_price) }}</s></span>
                                                    @endif
                                                @endif
                                                @if ($item->stock_insufficient)
                                                    <span class="b2b-tag b2b-tag--warning"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Stok kurang saat transaksi</span>
                                                @endif
                                            </div>
                                            @if ($item->notes)
                                                <div class="b2b-items__note"><i class="far fa-sticky-note" aria-hidden="true"></i> {{ $item->notes }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end b2b-money b2b-col-wide">{{ number_format($item->qty, 0, ',', '.') }}</td>
                                        <td class="text-end b2b-money b2b-col-wide">{{ $rupiah($item->final_price) }}</td>
                                        <td class="text-end b2b-money fw-bold">{{ $rupiah($item->line_total) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="b2b-totals">
                        <div class="b2b-totals__row"><span>Subtotal</span><span>{{ $rupiah($order->subtotal) }}</span></div>
                        @if ($order->shipping_cost)
                            <div class="b2b-totals__row"><span>Ongkos kirim</span><span>{{ $rupiah($order->shipping_cost) }}</span></div>
                        @endif
                        <div class="b2b-totals__row b2b-totals__row--grand"><span>Total</span><span>{{ $rupiah($order->grand_total) }}</span></div>
                    </div>
                </div>
            </div>

            <div class="b2b-order-grid__history">
                <div class="ob-card">
                    <div class="b2b-card__head">
                        <h2 class="b2b-card__title"><i class="fas fa-history" aria-hidden="true"></i> Riwayat</h2>
                    </div>
                    <div class="b2b-card__body">
                        @if ($activities->isEmpty())
                            <p class="b2b-muted mb-0">Belum ada riwayat.</p>
                        @else
                            <ol class="b2b-timeline">
                                @foreach ($activities as $activity)
                                    @php [$tone, $icon] = $activityStyles[$activity->action] ?? ['muted', 'fa-circle']; @endphp
                                    <li class="b2b-timeline__item">
                                        <span class="b2b-timeline__dot b2b-timeline__dot--{{ $tone }}" aria-hidden="true"><i class="fas {{ $icon }}"></i></span>
                                        <div class="b2b-timeline__body">
                                            <div class="b2b-timeline__title">
                                                @if ($activity->action === 'payment.reversed' && empty($activity->changes['refund_method']))
                                                    Pembayaran dibatalkan (salah catat)
                                                @else
                                                    {{ $activityLabels[$activity->action] ?? $activity->action }}
                                                @endif
                                            </div>
                                            <div class="b2b-timeline__meta">{{ $activity->created_at?->translatedFormat('d M Y, H:i') }} · {{ $activity->user?->name ?? 'Sistem' }}</div>
                                            @if (str_starts_with($activity->action, 'payment.') && !empty($activity->changes['amount']))
                                                <div class="b2b-timeline__text">{{ $rupiah($activity->changes['amount']) }} · {{ $activity->changes['method'] ?? $activity->changes['refund_method'] ?? '' }}</div>
                                            @endif
                                            @if (!empty($activity->changes['reason']))
                                                <div class="b2b-timeline__text">Alasan: {{ $activity->changes['reason'] }}</div>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                </div>
            </div>

            <div class="b2b-order-grid__aside">
                @if ($invoice)
                    <div class="ob-card">
                        <div class="b2b-card__head b2b-card__head--nowrap">
                            <div>
                                <h2 class="b2b-card__title"><i class="fas fa-file-invoice" aria-hidden="true"></i> Invoice &amp; Pembayaran</h2>
                                <p class="b2b-card__desc b2b-mono">{{ $invoice->invoice_number }} · {{ $invoice->issue_date->translatedFormat('d M Y') }}</p>
                            </div>
                            <span class="badge badge-{{ $invoice->statusVariant() }}">{{ $invoice->statusLabel() }}</span>
                        </div>
                        <div class="b2b-card__body">
                            <div class="b2b-paybox">
                                <div class="b2b-paybox__row"><span>Total tagihan</span><strong>{{ $rupiah($invoice->total_to_pay) }}</strong></div>
                                <div class="b2b-paybox__bar {{ $invoice->status === 'void' ? 'is-void' : '' }}" role="progressbar" aria-label="Persentase terbayar"
                                    aria-valuenow="{{ $paidPercent }}" aria-valuemin="0" aria-valuemax="100"><span style="width: {{ $paidPercent }}%"></span></div>
                                <div class="b2b-paybox__split">
                                    <div><span>Dibayar</span><strong class="text-success">{{ $rupiah($invoice->paid_amount) }}</strong></div>
                                    <div class="text-end"><span>Sisa tagihan</span><strong class="{{ $overdue ? 'text-danger' : '' }}">{{ $rupiah($outstanding) }}</strong></div>
                                </div>
                                @unless ($immediate)
                                    <div class="b2b-paybox__due {{ $overdue ? 'is-overdue' : '' }}">
                                        <i class="far fa-calendar-alt" aria-hidden="true"></i>
                                        Jatuh tempo {{ $invoice->due_date->translatedFormat('d M Y') }}{{ $overdue ? ' · telat '.abs($dueDays).' hari' : '' }}
                                    </div>
                                @endunless
                            </div>

                            @if ($invoice->canRecordPayment())
                                @can('pay b2b/invoices')
                                    <button type="button" class="btn ob-btn ob-btn-primary w-100 mt-3"
                                        data-pay-invoice data-url="{{ route('b2b/invoices/pay', $invoice->id) }}" data-number="{{ $invoice->invoice_number }}"
                                        data-customer="{{ $customer?->name }}" data-total="{{ $invoice->total_to_pay }}" data-paid="{{ $invoice->paid_amount }}"
                                        data-outstanding="{{ $outstanding }}" data-issue="{{ $invoice->issue_date->toDateString() }}" data-outlet="{{ $order->outlet_id }}">
                                        <i class="fas fa-hand-holding-usd" aria-hidden="true"></i> Catat pelunasan</button>
                                @endcan
                            @endif
                            @if ($invoice->canSendReminder())
                                @can('send b2b/reminders')
                                    <button type="button" class="btn ob-btn ob-btn-outline w-100 mt-2" {!! \App\Support\B2b\ReminderButton::attributes($invoice) !!}>
                                        <i class="fas fa-bell" aria-hidden="true"></i> Kirim reminder</button>
                                @endcan
                            @endif

                            <h3 class="b2b-subtitle mt-4">Pembayaran</h3>
                            @if ($payments->isEmpty())
                                <div class="b2b-empty b2b-empty--sm">
                                    <span class="b2b-empty__icon" aria-hidden="true"><i class="fas fa-wallet"></i></span>
                                    <p class="b2b-empty__text">{{ $invoice->status === 'void' ? 'Tidak ada pembayaran.' : 'Belum ada pembayaran.' }}</p>
                                </div>
                            @else
                                <ul class="b2b-list b2b-payments">
                                    @foreach ($payments as $payment)
                                        <li class="{{ $payment->status === 'reversed' ? 'is-reversed' : '' }}">
                                            <div class="b2b-payments__row">
                                                <span class="fw-bold">{{ $payment->method_name }}</span>
                                                <span class="b2b-money fw-bold">{{ $rupiah($payment->amount) }}</span>
                                            </div>
                                            <div class="b2b-muted">
                                                <span class="b2b-mono">{{ $payment->payment_number }}</span> · {{ $payment->paid_at->translatedFormat('d M Y H:i') }} · {{ $payment->receiver?->name ?? '-' }}
                                            </div>
                                            @if ($payment->outlet_id === null)
                                                <div class="b2b-muted">Diterima di rekening pusat</div>
                                            @endif
                                            @if ($payment->reference)
                                                <div class="b2b-muted">Ref. {{ $payment->reference }}</div>
                                            @endif
                                            @if ($payment->notes)
                                                <div class="b2b-muted"><i class="far fa-sticky-note" aria-hidden="true"></i> {{ $payment->notes }}</div>
                                            @endif
                                            @if ($payment->status === 'reversed')
                                                <span class="badge badge-danger mt-1">Dibatalkan {{ $payment->reversed_at?->translatedFormat('d M Y H:i') }}</span>
                                                @if ($payment->reverse_reason && $invoice->refunds->where('b2b_payment_id', $payment->id)->isEmpty())
                                                    <div class="b2b-muted">Alasan: {{ $payment->reverse_reason }}</div>
                                                @endif
                                            @endif
                                            <div class="b2b-payments__actions">
                                                @if ($payment->attachment_path)
                                                    <a href="{{ route('b2b/invoices/payments/attachment', $payment->id) }}" target="_blank"><i class="fas fa-paperclip" aria-hidden="true"></i> Bukti</a>
                                                @endif
                                                @if ($payment->status === 'posted' && ! $immediate && ! $cancelled)
                                                    @can('pay b2b/invoices')
                                                        <a href="{{ route('b2b/invoices/payments/reverse', $payment->id) }}" class="text-danger" data-b2b-confirm data-reason="Alasan pembatalan"
                                                            data-title="Batalkan pembayaran {{ $payment->payment_number }}?" data-text="Untuk pembayaran yang salah catat. Sisa tagihan invoice akan bertambah lagi; tidak ada pengembalian dana yang dicatat."
                                                            data-confirm-text="Ya, batalkan"><i class="fas fa-undo" aria-hidden="true"></i> Batalkan</a>
                                                    @endcan
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            @if ($reminders->isNotEmpty() || ($invoice->canSendReminder() && auth()->user()->can('send b2b/reminders')))
                                <h3 class="b2b-subtitle mt-3">Riwayat reminder</h3>
                                @if ($reminders->isEmpty())
                                    <p class="b2b-muted mb-3">Belum ada reminder untuk invoice ini.</p>
                                @else
                                    <ul class="b2b-list b2b-payments">
                                        @foreach ($reminders as $reminder)
                                            <li>
                                                <div class="b2b-payments__row">
                                                    <span class="fw-bold"><i class="{{ $reminder->channelIcon() }} me-1" aria-hidden="true"></i>{{ \App\Models\B2b\ReminderLog::offsetLabel((int) $reminder->pivot->offset_days) }}</span>
                                                    <span class="badge badge-{{ $reminder->statusVariant() }}">{{ $reminder->statusLabel() }}</span>
                                                </div>
                                                <div class="b2b-muted">
                                                    {{ ($reminder->sent_at ?? $reminder->created_at)->translatedFormat('d M Y H:i') }} ·
                                                    {{ $reminder->trigger === 'manual' ? 'Manual'.($reminder->sender ? ' oleh '.$reminder->sender->name : '') : 'Otomatis' }}
                                                </div>
                                                @if ($reminder->recipient)
                                                    <div class="b2b-muted">{{ $reminder->recipient }}</div>
                                                @endif
                                                @if ($reminder->error && $reminder->status !== 'sent')
                                                    <div class="b2b-muted text-danger">{{ \Illuminate\Support\Str::limit($reminder->error, 120) }}</div>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                    @can('read b2b/reminders')
                                        <p class="b2b-muted mb-3"><a href="{{ route('b2b/reminders') }}">Lihat semua aktivitas reminder</a></p>
                                    @endcan
                                @endif
                            @endif

                            @if ($invoice->refunds->isNotEmpty())
                                <h3 class="b2b-subtitle">Pengembalian dana</h3>
                                <ul class="b2b-list b2b-payments">
                                    @foreach ($invoice->refunds as $refund)
                                        <li class="is-refund">
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
                    </div>
                @endif

                <div class="ob-card">
                    <div class="b2b-card__head">
                        <h2 class="b2b-card__title"><i class="fas fa-info-circle" aria-hidden="true"></i> Detail Pesanan</h2>
                    </div>
                    <div class="b2b-card__body">
                        <div class="b2b-customer">
                            <span class="b2b-avatar b2b-avatar--{{ $customer?->type }}" aria-hidden="true">{{ $customer?->initials() }}</span>
                            <div class="ob-cell-stack">
                                @can('read b2b/customers')
                                    <a href="{{ route('b2b/customers/show', $order->b2b_customer_id) }}" class="b2b-cust-cell__name">{{ $customer?->name }}</a>
                                @else
                                    <span class="b2b-cust-cell__name">{{ $customer?->name }}</span>
                                @endcan
                                <span class="ob-cell-meta">{{ $customer?->code }}{{ $customer?->contact_name ? ' · '.$customer->contact_name : '' }}</span>
                            </div>
                        </div>
                        <dl class="b2b-dl">
                            <div class="b2b-dl__row"><dt>Pembayaran</dt><dd>{{ $order->paymentTypeLabel() }}</dd></div>
                            <div class="b2b-dl__row">
                                <dt>Harga</dt>
                                <dd>
                                    @if ($order->contract)
                                        @can('read b2b/contracts')
                                            <a href="{{ route('b2b/contracts/show', $order->b2b_contract_id) }}" class="b2b-mono">{{ $order->contract->contract_number }}</a>
                                        @else
                                            <span class="b2b-mono">{{ $order->contract->contract_number }}</span>
                                        @endcan
                                    @else
                                        Harga normal
                                    @endif
                                </dd>
                            </div>
                            <div class="b2b-dl__row">
                                <dt>Pengiriman</dt>
                                <dd>
                                    {{ $order->deliveryLabel() }}
                                    @if ($order->shipping_address_snapshot)
                                        <div class="ob-cell-meta mt-1">{{ $order->shipping_address_snapshot['full_address'] ?? '' }}</div>
                                    @endif
                                </dd>
                            </div>
                            <div class="b2b-dl__row"><dt>Catatan</dt><dd>{{ $order->notes }}</dd></div>
                        </dl>
                    </div>
                    <div class="b2b-card__foot">
                        Dibuat {{ $order->order_date->translatedFormat('d M Y, H:i') }}{{ $order->cashier ? ' oleh '.$order->cashier->name : '' }}
                        @if ($order->completed_at)
                            <br>Selesai {{ $order->completed_at->translatedFormat('d M Y, H:i') }}
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @include('layouts.b2b.invoices.partials.payment-modal')
        @include('layouts.b2b.reminders.partials.send-dialog')

        @if ($canCancel && $paid)
            @include('layouts.b2b.orders.partials.cancel-modal')
        @endif
    </div>

    @push('css')
        <style>
            .ob-page .ob-stat__value { font-size: 1.15rem; }
            .b2b-btn-danger-outline { color: var(--ob-danger) !important; }
            .b2b-btn-danger-outline:hover { border-color: var(--ob-danger) !important; background: var(--ob-danger-soft) !important; }

            /* Tata letak: item & riwayat di kiri, invoice + detail di kanan. Di layar kecil invoice tampil sebelum riwayat. */
            .b2b-order-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); grid-template-areas: "items aside" "history aside"; grid-template-rows: auto 1fr; column-gap: 1.25rem; align-items: start; }
            .b2b-order-grid__items { grid-area: items; }
            .b2b-order-grid__history { grid-area: history; }
            .b2b-order-grid__aside { grid-area: aside; }
            @media (max-width: 991.98px) {
                .b2b-order-grid { grid-template-columns: minmax(0, 1fr); grid-template-areas: "items" "aside" "history"; }
            }
            .b2b-card__head--nowrap { flex-wrap: nowrap; align-items: flex-start; }
            .b2b-card__head--nowrap > div { min-width: 0; }
            .b2b-card__head--nowrap > .badge { flex-shrink: 0; margin-top: .1rem; }

            /* Peringatan di bawah kepala halaman */
            .b2b-alert { display: flex; gap: .75rem; margin-bottom: 1.25rem; padding: 1rem 1.25rem; border: 1px solid; border-radius: var(--ob-radius); font-size: .85rem; line-height: 1.5; }
            .b2b-alert > i { margin-top: .2rem; }
            .b2b-alert strong { display: block; }
            .b2b-alert--danger { border-color: #fecaca; background: var(--ob-danger-soft); color: #991b1b; }
            .b2b-alert--danger strong { color: #7f1d1d; }
            .b2b-alert--warning { border-color: #fde68a; background: #fffbeb; color: #92400e; }
            .b2b-alert--warning strong { color: #78350f; }

            /* Tabel item */
            .b2b-items__name { font-weight: 600; color: var(--ob-text); }
            .b2b-items__meta { display: flex; flex-wrap: wrap; align-items: center; gap: .3rem .6rem; margin-top: .3rem; font-size: .75rem; color: var(--ob-muted); }
            .b2b-items__meta:empty { display: none; }
            .b2b-items__note { margin-top: .3rem; font-size: .78rem; color: var(--ob-text-2); }
            .b2b-items td { vertical-align: top !important; }
            .b2b-items__unit { display: none; margin-top: .15rem; font-size: .78rem; color: var(--ob-text-2); font-variant-numeric: tabular-nums; }
            @media (max-width: 575.98px) {
                .b2b-items .b2b-col-wide { display: none; }
                .b2b-items__unit { display: block; }
                .b2b-table--card.b2b-items th, .b2b-table--card.b2b-items td { padding-left: 1rem; padding-right: 1rem; }
            }
            .b2b-tag--warning { border-color: transparent; background: #fef3c7; color: #b45309; }
            .b2b-totals { padding: 1rem 1.25rem; border-top: 1px solid var(--ob-border); background: var(--ob-surface-2); border-radius: 0 0 var(--ob-radius) var(--ob-radius); }
            .b2b-totals__row { display: flex; justify-content: space-between; gap: 1rem; padding: .2rem 0; font-size: .875rem; color: var(--ob-text-2); font-variant-numeric: tabular-nums; }
            .b2b-totals__row--grand { margin-top: .35rem; padding-top: .6rem; border-top: 1px dashed var(--ob-border-strong); font-size: 1.05rem; font-weight: 700; color: var(--ob-text); }

            /* Ringkasan invoice */
            .b2b-paybox { padding: .95rem 1rem; border: 1px solid var(--ob-border); border-radius: 12px; background: var(--ob-surface-2); }
            .b2b-paybox__row { display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; font-size: .82rem; color: var(--ob-muted); }
            .b2b-paybox__row strong { font-size: 1.15rem; color: var(--ob-text); font-variant-numeric: tabular-nums; }
            .b2b-paybox__bar { height: 8px; margin: .7rem 0 .6rem; overflow: hidden; border-radius: 999px; background: var(--ob-border); }
            .b2b-paybox__bar > span { display: block; height: 100%; border-radius: inherit; background: var(--ob-success); }
            .b2b-paybox__bar.is-void > span { background: var(--ob-border-strong); }
            .b2b-paybox__split { display: flex; justify-content: space-between; gap: 1rem; }
            .b2b-paybox__split span { display: block; font-size: .72rem; font-weight: 600; color: var(--ob-muted); }
            .b2b-paybox__split strong { font-size: .95rem; font-variant-numeric: tabular-nums; }
            .b2b-paybox__due { margin-top: .75rem; padding-top: .65rem; border-top: 1px dashed var(--ob-border-strong); font-size: .8rem; color: var(--ob-text-2); }
            .b2b-paybox__due i { margin-right: .3rem; color: var(--ob-muted); }
            .b2b-paybox__due.is-overdue { font-weight: 600; color: var(--ob-danger); }
            .b2b-paybox__due.is-overdue i { color: var(--ob-danger); }
            .b2b-payments li.is-refund { border-color: #fecaca; background: #fffafa; }
            .b2b-empty--sm { padding: 1rem; border: 1px dashed var(--ob-border); border-radius: 12px; }
            .b2b-empty--sm .b2b-empty__icon { width: 36px; height: 36px; margin-bottom: .25rem; border-radius: 10px; }

            /* Detail pesanan */
            .b2b-customer { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--ob-border); }
            .b2b-customer + .b2b-dl .b2b-dl__row:first-child { padding-top: 0; }

            /* Riwayat */
            .b2b-timeline { position: relative; list-style: none; margin: 0; padding: 0; }
            .b2b-timeline__item { position: relative; display: flex; gap: .85rem; padding-bottom: 1.1rem; }
            .b2b-timeline__item:last-child { padding-bottom: 0; }
            .b2b-timeline__item:not(:last-child)::before { content: ""; position: absolute; top: 34px; bottom: 4px; left: 15px; width: 2px; border-radius: 2px; background: var(--ob-border); }
            .b2b-timeline__dot { display: inline-grid; place-items: center; flex-shrink: 0; width: 32px; height: 32px; border-radius: 10px; background: var(--ob-surface-2); color: var(--ob-muted); font-size: .75rem; }
            .b2b-timeline__dot--primary { background: var(--ob-primary-soft); color: var(--ob-primary); }
            .b2b-timeline__dot--success { background: var(--ob-success-soft); color: var(--ob-success); }
            .b2b-timeline__dot--danger { background: var(--ob-danger-soft); color: var(--ob-danger); }
            .b2b-timeline__body { min-width: 0; padding-top: .3rem; }
            .b2b-timeline__title { font-size: .875rem; font-weight: 600; color: var(--ob-text); }
            .b2b-timeline__meta { margin-top: .1rem; font-size: .76rem; color: var(--ob-muted); }
            .b2b-timeline__text { margin-top: .2rem; font-size: .8rem; color: var(--ob-text-2); }
        </style>
    @endpush
@endsection

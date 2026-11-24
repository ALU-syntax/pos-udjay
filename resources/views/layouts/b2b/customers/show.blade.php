@extends('layouts.app')

@section('content')
    @include('layouts.b2b.partials.assets')
    @include('layouts.b2b.partials.page-styles')
    @push('css')
        <style>
            /* Kartu Rekening Pengirim */
            .b2b-accounts { list-style: none; margin: 0; padding: 0; }
            .b2b-account { display: flex; gap: .75rem; padding: .85rem 1.25rem; border-top: 1px solid var(--ob-border); }
            .b2b-account:first-child { border-top: 0; }
            .b2b-account__icon { flex: none; display: inline-grid; place-items: center; width: 36px; height: 36px; border-radius: 10px; font-size: .9rem; }
            .b2b-account__icon--bank { background: var(--ob-primary-soft); color: var(--ob-primary); }
            .b2b-account__icon--ewallet { background: #ecfdf5; color: #047857; }
            .b2b-account__body { display: flex; flex-direction: column; gap: .1rem; min-width: 0; font-size: .82rem; }
            .b2b-account__top { display: flex; align-items: center; gap: .4rem; }
            .b2b-account__top strong { font-size: .875rem; color: var(--ob-text); }
            .b2b-account__number { font-size: .92rem; font-weight: 600; letter-spacing: .02em; color: var(--ob-text); }
            .b2b-account__name { color: var(--ob-text-2); overflow-wrap: anywhere; }
        </style>
    @endpush

    <div class="main-content ob-page">
        <div class="ob-header">
            <div class="b2b-profile">
                <span class="b2b-avatar b2b-avatar--lg b2b-avatar--{{ $customer->type }} {{ $customer->is_active ? '' : 'is-inactive' }}" aria-hidden="true">{{ $customer->initials() }}</span>
                <div>
                    <div class="ob-header__eyebrow">
                        <a href="{{ route('b2b/customers') }}">Customer B2B</a><span class="b2b-crumb-sep" aria-hidden="true">/</span>{{ $customer->code }}
                    </div>
                    <h1 class="ob-header__title">{{ $customer->name }}</h1>
                    <div class="b2b-profile__meta">
                        <span class="badge badge-primary">{{ $customer->typeLabel() }}</span>
                        <span class="badge {{ $customer->is_active ? 'badge-success' : 'badge-muted' }}">{{ $customer->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        @if ($customer->registeredViaPortal())
                            <span class="badge badge-info" title="Mendaftar sendiri lewat form pendaftaran B2B pada {{ $customer->created_at?->translatedFormat('d M Y H:i') }}">Daftar via form</span>
                        @endif
                        <span>
                            @if ($effectiveContract)
                                Kontrak <a href="{{ route('b2b/contracts/show', $effectiveContract->id) }}">{{ $effectiveContract->contract_number }}</a>
                                berlaku sampai {{ $effectiveContract->end_date->translatedFormat('d M Y') }}
                            @else
                                Tanpa kontrak berlaku · harga normal
                            @endif
                        </span>
                    </div>
                </div>
            </div>
            <div class="b2b-actions">
                @can('read b2b/invoices')
                    <a href="{{ route('b2b/invoices/statement', $customer->id) }}" target="_blank" class="btn ob-btn ob-btn-outline"
                        title="Rekap semua invoice belum lunas untuk dikirim ke customer"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i> Rekap Tagihan</a>
                @endcan
                @can('create b2b/contracts')
                    <a href="{{ route('b2b/contracts/create', ['customer_id' => $customer->id]) }}" class="btn ob-btn ob-btn-outline"><i class="fas fa-file-signature" aria-hidden="true"></i> Buat Kontrak</a>
                @endcan
                @can('update b2b/customers')
                    <a href="{{ route('b2b/customers/edit', $customer->id) }}" class="btn ob-btn ob-btn-primary"><i class="fas fa-pen" aria-hidden="true"></i> Edit</a>
                @endcan
            </div>
        </div>

        <div class="ob-stats">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-shopping-bag"></i></span>
                <div><p class="ob-stat__label">Pesanan</p><p class="ob-stat__value">{{ number_format($stats['orders'], 0, ',', '.') }}</p></div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-file-invoice"></i></span>
                <div><p class="ob-stat__label">Invoice belum lunas</p><p class="ob-stat__value">{{ number_format($stats['open_invoices'], 0, ',', '.') }}</p></div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--success" aria-hidden="true"><i class="fas fa-wallet"></i></span>
                <div><p class="ob-stat__label">Sisa piutang</p><p class="ob-stat__value">Rp {{ number_format($stats['outstanding'], 0, ',', '.') }}</p></div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--danger" aria-hidden="true"><i class="fas fa-exclamation-triangle"></i></span>
                <div><p class="ob-stat__label">Lewat jatuh tempo</p><p class="ob-stat__value">{{ number_format($stats['overdue'], 0, ',', '.') }}</p></div>
            </div>
        </div>

        <div class="b2b-grid-2">
            <div>
                <div class="ob-card">
                    <div class="b2b-card__head">
                        <h2 class="b2b-card__title"><i class="fas fa-file-signature" aria-hidden="true"></i> Kontrak <span class="b2b-count">{{ $contracts->count() }}</span></h2>
                    </div>
                    @if ($contracts->isEmpty())
                        <div class="b2b-empty">
                            <span class="b2b-empty__icon" aria-hidden="true"><i class="fas fa-file-signature"></i></span>
                            <p class="b2b-empty__title">Belum ada kontrak</p>
                            <p class="b2b-empty__text">Customer tetap bisa belanja dengan harga normal.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="b2b-table b2b-table--card">
                                <thead><tr><th>No. Kontrak</th><th>Periode</th><th>Harga</th><th>Status</th></tr></thead>
                                <tbody>
                                    @foreach ($contracts as $contract)
                                        <tr>
                                            <td><a href="{{ route('b2b/contracts/show', $contract->id) }}">{{ $contract->contract_number }}</a></td>
                                            <td class="text-nowrap">{{ $contract->start_date->translatedFormat('d M Y') }} – {{ $contract->end_date->translatedFormat('d M Y') }}</td>
                                            <td>{{ $contract->items_count }} harga</td>
                                            <td><span class="badge badge-{{ $contract->statusVariant() }}">{{ $contract->statusLabel() }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="ob-card">
                    <div class="b2b-card__head">
                        <div>
                            <h2 class="b2b-card__title"><i class="fas fa-shopping-bag" aria-hidden="true"></i> Pesanan Terakhir</h2>
                            <p class="b2b-card__desc">Maksimal 10 pesanan terbaru dari semua outlet.</p>
                        </div>
                    </div>
                    @if ($recentOrders->isEmpty())
                        <div class="b2b-empty">
                            <span class="b2b-empty__icon" aria-hidden="true"><i class="fas fa-shopping-bag"></i></span>
                            <p class="b2b-empty__title">Belum ada pesanan</p>
                            <p class="b2b-empty__text">Pesanan yang dibuat di Kasir B2B untuk customer ini muncul di sini.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="b2b-table b2b-table--card">
                                <thead><tr><th>Pesanan</th><th>Outlet</th><th class="text-end">Total</th><th>Status</th><th>Invoice</th></tr></thead>
                                <tbody>
                                    @foreach ($recentOrders as $order)
                                        <tr>
                                            <td>
                                                @can('read b2b/orders')
                                                    <a href="{{ route('b2b/orders/show', $order->id) }}">{{ $order->order_number }}</a>
                                                @else
                                                    <span class="fw-bold">{{ $order->order_number }}</span>
                                                @endcan
                                                <div class="b2b-muted">{{ $order->order_date->translatedFormat('d M Y, H:i') }}</div>
                                            </td>
                                            <td>{{ $order->outlet?->name }}</td>
                                            <td class="text-end b2b-money fw-bold">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
                                            <td><span class="badge badge-{{ $order->statusVariant() }}">{{ $order->statusLabel() }}</span></td>
                                            <td>@if ($order->invoice)<span class="badge badge-{{ $order->invoice->statusVariant() }}">{{ $order->invoice->statusLabel() }}</span>@endif</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div>
                <div class="ob-card">
                    <div class="b2b-card__head">
                        <h2 class="b2b-card__title"><i class="fas fa-id-card" aria-hidden="true"></i> Data Customer</h2>
                    </div>
                    <div class="b2b-card__body">
                        <dl class="b2b-dl">
                            <div class="b2b-dl__row"><dt>NPWP</dt><dd>{{ $customer->npwp }}</dd></div>
                            <div class="b2b-dl__row"><dt>PIC</dt><dd>{{ $customer->contact_name }}</dd></div>
                            <div class="b2b-dl__row"><dt>Telepon</dt><dd>@if ($customer->phone)<a href="tel:{{ $customer->phone }}">{{ $customer->phone }}</a>@endif</dd></div>
                            <div class="b2b-dl__row"><dt>Email</dt><dd>@if ($customer->email)<a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>@endif</dd></div>
                            <div class="b2b-dl__row"><dt>Alamat</dt><dd>{{ $customer->hasAddress() ? $customer->fullAddress() : '' }}</dd></div>
                            <div class="b2b-dl__row">
                                <dt>Reminder</dt>
                                <dd>
                                    @if ($customer->reminder_enabled)
                                        <div class="b2b-tags">
                                            @foreach ($customer->reminder_channels ?? [] as $channel)
                                                <span class="b2b-tag b2b-tag--primary">{{ \App\Models\B2b\Customer::REMINDER_CHANNELS[$channel] ?? $channel }}</span>
                                            @endforeach
                                            @foreach (\App\Models\B2b\Customer::REMINDER_DAYS as $day => $label)
                                                @if (in_array($day, $customer->reminder_days ?? []))
                                                    <span class="b2b-tag">{{ $label }}</span>
                                                @endif
                                            @endforeach
                                            @if ($customer->reminder_overdue_daily)
                                                <span class="b2b-tag">Harian setelah lewat</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="b2b-muted">Tidak dikirim</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="b2b-dl__row"><dt>Catatan</dt><dd>{{ $customer->notes }}</dd></div>
                        </dl>
                    </div>
                    <div class="b2b-card__foot">
                        Dibuat {{ $customer->created_at?->translatedFormat('d M Y, H:i') }}{{ $customer->creator ? ' oleh '.$customer->creator->name : '' }}
                        @if ($customer->modified_by)
                            <br>Diubah {{ $customer->updated_at?->translatedFormat('d M Y, H:i') }}{{ $customer->modifier ? ' oleh '.$customer->modifier->name : '' }}
                        @endif
                    </div>
                </div>

                <div class="ob-card">
                    <div class="b2b-card__head">
                        <div>
                            <h2 class="b2b-card__title"><i class="fas fa-university" aria-hidden="true"></i> Rekening Pengirim <span class="b2b-count">{{ $customer->accounts->count() }}</span></h2>
                            <p class="b2b-card__desc">Rekening &amp; e-wallet yang biasa dipakai customer untuk transfer.</p>
                        </div>
                    </div>
                    @if ($customer->accounts->isEmpty())
                        <div class="b2b-empty">
                            <span class="b2b-empty__icon" aria-hidden="true"><i class="fas fa-university"></i></span>
                            <p class="b2b-empty__title">Belum ada rekening terdaftar</p>
                            <p class="b2b-empty__text">
                                @can('update b2b/customers')
                                    Tambahkan lewat <a href="{{ route('b2b/customers/edit', $customer->id) }}">Edit customer</a> supaya pembayaran mudah dikenali.
                                @else
                                    Rekening ditambahkan lewat Edit customer.
                                @endcan
                            </p>
                        </div>
                    @else
                        <ul class="b2b-accounts">
                            @foreach ($customer->accounts as $account)
                                <li class="b2b-account">
                                    <span class="b2b-account__icon b2b-account__icon--{{ $account->type }}" aria-hidden="true">
                                        <i class="fas {{ $account->type === \App\Models\B2b\CustomerAccount::TYPE_EWALLET ? 'fa-mobile-alt' : 'fa-university' }}"></i>
                                    </span>
                                    <div class="b2b-account__body">
                                        <div class="b2b-account__top">
                                            <strong>{{ $account->providerLabel() }}</strong>
                                            <span class="b2b-tag">{{ $account->typeLabel() }}</span>
                                        </div>
                                        <span class="b2b-account__number b2b-mono">{{ $account->formattedNumber() }}</span>
                                        <span class="b2b-account__name">a.n. {{ $account->account_name }}</span>
                                        @if ($account->notes)
                                            <span class="b2b-muted">{{ $account->notes }}</span>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                @can('delete b2b/customers')
                    <div class="ob-card">
                        <div class="b2b-danger-zone">
                            <div>
                                <p class="b2b-danger-zone__title">Hapus customer</p>
                                <p class="b2b-danger-zone__text">Hanya untuk customer yang belum pernah memesan. Yang sudah punya pesanan cukup dinonaktifkan.</p>
                            </div>
                            <a href="{{ route('b2b/customers/destroy', $customer->id) }}" class="btn ob-btn ob-btn-danger ob-btn-sm" data-b2b-confirm data-method="DELETE"
                                data-title="Hapus customer {{ $customer->name }}?" data-text="Kontrak draft/aktif customer ini ikut dihentikan." data-confirm-text="Ya, hapus">
                                <i class="fas fa-trash" aria-hidden="true"></i> Hapus
                            </a>
                        </div>
                    </div>
                @endcan
            </div>
        </div>
    </div>
@endsection

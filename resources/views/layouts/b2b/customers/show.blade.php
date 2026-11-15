@extends('layouts.app')

@section('content')
    @include('layouts.b2b.partials.assets')

    <div class="main-content ob-page">
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow"><a href="{{ route('b2b/customers') }}">Customer B2B</a> · {{ $customer->code }}</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-building"></i></span>
                    {{ $customer->name }}
                    <span class="badge badge-muted ms-2">{{ $customer->typeLabel() }}</span>
                    @unless ($customer->is_active)
                        <span class="badge badge-muted ms-2">Nonaktif</span>
                    @endunless
                </h1>
                <p class="ob-header__desc">
                    @if ($effectiveContract)
                        Kontrak berlaku <a href="{{ route('b2b/contracts/show', $effectiveContract->id) }}">{{ $effectiveContract->contract_number }}</a>
                        sampai {{ $effectiveContract->end_date->translatedFormat('d M Y') }}
                    @else
                        Tanpa kontrak berlaku (harga normal)
                    @endif
                </p>
            </div>
            <div class="b2b-actions">
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
                <div class="ob-card mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="ob-section-title mb-0"><i class="fas fa-file-signature" aria-hidden="true"></i> Kontrak</h2>
                    </div>
                    @if ($contracts->isEmpty())
                        <p class="b2b-muted mb-0">Belum ada kontrak. Customer tetap bisa belanja dengan harga normal.</p>
                    @else
                        <div class="table-responsive">
                            <table class="b2b-table">
                                <thead><tr><th>No. Kontrak</th><th>Periode</th><th>Aturan</th><th>Status</th></tr></thead>
                                <tbody>
                                    @foreach ($contracts as $contract)
                                        <tr>
                                            <td><a href="{{ route('b2b/contracts/show', $contract->id) }}" class="fw-bold">{{ $contract->contract_number }}</a></td>
                                            <td class="text-nowrap">{{ $contract->start_date->translatedFormat('d M Y') }} – {{ $contract->end_date->translatedFormat('d M Y') }}</td>
                                            <td>{{ $contract->rules_count }}</td>
                                            <td><span class="badge badge-{{ $contract->statusVariant() }}">{{ $contract->statusLabel() }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="ob-card mb-3">
                    <h2 class="ob-section-title"><i class="fas fa-shopping-bag" aria-hidden="true"></i> Pesanan Terakhir</h2>
                    @if ($recentOrders->isEmpty())
                        <p class="b2b-muted mb-0">Belum ada pesanan.</p>
                    @else
                        <div class="table-responsive">
                            <table class="b2b-table">
                                <thead><tr><th>Pesanan</th><th>Outlet</th><th class="text-end">Total</th><th>Status</th><th>Invoice</th></tr></thead>
                                <tbody>
                                    @foreach ($recentOrders as $order)
                                        <tr>
                                            <td>
                                                @can('read b2b/orders')
                                                    <a href="{{ route('b2b/orders/show', $order->id) }}" class="fw-bold">{{ $order->order_number }}</a>
                                                @else
                                                    <span class="fw-bold">{{ $order->order_number }}</span>
                                                @endcan
                                                <div class="b2b-muted">{{ $order->order_date->translatedFormat('d M Y, H:i') }}</div>
                                            </td>
                                            <td>{{ $order->outlet?->name }}</td>
                                            <td class="text-end b2b-money">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
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
                <div class="ob-card mb-3">
                    <h2 class="ob-section-title"><i class="fas fa-id-card" aria-hidden="true"></i> Data Customer</h2>
                    <dl class="ob-meta" style="grid-template-columns: 1fr;">
                        <div class="ob-meta__item"><dt>NPWP</dt><dd>{{ $customer->npwp }}</dd></div>
                        <div class="ob-meta__item"><dt>Telepon</dt><dd>{{ $customer->phone }}</dd></div>
                        <div class="ob-meta__item"><dt>Email</dt><dd>{{ $customer->email }}</dd></div>
                        <div class="ob-meta__item"><dt>Reminder jatuh tempo</dt><dd>{{ $customer->reminderSummary() }}</dd></div>
                        <div class="ob-meta__item"><dt>Catatan</dt><dd>{{ $customer->notes }}</dd></div>
                    </dl>
                    <p class="b2b-muted mb-0">
                        Dibuat {{ $customer->created_at?->translatedFormat('d M Y H:i') }}{{ $customer->creator ? ' oleh '.$customer->creator->name : '' }}
                        @if ($customer->modified_by)
                            <br>Terakhir diubah {{ $customer->updated_at?->translatedFormat('d M Y H:i') }}{{ $customer->modifier ? ' oleh '.$customer->modifier->name : '' }}
                        @endif
                    </p>
                </div>

                <div class="ob-card mb-3">
                    <h2 class="ob-section-title"><i class="fas fa-user-tie" aria-hidden="true"></i> Kontak</h2>
                    <ul class="b2b-list">
                        @forelse ($customer->contacts as $contact)
                            <li>
                                <div class="fw-bold">
                                    {{ $contact->name }}
                                    @if ($contact->is_primary)<span class="badge badge-primary ms-1">Utama</span>@endif
                                    @if ($contact->receive_reminder)<span class="badge badge-success ms-1"><i class="fas fa-bell" aria-hidden="true"></i> Reminder</span>@endif
                                </div>
                                <div class="b2b-muted">{{ collect([$contact->position, $contact->phone, $contact->email])->filter()->implode(' · ') }}</div>
                            </li>
                        @empty
                            <li class="b2b-muted">Belum ada kontak.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="ob-card mb-3">
                    <h2 class="ob-section-title"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> Alamat</h2>
                    <ul class="b2b-list">
                        @forelse ($customer->addresses as $address)
                            <li>
                                <div class="fw-bold">
                                    {{ $address->label }}
                                    @if ($address->is_default_billing)<span class="badge badge-primary ms-1">Penagihan</span>@endif
                                    @if ($address->is_default_shipping)<span class="badge badge-success ms-1">Pengiriman</span>@endif
                                </div>
                                <div>{{ $address->fullAddress() }}</div>
                                @if ($address->recipient_name || $address->phone)
                                    <div class="b2b-muted">{{ collect([$address->recipient_name, $address->phone])->filter()->implode(' · ') }}</div>
                                @endif
                            </li>
                        @empty
                            <li class="b2b-muted">Belum ada alamat.</li>
                        @endforelse
                    </ul>
                </div>

                @can('delete b2b/customers')
                    <div class="ob-card">
                        <h2 class="ob-section-title"><i class="fas fa-trash" aria-hidden="true"></i> Hapus Customer</h2>
                        <p class="b2b-muted">Hanya bisa untuk customer yang belum pernah memesan. Customer dengan riwayat pesanan cukup dinonaktifkan.</p>
                        <a href="{{ route('b2b/customers/destroy', $customer->id) }}" class="btn ob-btn ob-btn-danger btn-sm" data-b2b-confirm data-method="DELETE"
                            data-title="Hapus customer {{ $customer->name }}?" data-text="Kontrak draft/aktif customer ini ikut dihentikan." data-confirm-text="Ya, hapus">
                            Hapus customer
                        </a>
                    </div>
                @endcan
            </div>
        </div>
    </div>
@endsection

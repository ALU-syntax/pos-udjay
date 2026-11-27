@extends('layouts.app')
@section('content')
    @include('layouts.reports.partials.ui-styles')
    @include('layouts.warehouse.partials.ui-styles')

    @php
        // Qty tanpa nol di belakang koma (decimal:5); Rupiah tanpa ,00 kalau bulat
        $qty = fn ($value) => rtrim(rtrim(number_format((float) $value, 5, ',', '.'), '0'), ',');
        $rupiah = fn ($value) => 'Rp ' . number_format((float) $value, fmod((float) $value, 1) ? 2 : 0, ',', '.');
        $unitOf = fn ($unit) => $unit?->symbol ?: $unit?->name;
        $dateTime = fn ($value) => $value?->translatedFormat('d M Y, H:i');
        $partyLabel = fn ($party) => ['buyer' => 'Pembeli (internal)', 'supplier' => 'Supplier'][$party] ?? ucfirst((string) $party);
        $allItems = $purchaseOrders->flatMap(fn ($purchaseOrder) => $purchaseOrder->items);
        $fullyReceived = $allItems->filter(fn ($item) => (float) $item->qty_base_ordered > 0 && (float) $item->qty_base_received >= (float) $item->qty_base_ordered)->count();
        $hasPrice = $allItems->contains(fn ($item) => $item->subtotal !== null);
        $planStatus = $procurementPlan->status;
    @endphp

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">
                    <a href="{{ route('warehouse/procurement-plan') }}">Procurement Plan</a><span class="wh-crumb-sep" aria-hidden="true">/</span><a
                        href="{{ route('warehouse/procurement-plan/detail', $procurementPlan->id) }}">{{ $procurementPlan->plan_number }}</a>
                </div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-file-invoice"></i></span>
                    Purchase Order
                </h1>
                <div class="wh-profile-meta">
                    @if ($planStatus)
                        <span class="badge {{ $planStatus->badge_class }}">Plan: {{ $planStatus->label }}</span>
                    @endif
                    <span><i class="fas fa-warehouse" aria-hidden="true"></i> {{ $procurementPlan->planningLocation?->name ?? '-' }}</span>
                    <span>Satu PO per supplier, dibuat dari plan ini.</span>
                </div>
            </div>
            <div class="wh-actions">
                <a href="{{ route('warehouse/procurement-plan/detail', $procurementPlan->id) }}" class="btn ob-btn ob-btn-outline">
                    <i class="fas fa-clipboard-check" aria-hidden="true"></i> Detail Plan
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="ob-notice pp-notice" role="status">
                <i class="fas fa-check-circle" aria-hidden="true"></i> {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="ob-notice pp-notice pp-notice--error" role="alert">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i> {{ $errors->first() }}
            </div>
        @endif

        {{-- Ringkasan --}}
        <div class="ob-stats">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-file-invoice"></i></span>
                <div>
                    <p class="ob-stat__label">Purchase Order</p>
                    <p class="ob-stat__value">{{ number_format($stats['total_purchase_orders'] ?? 0, 0, ',', '.') }}</p>
                    <p class="wh-stat-sub">{{ number_format($stats['total_suppliers'] ?? 0, 0, ',', '.') }} supplier</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-boxes"></i></span>
                <div>
                    <p class="ob-stat__label">Item Dipesan</p>
                    <p class="ob-stat__value">{{ number_format($stats['total_items'] ?? 0, 0, ',', '.') }}</p>
                    <p class="wh-stat-sub">bahan di semua PO</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--success" aria-hidden="true"><i class="fas fa-truck-loading"></i></span>
                <div>
                    <p class="ob-stat__label">Diterima Penuh</p>
                    <p class="ob-stat__value">{{ number_format($fullyReceived, 0, ',', '.') }}<span class="pp-stat-of"> / {{ number_format($allItems->count(), 0, ',', '.') }}</span></p>
                    <p class="wh-stat-sub">{{ number_format($stats['total_receipts'] ?? 0, 0, ',', '.') }} dokumen penerimaan</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--danger" aria-hidden="true"><i class="fas fa-coins"></i></span>
                <div>
                    <p class="ob-stat__label">Estimasi Nilai</p>
                    <p class="ob-stat__value pp-stat-money">{{ $hasPrice ? $rupiah($stats['estimated_total'] ?? 0) : '-' }}</p>
                    <p class="wh-stat-sub">{{ $hasPrice ? 'total subtotal semua PO' : 'harga belum diisi' }}</p>
                </div>
            </div>
        </div>

        @forelse ($purchaseOrders as $purchaseOrder)
            @php
                $items = $purchaseOrder->items;
                $status = $purchaseOrder->status;
                $contact = $purchaseOrder->supplier?->primaryContact;
                $contactText = $contact ? ($contact->phone ?: $contact->email ?: $contact->name) : null;
                $poHasPrice = $items->contains(fn ($item) => $item->subtotal !== null);
                $receipts = $purchaseOrder->receipts;
                $cancellations = $purchaseOrder->cancellations;
            @endphp

            <div class="ob-card pp-po">
                <div class="wh-card__head">
                    <div>
                        <h2 class="wh-card__title">
                            <i class="fas fa-file-invoice" aria-hidden="true"></i> {{ $purchaseOrder->po_number }}
                            @if ($status)
                                <span class="badge {{ $status->badge_class }}">{{ $status->label }}</span>
                            @endif
                        </h2>
                        <p class="wh-card__desc">
                            <i class="fas fa-truck" aria-hidden="true"></i> <strong class="pp-supplier-name">{{ $purchaseOrder->supplier?->name ?? '-' }}</strong>
                            @if ($contactText)
                                · {{ $contactText }}
                            @endif
                        </p>
                    </div>
                    <div class="pp-po-total">
                        <span class="pp-po-total__label">Total</span>
                        <span class="pp-po-total__value">{{ $poHasPrice ? $rupiah($items->sum(fn ($item) => (float) ($item->subtotal ?? 0))) : '-' }}</span>
                    </div>
                </div>

                <dl class="pp-po-meta">
                    <div><dt>Tanggal Order</dt><dd>{{ $purchaseOrder->order_date?->translatedFormat('d M Y') }}</dd></div>
                    <div><dt>Perkiraan Tiba</dt><dd>{{ $purchaseOrder->expected_delivery_date?->translatedFormat('d M Y') }}</dd></div>
                    <div><dt>Lokasi Terima</dt><dd>{{ $purchaseOrder->receivingLocation?->name }}</dd></div>
                    <div><dt>Diajukan Oleh</dt><dd>{{ $purchaseOrder->requestedBy?->name }}</dd></div>
                </dl>

                @if ($items->isEmpty())
                    <p class="wh-side-empty">Belum ada item di PO ini.</p>
                @else
                    <div class="ob-table-scroll">
                        <table class="wh-table pp-po-items">
                            <thead>
                                <tr>
                                    <th scope="col">Bahan Baku</th>
                                    <th scope="col" class="text-end">Dipesan</th>
                                    <th scope="col" class="text-end">Diterima</th>
                                    <th scope="col" class="text-end">Harga</th>
                                    <th scope="col" class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    @php
                                        // qty_ordered dalam satuan beli (unit_id); diterima/ditolak/batal dalam satuan dasar
                                        $orderUnit = $unitOf($item->unit);
                                        $unit = $unitOf($item->rawMaterial?->baseUnit) ?: $orderUnit;
                                        $received = (float) $item->qty_base_received;
                                        $ordered = (float) $item->qty_base_ordered;
                                        $extra = collect([
                                            (float) $item->qty_base_rejected > 0 ? 'Ditolak ' . $qty($item->qty_base_rejected) . ' ' . $unit : null,
                                            (float) $item->qty_base_cancelled > 0 ? 'Batal ' . $qty($item->qty_base_cancelled) . ' ' . $unit : null,
                                        ])->filter()->implode(' · ');
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="wh-cell-stack">
                                                <span class="wh-strong">{{ $item->rawMaterial?->name ?? '-' }}</span>
                                                <span class="ob-cell-meta">{{ $item->rawMaterial?->code ?: 'Tanpa kode' }}</span>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="wh-cell-stack wh-cell-stack--end">
                                                <span class="wh-num wh-strong">{{ $qty($item->qty_ordered) }} {{ $orderUnit ?: $unit }}</span>
                                                @if ($orderUnit && $orderUnit !== $unit)
                                                    <span class="ob-cell-meta wh-num">= {{ $qty($item->qty_base_ordered) }} {{ $unit }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="wh-cell-stack wh-cell-stack--end">
                                                @if ($ordered > 0 && $received >= $ordered)
                                                    <span class="badge badge-success">Lengkap</span>
                                                @else
                                                    <span class="wh-num">{{ $qty($received) }} {{ $unit }}</span>
                                                @endif
                                                @if ($extra)
                                                    <span class="ob-cell-meta wh-num">{{ $extra }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            @if ($item->unit_price !== null)
                                                <div class="wh-cell-stack wh-cell-stack--end">
                                                    <span class="wh-num">{{ $rupiah($item->unit_price) }}</span>
                                                    @if ($orderUnit)
                                                        <span class="ob-cell-meta">per {{ $orderUnit }}</span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="ob-cell-meta">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end wh-num wh-strong">{{ $item->subtotal !== null ? $rupiah($item->subtotal) : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($receipts->isNotEmpty())
                    <div class="pp-po-section">
                        <h3 class="pp-po-section__title"><i class="fas fa-truck-loading" aria-hidden="true"></i> Penerimaan <span class="wh-count">{{ $receipts->count() }}</span></h3>
                    </div>
                    <div class="ob-table-scroll">
                        <table class="wh-table">
                            <thead>
                                <tr>
                                    <th scope="col">Penerimaan</th>
                                    <th scope="col">Surat Jalan / Invoice</th>
                                    <th scope="col">Diterima</th>
                                    <th scope="col" class="text-end">Item</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($receipts as $receipt)
                                    <tr>
                                        <td>
                                            <div class="wh-cell-stack">
                                                <span class="wh-strong pp-nowrap">{{ $receipt->receipt_number ?? '-' }}</span>
                                                @if ($receipt->status)
                                                    <span class="badge {{ $receipt->status->badge_class }}">{{ $receipt->status->label }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="wh-cell-stack">
                                                <span>{{ $receipt->delivery_note_number ?: 'Tanpa surat jalan' }}</span>
                                                <span class="ob-cell-meta">{{ $receipt->supplier_invoice_number ? 'Invoice ' . $receipt->supplier_invoice_number : 'Tanpa invoice' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="wh-cell-stack">
                                                <span class="pp-nowrap">{{ $dateTime($receipt->received_at) ?? '-' }}</span>
                                                <span class="ob-cell-meta">{{ $receipt->receivedBy?->name ?? '-' }}</span>
                                            </div>
                                        </td>
                                        <td class="text-end wh-num">{{ $receipt->items->count() }} bahan</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($cancellations->isNotEmpty())
                    <div class="pp-po-section">
                        <h3 class="pp-po-section__title"><i class="fas fa-ban" aria-hidden="true"></i> Pembatalan <span class="wh-count">{{ $cancellations->count() }}</span></h3>
                    </div>
                    <div class="ob-table-scroll">
                        <table class="wh-table">
                            <thead>
                                <tr>
                                    <th scope="col">Pembatalan</th>
                                    <th scope="col">Waktu</th>
                                    <th scope="col">Alasan</th>
                                    <th scope="col" class="text-end">Item</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($cancellations as $cancellation)
                                    <tr>
                                        <td>
                                            <div class="wh-cell-stack">
                                                <span class="wh-strong pp-nowrap">{{ $cancellation->cancellation_number ?: 'Tanpa nomor' }}</span>
                                                <span class="ob-cell-meta">Oleh {{ $partyLabel($cancellation->cancelled_by_party) }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="wh-cell-stack">
                                                <span class="pp-nowrap">{{ $dateTime($cancellation->cancelled_at) ?? '-' }}</span>
                                                <span class="ob-cell-meta">{{ $cancellation->cancelledBy?->name ? 'Dicatat ' . $cancellation->cancelledBy->name : 'Dari supplier' }}</span>
                                            </div>
                                        </td>
                                        <td class="pp-reason">{{ $cancellation->reason ?: '-' }}</td>
                                        <td class="text-end wh-num">{{ $cancellation->items->count() }} bahan</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($receipts->isEmpty() && $cancellations->isEmpty())
                    <p class="pp-po-foot"><i class="fas fa-info-circle" aria-hidden="true"></i> Belum ada penerimaan maupun pembatalan untuk PO ini.</p>
                @endif
            </div>
        @empty
            <div class="ob-card">
                <div class="wh-empty">
                    <span class="wh-empty__icon" aria-hidden="true"><i class="fas fa-file-invoice"></i></span>
                    <p class="wh-empty__title">Belum ada Purchase Order</p>
                    <p class="wh-empty__text">PO hanya dibuat untuk bahan yang perlu dibeli. Bila semua bahan cukup dari stok di lokasi planning, plan ini tidak menghasilkan PO.</p>
                </div>
            </div>
        @endforelse
    </div>

    @push('css')
        <style>
            .ob-page .ob-stats .ob-stat { margin-bottom: 0; }
            .pp-notice { margin-top: 1rem; }
            .pp-notice--error { border-color: var(--ob-danger); color: var(--ob-danger); background: var(--ob-danger-soft); }
            .ob-page .pp-stat-money { font-size: 1.15rem; font-variant-numeric: tabular-nums; white-space: nowrap; }
            .pp-stat-of { font-size: .9rem; font-weight: 500; color: var(--ob-muted); }

            .pp-po { margin-top: 1.25rem; }
            .pp-po .wh-card__title .badge { font-size: .7rem; }
            .pp-supplier-name { color: var(--ob-text); font-weight: 600; }
            .pp-po-total { display: flex; flex-direction: column; align-items: flex-end; }
            .pp-po-total__label { font-size: .7rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--ob-muted); }
            @media (max-width: 575.98px) { .pp-po-total { align-items: flex-start; } }
            .pp-po-total__value { font-size: 1.05rem; font-weight: 700; color: var(--ob-text); font-variant-numeric: tabular-nums; white-space: nowrap; }

            .pp-po-meta { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem 1.25rem; margin: 0; padding: 1rem 1.25rem; border-bottom: 1px solid var(--ob-border); }
            .pp-po-meta dt { font-size: .72rem; font-weight: 600; color: var(--ob-muted); }
            .pp-po-meta dd { margin: .15rem 0 0; font-size: .85rem; font-weight: 500; color: var(--ob-text); overflow-wrap: anywhere; }
            .pp-po-meta dd:empty::before { content: "—"; font-weight: 400; color: var(--ob-muted); }
            @media (max-width: 767.98px) { .pp-po-meta { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

            .pp-po-items td:first-child { min-width: 200px; }
            /* Lebar kolom angka sama di setiap kartu PO agar mudah dibandingkan */
            .pp-po-items th:nth-child(n+2) { width: 16%; }
            .pp-po-section { padding: .9rem 1.25rem .6rem; border-top: 1px solid var(--ob-border); }
            .pp-po-section__title { display: flex; align-items: center; gap: .5rem; margin: 0; font-size: .85rem; font-weight: 700; color: var(--ob-text); }
            .pp-po-section__title > i { font-size: .8rem; color: var(--ob-primary); }
            .pp-nowrap { white-space: nowrap; }
            .pp-reason { min-width: 200px; white-space: normal; }
            .pp-po-foot { margin: 0; padding: .85rem 1.25rem; border-top: 1px solid var(--ob-border); font-size: .8rem; color: var(--ob-muted); }
        </style>
    @endpush
@endsection

@extends('layouts.app')
@section('content')
    @include('layouts.reports.partials.ui-styles')
    @include('layouts.warehouse.partials.ui-styles')

    @php
        $status = $procurementPlan->status;
        $isDraft = $status?->code === \App\Models\ProcurementPlanStatus::DRAFT;
        // Qty tanpa nol di belakang koma (decimal:5); Rupiah tanpa ,00 kalau bulat
        $qty = fn ($value) => rtrim(rtrim(number_format((float) $value, 5, ',', '.'), '0'), ',');
        $rupiah = fn ($value) => 'Rp ' . number_format((float) $value, fmod((float) $value, 1) ? 2 : 0, ',', '.');
        $unitOf = fn ($unit) => $unit?->symbol ?: $unit?->name;
        $items = $procurementPlan->items->sortBy(fn ($item) => $item->rawMaterial?->name)->values();
        $sources = $items->flatMap(fn ($item) => $item->sources->map(fn ($source) => (object) ['source' => $source, 'item' => $item]));
        $toPurchaseCount = $items->filter(fn ($item) => (float) $item->qty_to_purchase_base > 0)->count();
        $hasPrice = $items->contains(fn ($item) => $item->estimated_subtotal !== null);
        $canRead = auth()->user()->can('read warehouse/procurement-plan');
        // Qty dalam satuan beli diambil dari snapshot item PO (satu PO per supplier, satu baris per bahan)
        $poItemByKey = $procurementPlan->purchaseOrders->flatMap(fn ($purchaseOrder) => $purchaseOrder->items
            ->mapWithKeys(fn ($poItem) => [$purchaseOrder->supplier_id . '-' . $poItem->raw_material_id => $poItem]));
    @endphp

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">
                    <a href="{{ route('warehouse/procurement-plan') }}">Procurement Plan</a><span class="wh-crumb-sep" aria-hidden="true">/</span>Detail
                </div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-clipboard-check"></i></span>
                    {{ $procurementPlan->plan_number }}
                </h1>
                <div class="wh-profile-meta">
                    @if ($status)
                        <span class="badge {{ $status->badge_class }}">{{ $status->label }}</span>
                    @endif
                    <span><i class="fas fa-warehouse" aria-hidden="true"></i> {{ $procurementPlan->planningLocation?->name ?? '-' }}</span>
                    <span>Dibuat {{ $procurementPlan->planned_at?->translatedFormat('d M Y, H:i') ?? '-' }}{{ $procurementPlan->plannedBy ? ' oleh ' . $procurementPlan->plannedBy->name : '' }}</span>
                </div>
            </div>
            <div class="wh-actions">
                @if ($canRead)
                    <a href="{{ route('warehouse/procurement-plan/purchase-orders', $procurementPlan->id) }}" class="btn ob-btn ob-btn-outline">
                        <i class="fas fa-file-invoice" aria-hidden="true"></i> Purchase Order
                    </a>
                @endif
                @if ($isDraft)
                    @can('delete warehouse/procurement-plan')
                        <a href="{{ route('warehouse/procurement-plan/destroy', $procurementPlan->id) }}" class="btn ob-btn ob-btn-outline pp-btn-danger delete-procurement-plan"
                            data-number="{{ $procurementPlan->plan_number }}">
                            <i class="fas fa-trash" aria-hidden="true"></i> Hapus Draft
                        </a>
                    @endcan
                @endif
            </div>
        </div>

        @if (session('success'))
            <div class="ob-notice pp-notice" role="status">
                <i class="fas fa-check-circle" aria-hidden="true"></i> {{ session('success') }}
            </div>
        @endif

        {{-- Ringkasan --}}
        <div class="ob-stats">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-boxes"></i></span>
                <div>
                    <p class="ob-stat__label">Bahan Baku</p>
                    <p class="ob-stat__value">{{ number_format($items->count(), 0, ',', '.') }}</p>
                    <p class="wh-stat-sub">dari {{ number_format($sources->count(), 0, ',', '.') }} request</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-shopping-cart"></i></span>
                <div>
                    <p class="ob-stat__label">Perlu Dibeli</p>
                    <p class="ob-stat__value">{{ number_format($toPurchaseCount, 0, ',', '.') }}</p>
                    <p class="wh-stat-sub">bahan, sisanya cukup dari stok</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--success" aria-hidden="true"><i class="fas fa-file-invoice"></i></span>
                <div>
                    <p class="ob-stat__label">Purchase Order</p>
                    <p class="ob-stat__value">{{ number_format($procurementPlan->purchase_orders_count, 0, ',', '.') }}</p>
                    <p class="wh-stat-sub">satu PO per supplier</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--danger" aria-hidden="true"><i class="fas fa-coins"></i></span>
                <div>
                    <p class="ob-stat__label">Estimasi Biaya</p>
                    <p class="ob-stat__value pp-stat-money">{{ $hasPrice ? $rupiah($stats['estimated_total'] ?? 0) : '-' }}</p>
                    <p class="wh-stat-sub">{{ $hasPrice ? 'dari harga supplier terakhir' : 'harga supplier belum diisi' }}</p>
                </div>
            </div>
        </div>

        <div class="wh-detail-grid">
            <div>
                {{-- Bahan baku --}}
                <div class="ob-card">
                    <div class="wh-card__head">
                        <div>
                            <h2 class="wh-card__title"><i class="fas fa-boxes" aria-hidden="true"></i> Bahan Baku <span class="wh-count">{{ $items->count() }}</span></h2>
                            <p class="wh-card__desc">Kebutuhan digabung per bahan. Yang dibeli hanya kekurangan setelah dikurangi stok bebas di lokasi planning.</p>
                        </div>
                    </div>
                    @if ($items->isEmpty())
                        <div class="wh-empty">
                            <span class="wh-empty__icon" aria-hidden="true"><i class="fas fa-boxes"></i></span>
                            <p class="wh-empty__title">Belum ada bahan</p>
                            <p class="wh-empty__text">Plan ini tidak memiliki item.</p>
                        </div>
                    @else
                        <div class="ob-table-scroll">
                            <table class="wh-table pp-items" id="procurement-plan-items-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Bahan Baku</th>
                                        <th scope="col" class="text-end">Dibutuhkan</th>
                                        <th scope="col" class="text-end">Stok Bebas</th>
                                        <th scope="col" class="text-end">Perlu Dibeli</th>
                                        <th scope="col" class="text-end">Estimasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($items as $item)
                                        @php
                                            $unit = $unitOf($item->unit) ?: $unitOf($item->rawMaterial?->baseUnit);
                                            $purchaseUnit = $unitOf($item->supplierRawMaterial?->purchaseUnit);
                                            $poItem = $poItemByKey->get($item->supplier_id . '-' . $item->raw_material_id);
                                            $poUnit = $unitOf($poItem?->unit);
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="wh-cell-stack">
                                                    <span class="wh-strong">{{ $item->rawMaterial?->name ?? '-' }}</span>
                                                    <span class="ob-cell-meta">{{ $item->rawMaterial?->code ?: 'Tanpa kode' }} · {{ $item->sources->count() }} request</span>
                                                    <span class="pp-supplier"><i class="fas fa-truck" aria-hidden="true"></i> {{ $item->supplier?->name ?? 'Tanpa supplier' }}{{ $item->supplierRawMaterial?->supplier_material_name ? ' · ' . $item->supplierRawMaterial->supplier_material_name : '' }}</span>
                                                </div>
                                            </td>
                                            <td class="text-end wh-num">{{ $qty($item->qty_required_base) }} {{ $unit }}</td>
                                            <td class="text-end wh-num">{{ $qty($item->qty_available_base) }} {{ $unit }}</td>
                                            <td class="text-end">
                                                @if ((float) $item->qty_to_purchase_base > 0)
                                                    <div class="wh-cell-stack wh-cell-stack--end">
                                                        <span class="wh-strong wh-num">{{ $qty($item->qty_to_purchase_base) }} {{ $unit }}</span>
                                                        @if ($poItem && $poUnit && $poUnit !== $unit)
                                                            <span class="ob-cell-meta wh-num">= {{ $qty($poItem->qty_ordered) }} {{ $poUnit }}</span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="badge badge-success">Cukup dari stok</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if ($item->estimated_unit_price !== null)
                                                    <div class="wh-cell-stack wh-cell-stack--end">
                                                        <span class="wh-strong wh-num">{{ $rupiah($item->estimated_subtotal ?? 0) }}</span>
                                                        <span class="ob-cell-meta wh-num">{{ $rupiah($item->estimated_unit_price) }}{{ $purchaseUnit ? ' per ' . $purchaseUnit : '' }}</span>
                                                    </div>
                                                @else
                                                    <span class="ob-cell-meta">Harga belum diisi</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Asal request --}}
                <div class="ob-card">
                    <div class="wh-card__head">
                        <div>
                            <h2 class="wh-card__title"><i class="fas fa-clipboard-list" aria-hidden="true"></i> Asal Request <span class="wh-count">{{ $sources->count() }}</span></h2>
                            <p class="wh-card__desc">Item Request Order yang dialokasikan ke plan ini.</p>
                        </div>
                    </div>
                    @if ($sources->isEmpty())
                        <div class="wh-empty">
                            <span class="wh-empty__icon" aria-hidden="true"><i class="fas fa-clipboard-list"></i></span>
                            <p class="wh-empty__title">Belum ada request</p>
                            <p class="wh-empty__text">Tidak ada item Request Order yang tercatat untuk plan ini.</p>
                        </div>
                    @else
                        <div class="ob-table-scroll">
                            <table class="wh-table pp-sources-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Request</th>
                                        <th scope="col">Peminta → Pemenuh</th>
                                        <th scope="col">Bahan Baku</th>
                                        <th scope="col" class="text-end">Disetujui</th>
                                        <th scope="col" class="text-end">Masuk Plan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($sources as $row)
                                        @php
                                            $requestItem = $row->source->rawMaterialRequestItem;
                                            $requestOrder = $requestItem?->rawMaterialRequest;
                                            $unit = $requestItem?->approved_base_satuan_name ?: $unitOf($row->item->unit);
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="wh-cell-stack">
                                                    @if ($requestOrder)
                                                        <a class="wh-strong pp-link" href="{{ route('warehouse/request-order/detail', $requestOrder->id) }}">{{ $requestOrder->request_number }}</a>
                                                    @else
                                                        <span class="wh-strong">-</span>
                                                    @endif
                                                    <span class="ob-cell-meta">{{ $requestOrder?->needed_at ? 'Dibutuhkan ' . $requestOrder->needed_at->translatedFormat('d M Y') : 'Tanpa tanggal dibutuhkan' }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="wh-cell-stack">
                                                    <span class="wh-strong">{{ $requestOrder?->requesterInventory?->name ?? '-' }}</span>
                                                    <span class="ob-cell-meta"><i class="fas fa-long-arrow-alt-right" aria-hidden="true"></i> {{ $requestOrder?->fulfillmentInventory?->name ?? 'Pemenuh belum ditentukan' }}</span>
                                                </div>
                                            </td>
                                            <td>{{ $requestItem?->rawMaterial?->name ?? $row->item->rawMaterial?->name ?? '-' }}</td>
                                            <td class="text-end wh-num">{{ $qty($requestItem?->approved_base_qty ?? 0) }} {{ $unit }}</td>
                                            <td class="text-end wh-num wh-strong">{{ $qty($row->source->qty_base_allocated) }} {{ $unit }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Kolom samping --}}
            <div>
                <div class="ob-card">
                    <div class="wh-card__head">
                        <h2 class="wh-card__title"><i class="fas fa-id-card" aria-hidden="true"></i> Informasi Plan</h2>
                    </div>
                    <div class="wh-card__body">
                        <dl class="wh-dl">
                            <div class="wh-dl__row"><dt>Nomor</dt><dd>{{ $procurementPlan->plan_number }}</dd></div>
                            <div class="wh-dl__row"><dt>Lokasi</dt><dd>{{ $procurementPlan->planningLocation?->name }}</dd></div>
                            <div class="wh-dl__row"><dt>Dibuat</dt><dd>{{ $procurementPlan->planned_at?->translatedFormat('d M Y, H:i') }}</dd></div>
                            <div class="wh-dl__row"><dt>Oleh</dt><dd>{{ $procurementPlan->plannedBy?->name }}</dd></div>
                            @if ($procurementPlan->approved_at)
                                <div class="wh-dl__row"><dt>Jadi PO</dt><dd>{{ $procurementPlan->approved_at->translatedFormat('d M Y, H:i') }}{{ $procurementPlan->approvedBy ? ' · ' . $procurementPlan->approvedBy->name : '' }}</dd></div>
                            @endif
                            <div class="wh-dl__row"><dt>Catatan</dt><dd class="pp-notes">{{ $procurementPlan->notes }}</dd></div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('js')
        <script>
            $(function () {
                $('.main-content').on('click', '.delete-procurement-plan', function (event) {
                    event.preventDefault();
                    const button = this;

                    Swal.fire({
                        title: 'Hapus draft?',
                        text: 'Plan ' + $(button).data('number') + ' akan dihapus dan item Request Order-nya bisa direncanakan lagi.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (!result.isConfirmed) return;

                        $.ajax({
                            url: button.href,
                            method: 'DELETE',
                            data: { _token: @json(csrf_token()) },
                            success: function (response) {
                                showToast(response.status || 'success', response.message || 'Procurement plan draft berhasil dihapus.');
                                window.location.href = @json(route('warehouse/procurement-plan'));
                            },
                            error: function (xhr) {
                                const errors = xhr.responseJSON?.errors;
                                const message = errors ? Object.values(errors).flat()[0] : xhr.responseJSON?.message;
                                showToast('error', message || 'Gagal menghapus procurement plan');
                            }
                        });
                    });
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            .ob-page .ob-stats .ob-stat { margin-bottom: 0; }
            .pp-notice { margin-top: 1rem; }
            .ob-page .pp-stat-money { font-size: 1.15rem; font-variant-numeric: tabular-nums; white-space: nowrap; }
            .pp-btn-danger { color: var(--ob-danger) !important; border-color: var(--ob-danger) !important; }
            .pp-btn-danger:hover { background: var(--ob-danger-soft) !important; }
            .pp-notes { white-space: pre-line; font-weight: 400 !important; }
            .pp-link { text-decoration: none; color: var(--ob-primary); }
            .pp-link:hover { text-decoration: underline; }

            .pp-supplier { font-size: .78rem; color: var(--ob-text-2); }
            .pp-supplier i { width: .9rem; font-size: .7rem; color: var(--ob-muted); }
            .pp-items td:first-child, .pp-sources-table td:first-child { min-width: 200px; }
            .pp-sources-table td:nth-child(2) { min-width: 180px; }
        </style>
    @endpush
@endsection

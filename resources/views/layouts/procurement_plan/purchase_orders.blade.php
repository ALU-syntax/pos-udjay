@extends('layouts.app')
@section('content')
    @php
        $formatQty = fn ($value) => number_format((float) $value, 1, ',', '.');
        $formatMoney = fn ($value) => $value === null ? '-' : 'Rp ' . number_format((float) $value, 2, ',', '.');
        $poStatusClass = fn ($code) => match ($code) {
            'draft' => 'bg-secondary',
            'submitted' => 'bg-primary',
            'approved' => 'bg-success',
            'ordered' => 'bg-info text-dark',
            'partially_received' => 'bg-warning text-dark',
            'received' => 'bg-success',
            'cancelled' => 'bg-dark',
            'rejected' => 'bg-danger',
            default => 'bg-light text-dark border',
        };
        $receiptStatusClass = fn ($code) => match ($code) {
            'draft' => 'bg-secondary',
            'posted' => 'bg-success',
            'voided' => 'bg-dark',
            default => 'bg-light text-dark border',
        };
    @endphp

    <div class="main-content procurement-po-detail-page">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ $errors->first() }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h2 class="h4 mb-1 font-weight-bold">
                    <i class="fa fa-file-invoice me-2"></i>Detail PO {{ $procurementPlan->plan_number }}
                </h2>
                <p class="text-muted small mb-0">Purchase order yang digenerate dari procurement plan ini, dikelompokkan per supplier.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('warehouse/procurement-plan') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-arrow-left me-1"></i>Kembali
                </a>
                <a href="{{ route('warehouse/procurement-plan/detail', $procurementPlan->id) }}" class="btn btn-outline-primary btn-sm">
                    <i class="fa fa-clipboard-check me-1"></i>Detail Plan
                </a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-4">
                <div class="card shadow-sm po-info-card po-border-primary h-100">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fa fa-info-circle me-2"></i>Plan Info</h5>
                    </div>
                    <div class="card-body">
                        <dl class="po-info-list mb-0">
                            <div>
                                <dt>Plan Number</dt>
                                <dd>{{ $procurementPlan->plan_number }}</dd>
                            </div>
                            <div>
                                <dt>Planning Location</dt>
                                <dd>{{ optional($procurementPlan->planningLocation)->name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt>Status Plan</dt>
                                <dd>{{ optional($procurementPlan->status)->name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt>Planned At</dt>
                                <dd>{{ optional($procurementPlan->planned_at)->format('d M Y H:i') ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card shadow-sm po-info-card po-border-success h-100">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fa fa-chart-pie me-2"></i>PO Summary</h5>
                    </div>
                    <div class="card-body">
                        <dl class="po-info-list mb-0">
                            <div>
                                <dt>Total PO</dt>
                                <dd>{{ number_format($stats['total_purchase_orders'] ?? 0, 0, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt>Total Supplier</dt>
                                <dd>{{ number_format($stats['total_suppliers'] ?? 0, 0, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt>Total Item</dt>
                                <dd>{{ number_format($stats['total_items'] ?? 0, 0, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt>Estimasi Nilai</dt>
                                <dd>{{ $formatMoney($stats['estimated_total'] ?? 0) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card shadow-sm po-info-card po-border-info h-100">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fa fa-truck-loading me-2"></i>Progress</h5>
                    </div>
                    <div class="card-body">
                        <dl class="po-info-list mb-0">
                            <div>
                                <dt>Qty Ordered</dt>
                                <dd>{{ $formatQty($stats['qty_base_ordered'] ?? 0) }}</dd>
                            </div>
                            <div>
                                <dt>Qty Received</dt>
                                <dd>{{ $formatQty($stats['qty_base_received'] ?? 0) }}</dd>
                            </div>
                            <div>
                                <dt>Penerimaan</dt>
                                <dd>{{ number_format($stats['total_receipts'] ?? 0, 0, ',', '.') }} dokumen</dd>
                            </div>
                            <div>
                                <dt>Cancellation</dt>
                                <dd>{{ number_format($stats['total_cancellations'] ?? 0, 0, ',', '.') }} dokumen</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        @forelse ($purchaseOrders as $purchaseOrder)
            @php
                $poItems = $purchaseOrder->items;
                $poTotal = $poItems->sum(fn ($item) => (float) ($item->subtotal ?? 0));
                $qtyOrdered = $poItems->sum(fn ($item) => (float) $item->qty_base_ordered);
                $qtyReceived = $poItems->sum(fn ($item) => (float) $item->qty_base_received);
                $poStatusCode = optional($purchaseOrder->status)->code;
            @endphp

            <div class="card shadow-sm po-card mb-4">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <h5 class="mb-0">{{ $purchaseOrder->po_number }}</h5>
                                <span class="badge {{ $poStatusClass($poStatusCode) }}">{{ optional($purchaseOrder->status)->name ?? '-' }}</span>
                            </div>
                            <div class="text-muted small">
                                {{ optional($purchaseOrder->supplier)->name ?? '-' }}
                                @if ($purchaseOrder->supplier?->primaryContact)
                                    - {{ $purchaseOrder->supplier->primaryContact->phone ?? $purchaseOrder->supplier->primaryContact->email ?? $purchaseOrder->supplier->primaryContact->name }}
                                @endif
                            </div>
                        </div>
                        <div class="po-card-summary">
                            <span>{{ $poItems->count() }} item</span>
                            <span>{{ $formatQty($qtyOrdered) }} ordered</span>
                            <span>{{ $formatQty($qtyReceived) }} received</span>
                            <strong>{{ $formatMoney($poTotal) }}</strong>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6 col-xl-3">
                            <div class="po-mini-box">
                                <span>Order Date</span>
                                <strong>{{ optional($purchaseOrder->order_date)->format('d M Y') ?? '-' }}</strong>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="po-mini-box">
                                <span>Expected Delivery</span>
                                <strong>{{ optional($purchaseOrder->expected_delivery_date)->format('d M Y') ?? '-' }}</strong>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="po-mini-box">
                                <span>Receiving Location</span>
                                <strong>{{ optional($purchaseOrder->receivingLocation)->name ?? '-' }}</strong>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="po-mini-box">
                                <span>Requested By</span>
                                <strong>{{ optional($purchaseOrder->requestedBy)->name ?? '-' }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="po-section-title">Item PO</div>
                    <div class="po-table-scroll mb-4">
                        <table class="table table-hover table-sm mb-0 align-middle po-items-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Bahan Baku</th>
                                    <th class="text-end">Qty Order</th>
                                    <th class="text-end">Qty Base</th>
                                    <th class="text-end">Received</th>
                                    <th class="text-end">Rejected</th>
                                    <th class="text-end">Cancelled</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($poItems as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ optional($item->rawMaterial)->name ?? '-' }}</div>
                                            <small class="text-muted">{{ optional($item->rawMaterial)->code ?? 'Tanpa kode' }}</small>
                                        </td>
                                        <td class="text-end">{{ $formatQty($item->qty_ordered) }} <small class="text-muted">{{ optional($item->unit)->symbol ?: optional($item->unit)->name }}</small></td>
                                        <td class="text-end">{{ $formatQty($item->qty_base_ordered) }}</td>
                                        <td class="text-end">{{ $formatQty($item->qty_base_received) }}</td>
                                        <td class="text-end">{{ $formatQty($item->qty_base_rejected) }}</td>
                                        <td class="text-end">{{ $formatQty($item->qty_base_cancelled) }}</td>
                                        <td class="text-end">{{ $formatMoney($item->unit_price) }}</td>
                                        <td class="text-end fw-semibold">{{ $formatMoney($item->subtotal) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">Belum ada item PO.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="po-section-title">Penerimaan & Surat Jalan</div>
                    <div class="po-table-scroll mb-4">
                        <table class="table table-hover table-sm mb-0 align-middle po-receipts-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Receipt</th>
                                    <th>Status</th>
                                    <th>Surat Jalan</th>
                                    <th>Invoice Supplier</th>
                                    <th>Received At</th>
                                    <th>Received By</th>
                                    <th class="text-end">Item</th>
                                    <th class="text-end">Accepted Base</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchaseOrder->receipts as $index => $receipt)
                                    @php
                                        $receiptStatusCode = optional($receipt->status)->code;
                                        $acceptedBase = $receipt->items->sum(fn ($item) => (float) $item->qty_base_accepted);
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $receipt->receipt_number ?? '-' }}</td>
                                        <td><span class="badge {{ $receiptStatusClass($receiptStatusCode) }}">{{ optional($receipt->status)->name ?? '-' }}</span></td>
                                        <td>{{ $receipt->delivery_note_number ?? '-' }}</td>
                                        <td>{{ $receipt->supplier_invoice_number ?? '-' }}</td>
                                        <td>{{ optional($receipt->received_at)->format('d M Y H:i') ?? '-' }}</td>
                                        <td>{{ optional($receipt->receivedBy)->name ?? '-' }}</td>
                                        <td class="text-end">{{ $receipt->items->count() }}</td>
                                        <td class="text-end">{{ $formatQty($acceptedBase) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">Belum ada penerimaan untuk PO ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="po-section-title">Cancellation</div>
                    <div class="po-table-scroll">
                        <table class="table table-hover table-sm mb-0 align-middle po-cancellations-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nomor Cancellation</th>
                                    <th>Pihak</th>
                                    <th>Cancelled At</th>
                                    <th>Cancelled By</th>
                                    <th>Reason</th>
                                    <th class="text-end">Item</th>
                                    <th class="text-end">Cancelled Base</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchaseOrder->cancellations as $index => $cancellation)
                                    @php
                                        $cancelledBase = $cancellation->items->sum(fn ($item) => (float) $item->qty_base_cancelled);
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $cancellation->cancellation_number ?? '-' }}</td>
                                        <td>{{ ucfirst($cancellation->cancelled_by_party) }}</td>
                                        <td>{{ optional($cancellation->cancelled_at)->format('d M Y H:i') ?? '-' }}</td>
                                        <td>{{ optional($cancellation->cancelledBy)->name ?? '-' }}</td>
                                        <td>{{ $cancellation->reason ?? '-' }}</td>
                                        <td class="text-end">{{ $cancellation->items->count() }}</td>
                                        <td class="text-end">{{ $formatQty($cancelledBase) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">Belum ada cancellation untuk PO ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @empty
            <div class="card shadow-sm po-card">
                <div class="card-body text-center text-muted py-5">
                    Belum ada purchase order yang digenerate dari procurement plan ini.
                </div>
            </div>
        @endforelse
    </div>

    @push('css')
        <style>
            .procurement-po-detail-page .card {
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
            }

            .po-info-card,
            .po-card {
                overflow: hidden;
            }

            .po-info-card::before,
            .po-card::before {
                content: "";
                display: block;
                height: 4px;
            }

            .po-border-primary::before,
            .po-card::before {
                background: #2f6fcf;
            }

            .po-border-success::before {
                background: #15965f;
            }

            .po-border-info::before {
                background: #13a2b8;
            }

            .po-info-list {
                display: grid;
                gap: 10px;
            }

            .po-info-list > div {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 16px;
                padding-bottom: 10px;
                border-bottom: 1px solid rgba(18, 38, 63, 0.08);
            }

            .po-info-list > div:last-child {
                border-bottom: 0;
                padding-bottom: 0;
            }

            .po-info-list dt {
                color: #667085;
                font-size: 12px;
                font-weight: 600;
                min-width: 130px;
            }

            .po-info-list dd {
                margin-bottom: 0;
                text-align: right;
                color: #1f2937;
                font-weight: 600;
                overflow-wrap: anywhere;
            }

            .po-card-summary {
                display: flex;
                flex-wrap: wrap;
                justify-content: flex-end;
                gap: 8px;
                color: #475467;
                font-size: 12px;
            }

            .po-card-summary span,
            .po-card-summary strong {
                display: inline-flex;
                align-items: center;
                min-height: 28px;
                padding: 4px 9px;
                border: 1px solid rgba(18, 38, 63, 0.1);
                border-radius: 8px;
                background: #f8fafc;
            }

            .po-mini-box {
                height: 100%;
                padding: 12px;
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
                background: #fbfcfe;
            }

            .po-mini-box span {
                display: block;
                color: #667085;
                font-size: 12px;
                font-weight: 600;
                margin-bottom: 4px;
            }

            .po-mini-box strong {
                color: #1f2937;
                font-size: 13px;
                overflow-wrap: anywhere;
            }

            .po-section-title {
                margin-bottom: 8px;
                color: #1f2937;
                font-size: 13px;
                font-weight: 800;
            }

            .po-table-scroll {
                overflow-x: auto;
            }

            .po-items-table,
            .po-receipts-table,
            .po-cancellations-table {
                min-width: 1020px;
            }

            .po-items-table th,
            .po-items-table td,
            .po-receipts-table th,
            .po-receipts-table td,
            .po-cancellations-table th,
            .po-cancellations-table td {
                white-space: nowrap;
            }

            .po-items-table td:nth-child(2),
            .po-cancellations-table td:nth-child(6) {
                white-space: normal;
                min-width: 220px;
                max-width: 360px;
                overflow-wrap: anywhere;
            }

            @media (max-width: 768px) {
                .po-info-list > div {
                    display: block;
                }

                .po-info-list dd {
                    margin-top: 4px;
                    text-align: left;
                }

                .po-card-summary {
                    justify-content: flex-start;
                }
            }
        </style>
    @endpush
@endsection

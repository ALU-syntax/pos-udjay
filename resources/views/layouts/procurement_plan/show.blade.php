@extends('layouts.app')
@section('content')
    @php
        $statusCode = optional($procurementPlan->status)->code;
        $isDraft = $statusCode === 'draft';
        $statusClass = match ($statusCode) {
            'draft' => 'bg-secondary',
            'reviewed' => 'bg-primary',
            'approved' => 'bg-success',
            'converted_to_po' => 'bg-info text-dark',
            'cancelled' => 'bg-dark',
            default => 'bg-light text-dark border',
        };
        $formatQty = fn ($value) => number_format((float) $value, 5, ',', '.');
        $formatMoney = fn ($value) => $value === null ? '-' : number_format((float) $value, 2, ',', '.');
    @endphp

    <div class="main-content procurement-plan-detail-page">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h2 class="h4 mb-1 font-weight-bold">
                    <i class="fa fa-clipboard-check me-2"></i>{{ $procurementPlan->plan_number }}
                </h2>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('warehouse/procurement-plan') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-arrow-left me-1"></i>Kembali
                </a>
                @if ($isDraft)
                    <a href="{{ route('warehouse/procurement-plan/destroy', $procurementPlan->id) }}" class="btn btn-outline-danger btn-sm delete-procurement-plan-detail">
                        <i class="fa fa-trash me-1"></i>Hapus
                    </a>
                @endif
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-4">
                <div class="card shadow-sm pp-info-card pp-border-primary h-100">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fa fa-info-circle me-2"></i>Plan Info</h5>
                    </div>
                    <div class="card-body">
                        <dl class="pp-info-list mb-0">
                            <div>
                                <dt>Plan Number</dt>
                                <dd>{{ $procurementPlan->plan_number }}</dd>
                            </div>
                            <div>
                                <dt>Planning Location</dt>
                                <dd>{{ optional($procurementPlan->planningLocation)->name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt>Status</dt>
                                <dd><span class="badge {{ $statusClass }}">{{ optional($procurementPlan->status)->name ?? '-' }}</span></dd>
                            </div>
                            <div class="pp-notes-row">
                                <dt>Notes</dt>
                                <dd>{{ $procurementPlan->notes ?: '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card shadow-sm pp-info-card pp-border-info h-100">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fa fa-user-clock me-2"></i>User & Time Info</h5>
                    </div>
                    <div class="card-body">
                        <dl class="pp-info-list mb-0">
                            <div>
                                <dt>Planned By</dt>
                                <dd>{{ optional($procurementPlan->plannedBy)->name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt>Planned At</dt>
                                <dd>{{ optional($procurementPlan->planned_at)->format('d M Y H:i') ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt>Approved By</dt>
                                <dd>{{ optional($procurementPlan->approvedBy)->name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt>Approved At</dt>
                                <dd>{{ optional($procurementPlan->approved_at)->format('d M Y H:i') ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card shadow-sm pp-info-card pp-border-success h-100">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fa fa-chart-pie me-2"></i>Procurement Summary</h5>
                    </div>
                    <div class="card-body">
                        <dl class="pp-info-list mb-0">
                            <div>
                                <dt>Total Items</dt>
                                <dd>{{ number_format($stats['total_items'] ?? 0, 0, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt>Total Sources</dt>
                                <dd>{{ number_format($stats['total_sources'] ?? 0, 0, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt>Required Base</dt>
                                <dd>{{ $formatQty($stats['qty_required_base'] ?? 0) }}</dd>
                            </div>
                            <div>
                                <dt>To Purchase Base</dt>
                                <dd>{{ $formatQty($stats['qty_to_purchase_base'] ?? 0) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm pp-items-card mb-4">
            <div class="card-header bg-white border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-0">Item Procurement</h5>
                    <small class="text-muted">Item sudah digabung berdasarkan bahan baku dari source Request Order yang dipilih.</small>
                </div>
                <input id="planItemSearch" type="search" class="form-control form-control-sm pp-detail-search"
                    placeholder="Cari bahan">
            </div>
            <div class="card-body p-0">
                <div class="pp-items-scroll">
                    <table id="procurement-plan-items-table" class="table table-hover table-sm mb-0 align-middle pp-items-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Bahan Baku</th>
                                <th>Supplier</th>
                                <th class="text-end">Required Base</th>
                                <th class="text-end">Available Base</th>
                                <th class="text-end">Shortage Base</th>
                                <th class="text-end">To Purchase</th>
                                <th class="text-end">Est. Unit Price</th>
                                <th class="text-end">Est. Subtotal</th>
                                <th>Source</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($procurementPlan->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ optional($item->rawMaterial)->name ?? '-' }}</div>
                                        <small class="text-muted">{{ optional($item->rawMaterial)->code ?? 'Tanpa kode' }} · {{ optional($item->unit)->symbol ?: optional($item->unit)->name }}</small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ optional($item->supplier)->name ?? '-' }}</div>
                                        <small class="text-muted">
                                            {{ optional($item->supplierRawMaterial)->supplier_material_name ?: 'Nama supplier material belum diisi' }}
                                            @if ($item->supplierRawMaterial?->purchaseUnit)
                                                · {{ $item->supplierRawMaterial->purchaseUnit->symbol ?: $item->supplierRawMaterial->purchaseUnit->name }}
                                            @endif
                                        </small>
                                    </td>
                                    <td class="text-end">{{ $formatQty($item->qty_required_base) }}</td>
                                    <td class="text-end">{{ $formatQty($item->qty_available_base) }}</td>
                                    <td class="text-end">{{ $formatQty($item->qty_shortage_base) }}</td>
                                    <td class="text-end fw-semibold">{{ $formatQty($item->qty_to_purchase_base) }}</td>
                                    <td class="text-end">{{ $formatMoney($item->estimated_unit_price) }}</td>
                                    <td class="text-end">{{ $formatMoney($item->estimated_subtotal) }}</td>
                                    <td>
                                        <div class="pp-source-stack">
                                            @foreach ($item->sources as $source)
                                                @php
                                                    $requestItem = $source->rawMaterialRequestItem;
                                                    $requestOrder = $requestItem?->rawMaterialRequest;
                                                @endphp
                                                <span class="badge bg-light text-dark border">
                                                    {{ $requestOrder?->request_number ?? '-' }}:
                                                    {{ $formatQty($source->qty_base_allocated) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">Belum ada item procurement plan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm pp-items-card">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">Detail Source Request Order</h5>
            </div>
            <div class="card-body p-0">
                <div class="pp-items-scroll">
                    <table class="table table-hover table-sm mb-0 align-middle pp-source-detail-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Request Order</th>
                                <th>Pemohon</th>
                                <th>Fulfillment</th>
                                <th>Bahan Baku</th>
                                <th class="text-end">Qty Approved</th>
                                <th class="text-end">Qty Allocated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $sourceIndex = 1; @endphp
                            @forelse ($procurementPlan->items as $item)
                                @foreach ($item->sources as $source)
                                    @php
                                        $requestItem = $source->rawMaterialRequestItem;
                                        $requestOrder = $requestItem?->rawMaterialRequest;
                                    @endphp
                                    <tr>
                                        <td>{{ $sourceIndex++ }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $requestOrder?->request_number ?? '-' }}</div>
                                            <small class="text-muted">{{ optional($requestOrder?->needed_at)->format('d M Y') ?? '-' }}</small>
                                        </td>
                                        <td>{{ optional($requestOrder?->requesterInventory)->name ?? '-' }}</td>
                                        <td>{{ optional($requestOrder?->fulfillmentInventory)->name ?? 'Belum ditentukan' }}</td>
                                        <td>{{ optional($requestItem?->rawMaterial)->name ?? optional($item->rawMaterial)->name ?? '-' }}</td>
                                        <td class="text-end">{{ $formatQty($requestItem?->qty_base_approved ?? 0) }}</td>
                                        <td class="text-end">{{ $formatQty($source->qty_base_allocated) }}</td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Belum ada source request order.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('js')
        <script>
            $(function() {
                const hasItemRows = $('#procurement-plan-items-table tbody td[colspan]').length === 0;

                if (hasItemRows) {
                    const itemTable = $('#procurement-plan-items-table').DataTable({
                        pageLength: 10,
                        lengthChange: false,
                        autoWidth: false,
                        order: [
                            [1, 'asc']
                        ],
                        dom: 'rtip',
                        language: {
                            emptyTable: 'Belum ada item procurement plan.',
                            info: 'Menampilkan _START_ - _END_ dari _TOTAL_ item',
                            infoEmpty: 'Belum ada item',
                            paginate: {
                                previous: 'Sebelumnya',
                                next: 'Berikutnya'
                            }
                        }
                    });

                    $('#planItemSearch').on('keyup search', function() {
                        itemTable.search(this.value).draw();
                    });
                }

                $('.main-content').on('click', '.delete-procurement-plan-detail', function(e) {
                    e.preventDefault();
                    const url = this.href;

                    Swal.fire({
                        title: 'Hapus procurement plan?',
                        text: 'Procurement plan draft dan source allocation-nya akan dihapus.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, hapus'
                    }).then((result) => {
                        if (!result.isConfirmed) {
                            return;
                        }

                        $.ajax({
                            url: url,
                            method: 'DELETE',
                            success: function(res) {
                                showToast(res.status, res.message);
                                window.location.href = "{{ route('warehouse/procurement-plan') }}";
                            },
                            error: function(err) {
                                showToast('error', err.responseJSON?.message || 'Gagal menghapus procurement plan');
                            }
                        });
                    });
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            .procurement-plan-detail-page .card {
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
            }

            .pp-info-card {
                overflow: hidden;
            }

            .pp-info-card::before {
                content: "";
                display: block;
                height: 4px;
            }

            .pp-border-primary::before {
                background: #2f6fcf;
            }

            .pp-border-info::before {
                background: #13a2b8;
            }

            .pp-border-success::before {
                background: #15965f;
            }

            .pp-info-list {
                display: grid;
                gap: 10px;
            }

            .pp-info-list > div {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 16px;
                padding-bottom: 10px;
                border-bottom: 1px solid rgba(18, 38, 63, 0.08);
            }

            .pp-info-list > div:last-child {
                border-bottom: 0;
                padding-bottom: 0;
            }

            .pp-info-list dt {
                color: #667085;
                font-size: 12px;
                font-weight: 600;
                min-width: 130px;
            }

            .pp-info-list dd {
                margin-bottom: 0;
                text-align: right;
                color: #1f2937;
                font-weight: 600;
                overflow-wrap: anywhere;
            }

            .pp-notes-row {
                display: block !important;
            }

            .pp-notes-row dd {
                margin-top: 4px;
                text-align: left;
                font-weight: 500;
            }

            .procurement-plan-detail-page .pp-detail-search {
                min-width: 220px;
            }

            .pp-items-scroll {
                padding: 16px 0;
                overflow-x: auto;
            }

            .pp-items-table,
            .pp-source-detail-table {
                min-width: 1120px;
            }

            .pp-items-table th,
            .pp-items-table td,
            .pp-source-detail-table th,
            .pp-source-detail-table td {
                white-space: nowrap;
            }

            .pp-items-table td:nth-child(2),
            .pp-items-table td:nth-child(3),
            .pp-items-table td:nth-child(10),
            .pp-source-detail-table td:nth-child(2),
            .pp-source-detail-table td:nth-child(5) {
                white-space: normal;
                min-width: 220px;
                max-width: 380px;
                overflow-wrap: anywhere;
            }

            .pp-source-stack {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
            }

            @media (max-width: 768px) {
                .procurement-plan-detail-page .pp-detail-search {
                    min-width: 100%;
                }

                .pp-info-list > div {
                    display: block;
                }

                .pp-info-list dd {
                    margin-top: 4px;
                    text-align: left;
                }
            }
        </style>
    @endpush
@endsection

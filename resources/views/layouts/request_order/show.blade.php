@extends('layouts.app')
@section('content')
    @php
        $statusCode = optional($requestOrder->status)->code;
        $isDraft = $statusCode === 'draft';
        $isSubmitted = $statusCode === 'submitted';
        $statusClass = match ($statusCode) {
            'draft' => 'bg-secondary',
            'submitted' => 'bg-primary',
            'approved', 'fulfilled' => 'bg-success',
            'waiting_stock', 'partially_fulfilled' => 'bg-warning text-dark',
            'waiting_procurement' => 'bg-info text-dark',
            'rejected' => 'bg-danger',
            'cancelled' => 'bg-dark',
            default => 'bg-light text-dark border',
        };
        $formatQty = fn ($value) => number_format((float) $value, 5, ',', '.');
    @endphp

    <div class="main-content request-order-detail-page">
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
                    <i class="fa fa-clipboard-list me-2"></i>{{ $requestOrder->request_number }}
                </h2>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('warehouse/request-order') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-arrow-left me-1"></i>Kembali
                </a>
                @if ($isDraft)
                    <a href="{{ route('warehouse/request-order/edit', $requestOrder->id) }}" class="btn btn-outline-primary btn-sm">
                        <i class="fa fa-edit me-1"></i>Edit
                    </a>
                    <a href="{{ route('warehouse/request-order/submit', $requestOrder->id) }}" class="btn btn-primary btn-sm submit-request-order-detail">
                        <i class="fa fa-paper-plane me-1"></i>Submit
                    </a>
                    <a href="{{ route('warehouse/request-order/destroy', $requestOrder->id) }}" class="btn btn-outline-danger btn-sm delete-request-order-detail">
                        <i class="fa fa-trash me-1"></i>Hapus
                    </a>
                @endif
                @if ($isSubmitted)
                    <button type="submit" form="approveRequestOrderForm" class="btn btn-success btn-sm">
                        <i class="fa fa-check-circle me-1"></i>Setujui
                    </button>
                @endif
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-4">
                <div class="card shadow-sm ro-info-card ro-border-primary h-100">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fa fa-info-circle me-2"></i>Request Info</h5>
                    </div>
                    <div class="card-body">
                        <dl class="ro-info-list mb-0">
                            <div>
                                <dt>Request Number</dt>
                                <dd>{{ $requestOrder->request_number }}</dd>
                            </div>
                            <div>
                                <dt>Requester Inventory</dt>
                                <dd>{{ optional($requestOrder->requesterInventory)->name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt>Fulfillment Location</dt>
                                <dd>{{ optional($requestOrder->fulfillmentLocation)->name ?? 'Belum ditentukan' }}</dd>
                            </div>
                            <div>
                                <dt>Needed At</dt>
                                <dd>{{ optional($requestOrder->needed_at)->format('d M Y') ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt>Status</dt>
                                <dd><span class="badge {{ $statusClass }}">{{ optional($requestOrder->status)->name ?? '-' }}</span></dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card shadow-sm ro-info-card ro-border-info h-100">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fa fa-user-clock me-2"></i>User & Time Info</h5>
                    </div>
                    <div class="card-body">
                        <dl class="ro-info-list mb-0">
                            <div>
                                <dt>Requested By</dt>
                                <dd>{{ optional($requestOrder->requestedBy)->name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt>Requested At</dt>
                                <dd>{{ optional($requestOrder->requested_at)->format('d M Y H:i') ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt>Approved By</dt>
                                <dd>{{ optional($requestOrder->approvedBy)->name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt>Approved At</dt>
                                <dd>{{ optional($requestOrder->approved_at)->format('d M Y H:i') ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card shadow-sm ro-info-card ro-border-success h-100">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0"><i class="fa fa-clipboard-check me-2"></i>Fulfillment Summary</h5>
                    </div>
                    <div class="card-body">
                        <dl class="ro-info-list mb-0">
                            <div>
                                <dt>Total Items</dt>
                                <dd>{{ number_format($stats['total_items'] ?? 0, 0, ',', '.') }}</dd>
                            </div>
                            <div>
                                <dt>Total Approved Qty Base</dt>
                                <dd>{{ $formatQty($stats['total_approved_qty_base'] ?? 0) }}</dd>
                            </div>
                            <div>
                                <dt>Total Fulfilled Qty Base</dt>
                                <dd>{{ $formatQty($stats['total_fulfilled_qty_base'] ?? 0) }}</dd>
                            </div>
                            <div class="ro-notes-row">
                                <dt>Notes</dt>
                                <dd>{{ $requestOrder->notes ?: '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        @if ($isSubmitted)
            <form id="approveRequestOrderForm" action="{{ route('warehouse/request-order/approve', $requestOrder->id) }}" method="POST">
                @csrf
        @endif

        <div class="card shadow-sm ro-items-card">
            <div class="card-header bg-white border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-0">{{ $isSubmitted ? 'Review Item Request' : 'Item Request' }}</h5>
                    <small class="text-muted">
                        {{ $isSubmitted ? 'Isi Qty Approved Base sesuai jumlah yang disetujui.' : 'Qty base dihitung dari satuan pilihan ke satuan dasar bahan baku.' }}
                    </small>
                </div>
                <input id="requestItemSearch" type="search" class="form-control form-control-sm ro-detail-search"
                    placeholder="Cari bahan">
            </div>
            <div class="card-body p-0">
                <div class="ro-items-scroll">
                    <table id="request-order-items-detail-table" class="table table-hover table-sm mb-0 align-middle ro-items-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Bahan Baku</th>
                                <th class="text-end">Qty Request</th>
                                <th class="text-end">Qty Base</th>
                                <th class="text-end">Approved</th>
                                <th class="text-end">Fulfilled</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($requestOrder->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ optional($item->rawMaterial)->name ?? '-' }}</div>
                                        <small class="text-muted">{{ optional($item->rawMaterial)->code ?? 'Tanpa kode' }}</small>
                                    </td>
                                    <td class="text-end">{{ $formatQty($item->qty_requested) }}</td>
                                    <td class="text-end">
                                        {{ $formatQty($item->qty_base_requested) }}
                                        <small class="text-muted">{{ optional(optional($item->rawMaterial)->baseUnit)->symbol ?: optional(optional($item->rawMaterial)->baseUnit)->name }}</small>
                                    </td>
                                    <td class="text-end">
                                        @if ($isSubmitted)
                                            @php
                                                $approvalField = 'items.' . $item->id . '.qty_base_approved';
                                                $approvalValue = old($approvalField, $item->qty_base_approved ?? $item->qty_base_requested);
                                                $maxApprovalValue = number_format((float) $item->qty_base_requested, 5, '.', '');
                                            @endphp
                                            <input type="number" name="items[{{ $item->id }}][qty_base_approved]"
                                                class="form-control form-control-sm text-end ro-approval-input @error($approvalField) is-invalid @enderror"
                                                value="{{ $approvalValue }}" min="0" max="{{ $maxApprovalValue }}" step="0.00001" required>
                                            @error($approvalField)
                                                <div class="invalid-feedback text-start">{{ $message }}</div>
                                            @enderror
                                        @else
                                            {{ $item->qty_base_approved !== null ? $formatQty($item->qty_base_approved) : '-' }}
                                        @endif
                                    </td>
                                    <td class="text-end">{{ $formatQty($item->qty_base_fulfilled) }}</td>
                                    <td>{{ $item->notes ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Belum ada item request.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($isSubmitted)
                <div class="card-footer bg-white d-flex justify-content-end">
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-check-circle me-1"></i>Setujui Request Order
                    </button>
                </div>
            @endif
        </div>

        @if ($isSubmitted)
            </form>
        @endif
    </div>

    @push('js')
        <script>
            $(function() {
                const hasItemRows = $('#request-order-items-detail-table tbody td[colspan]').length === 0;
                const isSubmitted = @json($isSubmitted);

                if (hasItemRows && isSubmitted) {
                    $('#requestItemSearch').on('keyup search', function() {
                        const keyword = this.value.toLowerCase();

                        $('#request-order-items-detail-table tbody tr').each(function() {
                            $(this).toggle($(this).text().toLowerCase().includes(keyword));
                        });
                    });
                } else if (hasItemRows) {
                    const itemTable = $('#request-order-items-detail-table').DataTable({
                        pageLength: 10,
                        lengthChange: false,
                        autoWidth: false,
                        order: [
                            [1, 'asc']
                        ],
                        dom: 'rtip',
                        language: {
                            emptyTable: 'Belum ada item request.',
                            info: 'Menampilkan _START_ - _END_ dari _TOTAL_ item',
                            infoEmpty: 'Belum ada item',
                            paginate: {
                                previous: 'Sebelumnya',
                                next: 'Berikutnya'
                            }
                        }
                    });

                    $('#requestItemSearch').on('keyup search', function() {
                        itemTable.search(this.value).draw();
                    });
                }

                $('.main-content').on('click', '.submit-request-order-detail', function(e) {
                    e.preventDefault();
                    const url = this.href;

                    Swal.fire({
                        title: 'Submit request order?',
                        text: 'Request order akan masuk tahap review.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, submit'
                    }).then((result) => {
                        if (!result.isConfirmed) {
                            return;
                        }

                        $.ajax({
                            url: url,
                            method: 'POST',
                            success: function(res) {
                                showToast(res.status, res.message);
                                location.reload();
                            },
                            error: function(err) {
                                showToast('error', err.responseJSON?.message || 'Gagal submit request order');
                            }
                        });
                    });
                });

                $('.main-content').on('click', '.delete-request-order-detail', function(e) {
                    e.preventDefault();
                    const url = this.href;

                    Swal.fire({
                        title: 'Hapus request order?',
                        text: 'Request order draft akan dihapus dari daftar.',
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
                                window.location.href = "{{ route('warehouse/request-order') }}";
                            },
                            error: function(err) {
                                showToast('error', err.responseJSON?.message || 'Gagal menghapus request order');
                            }
                        });
                    });
                });

                $('#approveRequestOrderForm').on('submit', function(e) {
                    e.preventDefault();
                    const form = this;

                    Swal.fire({
                        title: 'Setujui request order?',
                        text: 'Qty approved akan disimpan dan status request order berubah menjadi approved.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#15965f',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, setujui'
                    }).then((result) => {
                        if (!result.isConfirmed) {
                            return;
                        }

                        HTMLFormElement.prototype.submit.call(form);
                    });
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            .request-order-detail-page .card {
                border: 1px solid rgba(18, 38, 63, 0.08);
                border-radius: 8px;
            }

            .ro-info-card {
                overflow: hidden;
            }

            .ro-info-card::before {
                content: "";
                display: block;
                height: 4px;
            }

            .ro-border-primary::before {
                background: #2f6fcf;
            }

            .ro-border-info::before {
                background: #13a2b8;
            }

            .ro-border-success::before {
                background: #15965f;
            }

            .ro-info-list {
                display: grid;
                gap: 10px;
            }

            .ro-info-list > div {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 16px;
                padding-bottom: 10px;
                border-bottom: 1px solid rgba(18, 38, 63, 0.08);
            }

            .ro-info-list > div:last-child {
                border-bottom: 0;
                padding-bottom: 0;
            }

            .ro-info-list dt {
                color: #667085;
                font-size: 12px;
                font-weight: 600;
                min-width: 130px;
            }

            .ro-info-list dd {
                margin-bottom: 0;
                text-align: right;
                color: #1f2937;
                font-weight: 600;
                overflow-wrap: anywhere;
            }

            .ro-notes-row {
                display: block !important;
            }

            .ro-notes-row dd {
                margin-top: 4px;
                text-align: left;
                font-weight: 500;
            }

            .request-order-detail-page .ro-detail-search {
                min-width: 220px;
            }

            .ro-items-scroll {
                padding: 16px 0;
                overflow-x: auto;
            }

            .ro-items-table {
                min-width: 960px;
            }

            .ro-items-table th,
            .ro-items-table td {
                white-space: nowrap;
            }

            .ro-items-table td:nth-child(2),
            .ro-items-table td:nth-child(7) {
                white-space: normal;
                min-width: 220px;
                max-width: 360px;
                overflow-wrap: anywhere;
                word-break: normal;
            }

            .ro-approval-input {
                display: inline-block;
                width: 150px;
            }

            @media (max-width: 768px) {
                .request-order-detail-page .ro-detail-search {
                    min-width: 100%;
                }

                .ro-info-list > div {
                    display: block;
                }

                .ro-info-list dd {
                    margin-top: 4px;
                    text-align: left;
                }
            }
        </style>
    @endpush
@endsection

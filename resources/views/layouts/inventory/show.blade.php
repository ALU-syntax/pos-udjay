@extends('layouts.app')
@section('content')
    <div class="main-content inventory-detail-page">
        <div class="mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h2 class="h4 mb-1 font-weight-bold">
                        <i class="fa fa-warehouse me-2"></i>{{ $inventoryLocation->name }}
                    </h2>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="badge bg-light text-dark border">{{ $inventoryLocation->code ?: 'Tanpa kode' }}</span>
                        @if ($inventoryLocation->type)
                            <span class="badge inventory-type-badge">{{ $inventoryLocation->type->name }}</span>
                        @endif
                        @if ($inventoryLocation->is_active)
                            <span class="badge bg-success">Aktif</span>
                        @else
                            <span class="badge bg-secondary">Tidak Aktif</span>
                        @endif
                    </div>
                    <p class="text-muted small mt-2 mb-0">Kelola bahan baku yang tersimpan di lokasi inventory ini secara real-time.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('warehouse/inventory') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa fa-arrow-left me-1"></i>Kembali
                    </a>
                    @can('create warehouse/inventory')
                        <a href="{{ route('warehouse/inventory/stock-balances/create', $inventoryLocation->id) }}" class="btn btn-primary btn-sm action">
                            <i class="fa fa-plus me-1"></i>Tambah Bahan Baku
                        </a>
                    @endcan
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6 col-xl-3">
                    <div class="card border-start border-primary h-100 shadow-sm">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Total Bahan</p>
                            <h4 class="mb-0 fw-bold">{{ number_format($stockStats['total_materials'] ?? 0, 0, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card border-start border-success h-100 shadow-sm">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Qty Available</p>
                            <h4 class="mb-0 fw-bold text-success">{{ number_format($stockStats['total_available'] ?? 0, 2, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card border-start border-warning h-100 shadow-sm">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Qty Reserved</p>
                            <h4 class="mb-0 fw-bold text-warning">{{ number_format($stockStats['total_reserved'] ?? 0, 2, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card border-start border-info h-100 shadow-sm">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Qty Free</p>
                            <h4 class="mb-0 fw-bold text-info">{{ number_format($stockStats['total_free'] ?? 0, 2, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">Informasi Lokasi</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-5 text-muted">Nama</dt>
                            <dd class="col-7">{{ $inventoryLocation->name }}</dd>

                            <dt class="col-5 text-muted">Kode</dt>
                            <dd class="col-7">{{ $inventoryLocation->code ?: '-' }}</dd>

                            <dt class="col-5 text-muted">Tipe</dt>
                            <dd class="col-7">{{ optional($inventoryLocation->type)->name ?: '-' }}</dd>

                            <dt class="col-5 text-muted">Parent</dt>
                            <dd class="col-7">{{ optional($inventoryLocation->parent)->name ?: '-' }}</dd>

                            <dt class="col-5 text-muted">Outlet</dt>
                            <dd class="col-7">{{ optional($inventoryLocation->outlet)->name ?: 'Semua outlet' }}</dd>

                            <dt class="col-5 text-muted">Brand</dt>
                            <dd class="col-7">{{ optional($inventoryLocation->brand)->name ?: 'Semua brand' }}</dd>

                            <dt class="col-5 text-muted">Update Terakhir</dt>
                            <dd class="col-7">{{ optional($inventoryLocation->updated_at)->format('d M Y H:i') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h5 class="mb-0">Stok Bahan Baku</h5>
                            <small class="text-muted">Kelola stok tersedia dan stok teralokasi per bahan baku.</small>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <input id="stockSearch" type="search" class="form-control form-control-sm stock-search-input"
                                placeholder="Cari bahan baku">
                            <select id="stockFilterStatus" class="form-select form-select-sm stock-status-select">
                                <option value="">Semua Status</option>
                                <option value="Ready">Ready</option>
                                <option value="Partial Reserved">Partial Reserved</option>
                                <option value="Fully Reserved">Fully Reserved</option>
                                <option value="Out of Stock">Out of Stock</option>
                            </select>
                            @can('create warehouse/inventory')
                                <a href="{{ route('warehouse/inventory/stock-balances/create', $inventoryLocation->id) }}"
                                    class="btn btn-primary btn-sm action">
                                    <i class="fa fa-plus me-1"></i> Bahan Baku
                                </a>
                            @endcan
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive mt-4 mb-3">
                            @if ($stockBalances->isNotEmpty())
                                <table id="inventory-stock-table" class="table table-hover table-sm mb-0 align-middle">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Bahan Baku</th>
                                            <th>Kategori</th>
                                            <th>Satuan</th>
                                            <th>Penyimpanan</th>
                                            <th class="text-end">Available</th>
                                            <th class="text-end">Reserved</th>
                                            <th class="text-end">Free</th>
                                            <th>Status</th>
                                            <th class="text-center"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($stockBalances as $index => $item)
                                            @php
                                                $available = (float) $item->qty_available;
                                                $reserved = (float) $item->qty_reserved;
                                                $free = $available - $reserved;
                                                $status = 'Ready';
                                                $statusClass = 'bg-success';

                                                if ($available <= 0) {
                                                    $status = 'Out of Stock';
                                                    $statusClass = 'bg-danger';
                                                } elseif ($reserved >= $available && $available > 0) {
                                                    $status = 'Fully Reserved';
                                                    $statusClass = 'bg-secondary';
                                                } elseif ($reserved > 0) {
                                                    $status = 'Partial Reserved';
                                                    $statusClass = 'bg-warning text-dark';
                                                }
                                            @endphp
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="fw-semibold">{{ optional($item->rawMaterial)->name ?? '-' }}</div>
                                                    <small class="text-muted">{{ optional($item->rawMaterial)->code ?? 'Tanpa kode' }}</small>
                                                </td>
                                                <td>{{ optional(optional($item->rawMaterial)->category)->name ?? '-' }}</td>
                                                <td>{{ optional(optional($item->rawMaterial)->baseUnit)->symbol ?: optional(optional($item->rawMaterial)->baseUnit)->name ?: '-' }}</td>
                                                <td>{{ optional(optional($item->rawMaterial)->storageType)->name ?? '-' }}</td>
                                                <td class="text-end">{{ number_format($available, 2, ',', '.') }}</td>
                                                <td class="text-end">{{ number_format($reserved, 2, ',', '.') }}</td>
                                                <td class="text-end">{{ number_format($free, 2, ',', '.') }}</td>
                                                <td>
                                                    <span class="badge {{ $statusClass }}">{{ $status }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group" role="group">
                                                        @can('update warehouse/inventory')
                                                            <a href="{{ route('warehouse/inventory/stock-balances/edit', [$inventoryLocation->id, $item->id]) }}"
                                                                class="btn btn-sm btn-outline-primary action">
                                                                Edit
                                                            </a>
                                                        @endcan
                                                        @can('delete warehouse/inventory')
                                                            <a href="{{ route('warehouse/inventory/stock-balances/destroy', [$inventoryLocation->id, $item->id]) }}"
                                                                class="btn btn-sm btn-outline-danger delete-stock">
                                                                Hapus
                                                            </a>
                                                        @endcan
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="p-4 text-center text-muted">
                                    Belum ada bahan baku di lokasi ini. Tambahkan bahan baku pertama untuk memulai kontrol stok.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('js')
        <script>
            let stockTable;

            $(function() {
                handleAction(null, null, function() {
                    location.reload();
                });

                if ($('#inventory-stock-table').length) {
                    stockTable = $('#inventory-stock-table').DataTable({
                        pageLength: 10,
                        lengthChange: false,
                        autoWidth: false,
                        order: [
                            [1, 'asc']
                        ],
                        dom: 'rtip',
                        language: {
                            emptyTable: 'Belum ada stok bahan baku.',
                            info: 'Menampilkan _START_ - _END_ dari _TOTAL_ bahan',
                            infoEmpty: 'Belum ada bahan',
                            paginate: {
                                previous: 'Sebelumnya',
                                next: 'Berikutnya'
                            }
                        }
                    });

                    $('#stockSearch').on('keyup search', function() {
                        stockTable.search(this.value).draw();
                    });

                    $('#stockFilterStatus').on('change', function() {
                        stockTable.column(8).search(this.value).draw();
                    });
                }

                $('.main-content').on('click', '.delete-stock', function(e) {
                    e.preventDefault();
                    const url = this.href;

                    Swal.fire({
                        title: 'Hapus stok bahan baku?',
                        text: 'Data stok bahan baku pada lokasi ini akan dihapus.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, hapus'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: url,
                                method: 'DELETE',
                                success: function() {
                                    showToast('success', 'Stok bahan baku dihapus');
                                    location.reload();
                                },
                                error: function(err) {
                                    showToast('error', err.responseJSON?.message || 'Gagal menghapus stok bahan baku');
                                }
                            });
                        }
                    });
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            .inventory-detail-page .inventory-type-badge {
                background: #edf3ff;
                color: #254ead;
                border: 1px solid rgba(37, 78, 173, 0.18);
            }

            .inventory-detail-page .stock-search-input {
                min-width: 180px;
            }

            .inventory-detail-page .stock-status-select {
                min-width: 170px;
            }

            @media (max-width: 768px) {
                .inventory-detail-page .stock-search-input,
                .inventory-detail-page .stock-status-select {
                    min-width: 100%;
                }
            }
        </style>
    @endpush
@endsection

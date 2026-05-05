@extends('layouts.app')
@section('content')
    <div class="main-content">
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h4 mb-0 font-weight-bold">
                        <i class="fa fa-boxes me-2"></i> Detail Supplier
                    </h2>
                    <p class="text-muted small mt-1">{{ $supplier->name }} — lihat semua bahan baku supplier dan kelola data pemasok secara mudah.</p>
                </div>
                <div>
                    <a href="{{ route('warehouse/supplier') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa fa-arrow-left me-1"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-start border-primary h-100 shadow-sm">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Status Supplier</p>
                            <h5 class="mb-0">{{ $supplier->is_active ? 'Aktif' : 'Tidak Aktif' }}</h5>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-start border-success h-100 shadow-sm">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Mode Procurement</p>
                            <h5 class="mb-0 text-capitalize">{{ $supplier->procurement_mode }}</h5>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-start border-info h-100 shadow-sm">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Jumlah Bahan</p>
                            <h5 class="mb-0">{{ $supplier->rawMaterials->count() }}</h5>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card border-start border-warning h-100 shadow-sm">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Preferred</p>
                            <h5 class="mb-0">{{ $supplier->preferredRawMaterials->count() }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">Profil Supplier</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-5 text-muted">Kode</dt>
                            <dd class="col-7">{{ $supplier->code ?? '-' }}</dd>

                            <dt class="col-5 text-muted">Nama</dt>
                            <dd class="col-7">{{ $supplier->name }}</dd>

                            <dt class="col-5 text-muted">Mode Pembelian</dt>
                            <dd class="col-7 text-capitalize">{{ $supplier->procurement_mode }}</dd>

                            <dt class="col-5 text-muted">Terakhir Diubah</dt>
                            <dd class="col-7">{{ optional($supplier->updated_at)->format('d M Y H:i') }}</dd>

                            <dt class="col-5 text-muted">Catatan</dt>
                            <dd class="col-7">{{ $supplier->notes ?? '-' }}</dd>
                        </dl>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">Kontak & Channel</h5>
                    </div>
                    <div class="card-body">
                        @if ($supplier->contacts->isNotEmpty())
                            <ul class="list-group list-group-flush">
                                @foreach ($supplier->contacts->take(4) as $contact)
                                    <li class="list-group-item px-0 border-0 py-2">
                                        <strong>{{ $contact->name ?? 'Kontak' }}</strong><br>
                                        <small>{{ $contact->phone ?? $contact->email ?? '-' }}</small>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-muted">Belum ada kontak supplier yang disimpan.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Daftar Bahan Supplier</h5>
                            <small class="text-muted">Tambahkan bahan baku yang ditawarkan oleh supplier ini.</small>
                        </div>
                        <div>
                            <a href="{{ route('warehouse/supplier/raw-materials/create', $supplier->id) }}" class="btn btn-primary btn-sm action">
                                <i class="fa fa-plus me-1"></i> Tambah Bahan Baku
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive mt-4 mb-3">
                            @if ($supplier->rawMaterials->isNotEmpty())
                                <table class="table table-hover table-sm mb-0 align-middle">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Master</th>
                                            <th>Nama Supplier</th>
                                            <th>Unit</th>
                                            <th>MOQ</th>
                                            <th>Lead Time</th>
                                            <th>Harga</th>
                                            <th>Status</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($supplier->rawMaterials as $index => $item)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ optional($item->rawMaterial)->name ?? '-' }}</td>
                                                <td>{{ $item->supplier_material_name ?? '-' }}</td>
                                                <td>{{ optional($item->purchaseUnit)->name ?? '-' }}</td>
                                                <td>{{ number_format($item->minimum_order_qty, 2, ',', '.') }}</td>
                                                <td>{{ $item->lead_time_days }} hari</td>
                                                <td>{{ $item->current_price !== null ? 'Rp ' . number_format($item->current_price, 2, ',', '.') : '-' }}</td>
                                                <td>
                                                    @if ($item->is_active)
                                                        <span class="badge bg-success">Aktif</span>
                                                    @else
                                                        <span class="badge bg-secondary">Nonaktif</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('warehouse/supplier/raw-materials/edit', [$supplier->id, $item->id]) }}" class="btn btn-sm btn-outline-primary action">
                                                        Edit
                                                    </a>
                                                    <a href="{{ route('warehouse/supplier/raw-materials/destroy', [$supplier->id, $item->id]) }}" class="btn btn-sm btn-outline-danger delete-raw-material">
                                                        Hapus
                                                    </a>
                                                </div>
                                            </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="p-4 text-center text-muted">
                                    Belum ada bahan baku supplier yang ditambahkan.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">Histori Harga Bahan Supplier</h5>
                            <small class="text-muted">Perubahan harga terlihat di sini setiap kali material diperbarui.</small>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive mt-4 mb-3">
                            @if ($supplier->rawMaterials->pluck('priceHistories')->flatten()->isNotEmpty())
                                <table class="table table-striped table-sm mb-0 align-middle">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Bahan</th>
                                            <th>Harga</th>
                                            <th>Tanggal Efektif</th>
                                            <th>Jenis Pajak</th>
                                            <th>Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($supplier->rawMaterials as $material)
                                            @foreach ($material->priceHistories->sortByDesc('effective_from') as $history)
                                                <tr>
                                                    <td>{{ $loop->parent->index + 1 }}</td>
                                                    <td>{{ optional($material->rawMaterial)->name ?? '-' }}</td>
                                                    <td>Rp {{ number_format($history->price, 2, ',', '.') }}</td>
                                                    <td>{{ optional($history->effective_from)->format('d M Y') ?? '-' }}</td>
                                                    <td class="text-capitalize">{{ str_replace('_', ' ', $history->tax_type) }}</td>
                                                    <td>{{ $history->notes ?? '-' }}</td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="p-4 text-center text-muted">
                                    Belum ada histori harga yang tercatat untuk supplier ini.
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
            $(function () {
                handleAction(null, null, function (res) {
                    location.reload();
                });

                $('.main-content').on('click', '.delete-raw-material', function (e) {
                    e.preventDefault();
                    const url = this.href;

                    Swal.fire({
                        title: 'Hapus bahan baku?',
                        text: 'Data bahan baku supplier akan dihapus permanen.',
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
                                success: function () {
                                    showToast('success', 'Bahan baku supplier dihapus');
                                    location.reload();
                                },
                                error: function () {
                                    showToast('error', 'Gagal menghapus bahan baku supplier');
                                }
                            });
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection

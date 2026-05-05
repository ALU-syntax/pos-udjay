@extends('layouts.app')
@section('content')
    <div class="main-content">
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h4 mb-0 font-weight-bold">
                        <i class="fa fa-truck me-2"></i> Supplier & Bahan Baku
                    </h2>
                    <p class="text-muted small mt-1">Kelola pemasok dan bahan baku supplier secara lengkap.</p>
                </div>
                <a href="{{ route('warehouse/supplier/create') }}" class="btn btn-primary btn-round action">
                    <i class="fa fa-plus me-2"></i> Tambah Supplier
                </a>
            </div>

            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <div class="card border-start border-primary h-100 shadow-sm">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Total Supplier</p>
                            <h3 class="mb-0 font-weight-bold">{{ $stats['total'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card border-start border-success h-100 shadow-sm">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Supplier Aktif</p>
                            <h3 class="mb-0 font-weight-bold text-success">{{ $stats['active'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card border-start border-danger h-100 shadow-sm">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Supplier Tidak Aktif</p>
                            <h3 class="mb-0 font-weight-bold text-danger">{{ $stats['inactive'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card border-start border-info h-100 shadow-sm">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Total Bahan Supplier</p>
                            <h3 class="mb-0 font-weight-bold text-info">{{ $stats['supplied_materials'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle me-2"></i>
                <strong>Sukses!</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Daftar Supplier</h5>
                        <small class="text-muted">Klik tombol action untuk melihat detail, edit, atau menghapus supplier.</small>
                    </div>
                    <div>
                        <a href="{{ route('warehouse/supplier/create') }}" class="btn btn-outline-primary btn-sm action">
                            <i class="fa fa-plus me-1"></i> Supplier Baru
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive mt-4 mb-3">
                    {!! $dataTable->table(['class' => 'table table-hover table-sm mb-0']) !!}
                </div>
            </div>
        </div>
    </div>

    @push('js')
        {!! $dataTable->scripts() !!}
        <script>
            var datatable = 'supplier-table';

            $(document).ready(function () {
                handleAction(datatable, null, function (res) {
                    console.log('Supplier action completed', res);
                });
                handleDelete(datatable);
            });
        </script>
    @endpush
@endsection

@extends('layouts.app')
@section('content')
    <div class="main-content">
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h4 mb-0 font-weight-bold">
                        <i class="fa fa-balance-scale me-2"></i>Manajemen Satuan
                    </h2>
                    <p class="text-muted small mt-1">Buat, lihat, dan perbarui satuan produk dan bahan baku.</p>
                </div>
                <a href="{{ route('warehouse/satuan/create') }}" class="btn btn-primary btn-round action">
                    <i class="fa fa-plus me-2"></i>Tambah Satuan
                </a>
            </div>

            <div class="row">
                <div class="col-md-4 col-sm-6 mb-3">
                    <div class="card border-start border-primary h-100">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Total Satuan</p>
                            <h3 class="mb-0 font-weight-bold" id="stats-total">{{ $stats['total'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6 mb-3">
                    <div class="card border-start border-success h-100">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Aktif</p>
                            <h3 class="mb-0 font-weight-bold text-success" id="stats-active">{{ $stats['active'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-6 mb-3">
                    <div class="card border-start border-danger h-100">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Tidak Aktif</p>
                            <h3 class="mb-0 font-weight-bold text-danger" id="stats-inactive">{{ $stats['inactive'] ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <select id="filterStatus" class="form-select form-select-sm bg-light border-0">
                            <option value="">Semua Status</option>
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <small class="text-muted">Klik tombol action untuk edit.</small>
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
            var success = "{{ session('success') }}";
            const datatable = 'satuan-table';
            var table;

            $(document).ready(function() {
                if ($.fn.DataTable.isDataTable('#' + datatable)) {
                    table = $('#' + datatable).DataTable();

                    $('#filterStatus').on('change', function() {
                        table.column('2:visible').search(this.value).draw();
                    });

                    const wrapper = $('#' + datatable).closest('.dataTables_wrapper');
                    wrapper.find('.dataTables_length, .dataTables_filter').closest('.row').addClass('mx-4');
                    wrapper.find('.dataTables_info, .dataTables_paginate').closest('.row').addClass('mx-4');
                }

                handleAction(datatable, null, function(res) {
                    const data = res.data;
                    $('#stats-total').text(data.total);
                    $('#stats-active').text(data.active);
                    $('#stats-inactive').text(data.inactive);
                });
            });

            if (success) {
                Swal.fire({
                    title: 'Sukses!',
                    text: success,
                    icon: 'success',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false,
                    timer: 3000
                });
            }
        </script>

        <style>
            .border-start {
                border-left-width: 4px !important;
            }

            .card {
                border: 1px solid rgba(0, 0, 0, 0.08);
                transition: box-shadow 0.25s ease;
            }

            .card:hover {
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            }

            #satuan-table tbody tr:hover {
                background-color: rgba(0, 0, 0, 0.03);
            }
        </style>
    @endpush
@endsection

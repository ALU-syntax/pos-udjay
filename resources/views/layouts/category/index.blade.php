@extends('layouts.app')
@section('content')
    <div class="main-content">
        <!-- Header Section -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h4 mb-0 font-weight-bold">
                        <i class="fa fa-tag me-2"></i>Manajemen Category
                    </h2>
                    <p class="text-muted small mt-1">Kelola kategori produk dengan mudah dan efisien</p>
                </div>
                @can('create library/category')
                    <a href="{{ route('library/category/create') }}" class="btn btn-primary btn-round action">
                        <i class="fa fa-plus me-2"></i>Tambah Category
                    </a>
                @endcan
            </div>

            <!-- Stats Cards -->
            <div class="row">
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-start border-primary h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted small mb-1">Total Categories</p>
                                    <h3 class="mb-0 font-weight-bold" id="stats-total">{{ $stats['total'] ?? 0 }}</h3>
                                </div>
                                <div class="bg-primary bg-opacity-10 p-2 rounded">
                                    <i class="fa text-white fa-list fa-lg"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-start border-success h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted small mb-1">Active</p>
                                    <h3 class="mb-0 font-weight-bold text-success" id="stats-active">{{ $stats['active'] ?? 0 }}</h3>
                                </div>
                                <div class="bg-success bg-opacity-10 p-2 rounded">
                                    <i class="fa text-white fa-check-circle fa-lg"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-start border-danger h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted small mb-1">Inactive</p>
                                    <h3 class="mb-0 font-weight-bold text-danger" id="stats-inactive">{{ $stats['inactive'] ?? 0 }}</h3>
                                </div>
                                <div class="bg-danger bg-opacity-10 p-2 rounded">
                                    <i class="fa text-white fa-times-circle fa-lg "></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="card border-start border-info h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted small mb-1">Reward Category</p>
                                    <h3 class="mb-0 font-weight-bold text-info" id="stats-reward">{{ $stats['reward'] ?? 0 }}</h3>
                                </div>
                                <div class="bg-info bg-opacity-10 p-2 rounded">
                                    <i class="fa text-white fa-star fa-lg "></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        @if (session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa fa-check-circle me-2"></i>
                <strong>Sukses!</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa fa-exclamation-circle me-2"></i>
                <strong>Error!</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Main Card with Filters and Table -->
        <div class="card shadow-sm">
            <!-- Card Header with Search & Filters -->
            <div class="card-header bg-white border-bottom">
                <div class="row g-2">
                    <div class="col-md-6">
                        <select id="filterStatus" class="form-select form-select-sm bg-light border-0">
                            <option value="">Semua Status</option>
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <select id="filterReward" class="form-select form-select-sm bg-light border-0">
                            <option value="">Semua Kategori Reward</option>
                            <option value="1">Reward</option>
                            <option value="0">Non-Reward</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table -->
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
            // Initialize variables
            var success = "{{ session('success') }}";
            const datatable = 'category-table';
            var table; // Will be set after DataTable initialization

            // Wait for DataTable to be initialized, then setup custom filters
            $(document).ready(function() {
                // Get existing DataTable instance (already initialized by Yajra)
                if ($.fn.DataTable.isDataTable('#' + datatable)) {
                    table = $('#' + datatable).DataTable();

                    $('#filterStatus').on('change', function() {
                        table.column('2:visible').search(this.value).draw();
                    });

                    $('#filterReward').on('change', function() {
                        table.column('3:visible').search(this.value).draw();
                    });
                }

                // Call existing handlers
                handleAction(datatable, null, function(res){
                    console.log("checkRes: ", res);
                    const data = res.data;

                    $('#stats-total').text(data.total);
                    $('#stats-active').text(data.active);
                    $('#stats-inactive').text(data.inactive);
                    $('#stats-reward').text(data.reward);
                });

                handleDelete(datatable);
            });

            // Success Notification - can run immediately
            if (success) {
                Swal.fire({
                    title: 'Sukses!',
                    text: success,
                    icon: 'success',
                    type: 'success',
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

            .bg-opacity-10 {
                background-color: rgba(255, 255, 255, 0.1);
            }

            .card {
                border: 1px solid rgba(0, 0, 0, 0.08);
                transition: box-shadow 0.3s ease;
            }

            .card:hover {
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            }

            #category-table tbody tr:hover {
                background-color: rgba(0, 0, 0, 0.02);
            }

            .input-group-text {
                border-color: transparent !important;
            }
        </style>
    @endpush
@endsection

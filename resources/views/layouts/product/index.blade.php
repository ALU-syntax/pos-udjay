@extends('layouts.app')
@section('content')
    @include('layouts.reports.partials.ui-styles')

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Library</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-mug-hot"></i></span>
                    Produk
                </h1>
                <p class="ob-header__desc">Kelola produk, varian harga, dan produk yang tampil di menu Order Table.</p>
            </div>
            @can('create library/product')
                <a href="{{ route('library/product/create') }}" id="btnTambahProduct" class="btn ob-btn ob-btn-primary action">
                    <i class="fas fa-plus" aria-hidden="true"></i> Tambah Produk
                </a>
            @endcan
        </div>

        @if (session()->has('error'))
            <div class="ob-notice" role="alert">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Tabel --}}
        <div class="ob-card">
            <div class="ob-tabs-bar">
                <div>
                    <h2 class="ob-section-title mb-1"><i class="fas fa-list" aria-hidden="true"></i> Daftar Produk</h2>
                    <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Switch Order Table mengatur produk yang tampil di menu pelanggan. Kasir tetap melihat semua produk.</span>
                </div>
                <div class="ob-list-filters">
                    <div class="ob-field ob-list-filter">
                        <label for="filter-outlet"><i class="fas fa-store" aria-hidden="true"></i> Outlet</label>
                        <select id="filter-outlet" class="form-control">
                            <option value="all" selected>Semua outlet</option>
                            @foreach ($outlets as $outlet)
                                <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ob-field ob-list-filter">
                        <label for="filter-status"><i class="fas fa-toggle-on" aria-hidden="true"></i> Status</label>
                        <select id="filter-status" class="form-select">
                            <option value="">Semua status</option>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                    <div class="ob-field ob-list-filter">
                        <label for="filter-order-table"><i class="fas fa-qrcode" aria-hidden="true"></i> Order Table</label>
                        <select id="filter-order-table" class="form-select">
                            <option value="">Semua</option>
                            <option value="1">Tampil</option>
                            <option value="0">Disembunyikan</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="ob-table-wrap">
                {!! $dataTable->table(['class' => 'table w-100']) !!}
            </div>
        </div>
    </div>

    @push('js')
        {!! $dataTable->scripts() !!}
        @include('components.order-table-switch-script', ['datatable' => 'product-table'])

        <script>
            // Dipakai skrip varian di product-modal; direset setiap form dibuka.
            var listVarianLength = 0;
            const datatable = 'product-table';
            var success = "{{ session('success') }}";

            $(function() {
                $('#filter-outlet').select2({ width: '100%', minimumResultsForSearch: 8 });
                $('#filter-outlet, #filter-status, #filter-order-table').on('change', function() {
                    window.LaravelDataTables[datatable].ajax.reload();
                });

                $('.main-content').on('click', '.action', function() {
                    listVarianLength = 0;
                });

                handleAction(datatable);
                handleDelete(datatable);
            });

            if (success) {
                Swal.fire({
                    title: 'Success!',
                    text: success,
                    icon: 'success',
                    customClass: { confirmButton: 'btn btn-primary' },
                    buttonsStyling: false
                });
            }
        </script>
    @endpush
@endsection

@extends('layouts.app')
@section('content')
    @include('layouts.reports.partials.ui-styles')

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Library</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-sliders-h"></i></span>
                    Modifier
                </h1>
                <p class="ob-header__desc">Kelola grup modifier (mis. Sugar Level), opsinya, dan produk yang memakainya.</p>
            </div>
            @can('create library/modifiers')
                <a href="{{ route('library/modifiers/create') }}" class="btn ob-btn ob-btn-primary action">
                    <i class="fas fa-plus" aria-hidden="true"></i> Tambah Modifier
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
                    <h2 class="ob-section-title mb-1"><i class="fas fa-list" aria-hidden="true"></i> Daftar Modifier</h2>
                    <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Opsi bergaris putus-putus disembunyikan dari Order Table. Atur per opsi lewat tombol edit.</span>
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
                        <label for="filter-required"><i class="fas fa-asterisk" aria-hidden="true"></i> Pilihan</label>
                        <select id="filter-required" class="form-select">
                            <option value="">Semua grup</option>
                            <option value="1">Wajib dipilih</option>
                            <option value="0">Opsional</option>
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
        @include('components.order-table-switch-script', ['datatable' => 'modifiersdatatables-table'])

        <script>
            const datatable = 'modifiersdatatables-table';
            var success = "{{ session('success') }}";

            $(function() {
                $('#filter-outlet').select2({ width: '100%', minimumResultsForSearch: 8 });
                $('#filter-outlet, #filter-required, #filter-order-table').on('change', function() {
                    window.LaravelDataTables[datatable].ajax.reload();
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

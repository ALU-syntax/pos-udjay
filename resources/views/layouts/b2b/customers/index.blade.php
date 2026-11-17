@extends('layouts.app')

@section('content')
    @include('layouts.b2b.partials.assets')
    @include('layouts.b2b.partials.page-styles')

    <div class="main-content ob-page">
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Kasir B2B</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-building"></i></span>
                    Customer B2B
                </h1>
                <p class="ob-header__desc">Pelanggan bisnis untuk Kasir B2B. Terpisah dari data customer/membership POS.</p>
            </div>
            @can('create b2b/customers')
                <a href="{{ route('b2b/customers/create') }}" class="btn ob-btn ob-btn-primary">
                    <i class="fas fa-plus" aria-hidden="true"></i> Tambah Customer
                </a>
            @endcan
        </div>

        <div class="ob-card">
            <div class="ob-tabs-bar">
                <div>
                    <h2 class="ob-section-title mb-1"><i class="fas fa-list" aria-hidden="true"></i> Daftar Customer</h2>
                    <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Customer tanpa kontrak berlaku tetap bisa belanja dengan harga normal.</span>
                </div>
                <div class="ob-list-filters">
                    <div class="ob-field ob-list-filter">
                        <label for="type-filter"><i class="fas fa-tag" aria-hidden="true"></i> Jenis</label>
                        <select id="type-filter" class="form-select">
                            <option value="">Semua jenis</option>
                            @foreach (\App\Models\B2b\Customer::TYPES as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ob-field ob-list-filter">
                        <label for="status-filter"><i class="fas fa-filter" aria-hidden="true"></i> Tampilkan</label>
                        <select id="status-filter" class="form-select">
                            <option value="">Semua customer</option>
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                            <option value="contract">Punya kontrak berlaku</option>
                            <option value="no_contract">Tanpa kontrak (harga normal)</option>
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
        <script>
            $(function () {
                $('#status-filter, #type-filter').on('change', () => window.LaravelDataTables['b2b-customer-table'].ajax.reload());
            });
        </script>
    @endpush
@endsection

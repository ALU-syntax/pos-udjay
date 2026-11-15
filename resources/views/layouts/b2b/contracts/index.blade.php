@extends('layouts.app')

@section('content')
    @include('layouts.b2b.partials.assets')

    <div class="main-content ob-page">
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Kasir B2B</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-file-signature"></i></span>
                    Kontrak B2B
                </h1>
                <p class="ob-header__desc">Diskon dan termin pembayaran per customer. Kontrak aktif berakhir otomatis setiap hari pukul 00:01 WIB setelah lewat tanggal berakhir.</p>
            </div>
            @can('create b2b/contracts')
                <a href="{{ route('b2b/contracts/create') }}" class="btn ob-btn ob-btn-primary">
                    <i class="fas fa-plus" aria-hidden="true"></i> Buat Kontrak
                </a>
            @endcan
        </div>

        <div class="ob-card">
            <div class="ob-tabs-bar">
                <h2 class="ob-section-title mb-0"><i class="fas fa-list" aria-hidden="true"></i> Daftar Kontrak</h2>
                <div class="ob-field ob-list-filter">
                    <label for="status-filter"><i class="fas fa-filter" aria-hidden="true"></i> Status</label>
                    <select id="status-filter" class="form-control">
                        <option value="">Semua status</option>
                        <option value="draft">Draft</option>
                        <option value="active">Aktif / terjadwal</option>
                        <option value="expired">Berakhir</option>
                        <option value="terminated">Dihentikan</option>
                    </select>
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
                $('#status-filter').on('change', () => window.LaravelDataTables['b2b-contract-table'].ajax.reload());
            });
        </script>
    @endpush
@endsection

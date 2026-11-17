@extends('layouts.app')

@section('content')
    @include('layouts.b2b.partials.assets')
    @include('layouts.b2b.partials.page-styles')
    @include('layouts.b2b.contracts.partials.styles')

    <div class="main-content ob-page">
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Kasir B2B</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-file-signature"></i></span>
                    Kontrak B2B
                </h1>
                <p class="ob-header__desc">Harga khusus per produk untuk setiap customer. Satu customer hanya punya satu kontrak di satu periode.</p>
            </div>
            @can('create b2b/contracts')
                <a href="{{ route('b2b/contracts/create') }}" class="btn ob-btn ob-btn-primary">
                    <i class="fas fa-plus" aria-hidden="true"></i> Buat Kontrak
                </a>
            @endcan
        </div>

        <div class="ob-stats">
            <button type="button" class="ob-card ob-stat b2b-stat-filter" data-status="running">
                <span class="ob-stat__icon ob-stat__icon--success" aria-hidden="true"><i class="fas fa-play"></i></span>
                <div><p class="ob-stat__label">Berjalan</p><p class="ob-stat__value">{{ number_format($stats['running'], 0, ',', '.') }}</p></div>
            </button>
            <button type="button" class="ob-card ob-stat b2b-stat-filter" data-status="ending">
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-hourglass-half"></i></span>
                <div><p class="ob-stat__label">Berakhir ≤ 30 hari</p><p class="ob-stat__value">{{ number_format($stats['ending_soon'], 0, ',', '.') }}</p></div>
            </button>
            <button type="button" class="ob-card ob-stat b2b-stat-filter" data-status="scheduled">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-calendar-alt"></i></span>
                <div><p class="ob-stat__label">Terjadwal</p><p class="ob-stat__value">{{ number_format($stats['scheduled'], 0, ',', '.') }}</p></div>
            </button>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--danger" aria-hidden="true"><i class="fas fa-building"></i></span>
                <div><p class="ob-stat__label">Customer dengan kontrak</p><p class="ob-stat__value">{{ number_format($stats['customers'], 0, ',', '.') }}</p></div>
            </div>
        </div>

        <div class="ob-card">
            <div class="ob-tabs-bar">
                <div>
                    <h2 class="ob-section-title mb-1"><i class="fas fa-list" aria-hidden="true"></i> Daftar Kontrak</h2>
                    <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Kontrak bisa diedit selama belum dihentikan. Pesanan lama tetap memakai harga saat transaksi.</span>
                </div>
                <div class="ob-list-filters">
                    <div class="ob-field ob-list-filter">
                        <label for="status-filter"><i class="fas fa-filter" aria-hidden="true"></i> Status</label>
                        <select id="status-filter" class="form-select">
                            <option value="">Semua status</option>
                            <option value="running">Berjalan</option>
                            <option value="ending">Berakhir ≤ 30 hari</option>
                            <option value="scheduled">Terjadwal</option>
                            <option value="ended">Berakhir</option>
                            <option value="terminated">Dihentikan</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="ob-table-wrap">
                {!! $dataTable->table(['class' => 'table w-100']) !!}
            </div>
        </div>
    </div>

    @push('css')
        <style>
            .b2b-stat-filter { width: 100%; border: 1px solid var(--ob-border); text-align: left; cursor: pointer; transition: border-color .15s ease, box-shadow .15s ease; }
            .b2b-stat-filter:hover { border-color: var(--ob-border-strong); }
            .b2b-stat-filter.is-active { border-color: var(--ob-primary); box-shadow: 0 0 0 1px var(--ob-primary); }
        </style>
    @endpush

    @push('js')
        {!! $dataTable->scripts() !!}
        <script>
            $(function () {
                const filter = $('#status-filter');
                const reload = () => {
                    $('.b2b-stat-filter').removeClass('is-active').filter(`[data-status="${filter.val()}"]`).addClass('is-active');
                    window.LaravelDataTables['b2b-contract-table'].ajax.reload();
                };
                filter.on('change', reload);
                // Kartu ringkasan berfungsi sebagai filter cepat; klik lagi untuk melepas.
                $('.b2b-stat-filter').on('click', function () {
                    filter.val(filter.val() === this.dataset.status ? '' : this.dataset.status);
                    reload();
                });
            });
        </script>
    @endpush
@endsection

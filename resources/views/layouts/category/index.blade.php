@extends('layouts.app')
@section('content')
    @include('layouts.reports.partials.ui-styles')

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Library</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-tag"></i></span>
                    Kategori
                </h1>
                <p class="ob-header__desc">Kelompokkan produk dan atur kategori yang tampil di menu Order Table.</p>
            </div>
            @can('create library/category')
                <a href="{{ route('library/category/create') }}" class="btn ob-btn ob-btn-primary action">
                    <i class="fas fa-plus" aria-hidden="true"></i> Tambah Kategori
                </a>
            @endcan
        </div>

        @if (session()->has('error'))
            <div class="ob-notice" role="alert">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Ringkasan (diperbarui dari respons simpan) --}}
        <div class="ob-stats">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-list"></i></span>
                <div>
                    <p class="ob-stat__label">Total Kategori</p>
                    <p class="ob-stat__value" id="stats-total">{{ number_format($stats['total'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--success" aria-hidden="true"><i class="fas fa-check-circle"></i></span>
                <div>
                    <p class="ob-stat__label">Aktif</p>
                    <p class="ob-stat__value" id="stats-active">{{ number_format($stats['active'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--danger" aria-hidden="true"><i class="fas fa-times-circle"></i></span>
                <div>
                    <p class="ob-stat__label">Nonaktif</p>
                    <p class="ob-stat__value" id="stats-inactive">{{ number_format($stats['inactive'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-star"></i></span>
                <div>
                    <p class="ob-stat__label">Kategori Reward</p>
                    <p class="ob-stat__value" id="stats-reward">{{ number_format($stats['reward'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- Tabel --}}
        <div class="ob-card">
            <div class="ob-tabs-bar">
                <div>
                    <h2 class="ob-section-title mb-1"><i class="fas fa-list" aria-hidden="true"></i> Daftar Kategori</h2>
                    <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Kategori yang disembunyikan menyembunyikan semua produknya dari Order Table.</span>
                </div>
                <div class="ob-list-filters">
                    <div class="ob-field ob-list-filter">
                        <label for="filter-status"><i class="fas fa-toggle-on" aria-hidden="true"></i> Status</label>
                        <select id="filter-status" class="form-select">
                            <option value="">Semua status</option>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </div>
                    <div class="ob-field ob-list-filter">
                        <label for="filter-reward"><i class="fas fa-star" aria-hidden="true"></i> Reward</label>
                        <select id="filter-reward" class="form-select">
                            <option value="">Semua kategori</option>
                            <option value="1">Reward</option>
                            <option value="0">Biasa</option>
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

        <script>
            const datatable = 'category-table';
            var success = "{{ session('success') }}";

            $(function() {
                $('#filter-status, #filter-reward, #filter-order-table').on('change', function() {
                    window.LaravelDataTables[datatable].ajax.reload();
                });

                handleAction(datatable, null, function(res) {
                    const data = res.data || {};
                    const format = (value) => Number(value || 0).toLocaleString('id-ID');
                    $('#stats-total').text(format(data.total));
                    $('#stats-active').text(format(data.active));
                    $('#stats-inactive').text(format(data.inactive));
                    $('#stats-reward').text(format(data.reward));
                });

                handleDelete(datatable);
            });

            if (success) {
                Swal.fire({
                    title: 'Sukses!',
                    text: success,
                    icon: 'success',
                    customClass: { confirmButton: 'btn btn-primary' },
                    buttonsStyling: false,
                    timer: 3000
                });
            }
        </script>
    @endpush
@endsection

@extends('layouts.app')
@section('content')
    @include('layouts.reports.partials.ui-styles')

    @php
        $canCreateConversion = $rawMaterials->isNotEmpty() && $unitCount >= 2;
    @endphp

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Library</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-balance-scale"></i></span>
                    Satuan
                </h1>
                <p class="ob-header__desc">Kelola master satuan dan aturan konversi khusus per bahan baku.</p>
            </div>
            @can('create library/satuan')
                <div class="sat-header-actions">
                    @if ($canCreateConversion)
                        <a href="{{ route('library/satuan/conversions/create') }}" class="btn ob-btn ob-btn-outline action">
                            <i class="fas fa-random" aria-hidden="true"></i> Tambah Konversi
                        </a>
                    @else
                        <button type="button" class="btn ob-btn ob-btn-outline" disabled
                            title="Butuh minimal 1 bahan baku dan 2 satuan">
                            <i class="fas fa-random" aria-hidden="true"></i> Tambah Konversi
                        </button>
                    @endif
                    <a href="{{ route('library/satuan/create') }}" class="btn ob-btn ob-btn-primary action">
                        <i class="fas fa-plus" aria-hidden="true"></i> Tambah Satuan
                    </a>
                </div>
            @endcan
        </div>

        {{-- Ringkasan (diperbarui dari respons simpan) --}}
        <div class="ob-stats">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-balance-scale"></i></span>
                <div>
                    <p class="ob-stat__label">Total Satuan</p>
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
                <span class="ob-stat__icon ob-stat__icon--danger" aria-hidden="true"><i class="fas fa-pause-circle"></i></span>
                <div>
                    <p class="ob-stat__label">Nonaktif</p>
                    <p class="ob-stat__value" id="stats-inactive">{{ number_format($stats['inactive'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-random"></i></span>
                <div>
                    <p class="ob-stat__label">Aturan Konversi</p>
                    <p class="ob-stat__value" id="stats-conversion-total">{{ number_format($stats['conversion_total'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        <ul class="nav ob-tabs sat-tabs" id="satuanTabs" role="tablist">
            <li role="presentation">
                <button class="nav-link active" id="master-tab" data-bs-toggle="tab" data-bs-target="#master-pane"
                    type="button" role="tab" aria-controls="master-pane" aria-selected="true">
                    <i class="fas fa-list" aria-hidden="true"></i> Master Satuan
                    <span class="ob-count" id="tab-count-master">{{ $stats['total'] ?? 0 }}</span>
                </button>
            </li>
            <li role="presentation">
                <button class="nav-link" id="conversion-tab" data-bs-toggle="tab" data-bs-target="#conversion-pane"
                    type="button" role="tab" aria-controls="conversion-pane" aria-selected="false">
                    <i class="fas fa-exchange-alt" aria-hidden="true"></i> Konversi per Bahan Baku
                    <span class="ob-count" id="tab-count-conversion">{{ $stats['conversion_total'] ?? 0 }}</span>
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="master-pane" role="tabpanel" aria-labelledby="master-tab">
                <div class="ob-card">
                    <div class="ob-tabs-bar">
                        <div>
                            <h2 class="ob-section-title mb-1"><i class="fas fa-list" aria-hidden="true"></i> Master Satuan</h2>
                            <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Kolom "Dipakai oleh" menghitung bahan baku yang memakai satuan ini sebagai satuan dasar.</span>
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
                        </div>
                    </div>
                    <div class="ob-table-wrap">
                        {!! $dataTable->table(['class' => 'table w-100']) !!}
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="conversion-pane" role="tabpanel" aria-labelledby="conversion-tab">
                <div class="ob-card">
                    <div class="ob-tabs-bar">
                        <div>
                            <h2 class="ob-section-title mb-1"><i class="fas fa-exchange-alt" aria-hidden="true"></i> Konversi per Bahan Baku</h2>
                            <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Formula khusus per bahan, misalnya 1 box telur = 15 kg atau 1 kg tepung = 1.000 g.</span>
                        </div>
                        <div class="ob-list-filters">
                            <div class="ob-field ob-list-filter">
                                <label for="filter-material"><i class="fas fa-cubes" aria-hidden="true"></i> Bahan Baku</label>
                                <select id="filter-material" class="form-select">
                                    <option value="">Semua bahan baku</option>
                                    @foreach ($rawMaterials as $material)
                                        <option value="{{ $material->id }}">{{ $material->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="ob-table-wrap">
                        {!! $conversionTable->table(['class' => 'table w-100']) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('js')
        {!! $dataTable->scripts() !!}
        {!! $conversionTable->scripts() !!}

        <script>
            $(function () {
                const datatable = 'satuan-table';
                const conversionDatatable = 'conversion-table';
                const table = window.LaravelDataTables[datatable];
                const conversionTable = window.LaravelDataTables[conversionDatatable];
                const tabKey = 'satuan-active-tab';

                function applyStats(data) {
                    if (!data) return;

                    const format = (value) => Number(value || 0).toLocaleString('id-ID');
                    $('#stats-total').text(format(data.total));
                    $('#stats-active').text(format(data.active));
                    $('#stats-inactive').text(format(data.inactive));
                    $('#stats-conversion-total').text(format(data.conversion_total));
                    $('#tab-count-master').text(format(data.total));
                    $('#tab-count-conversion').text(format(data.conversion_total));
                }

                function storeTab(tab) {
                    try { localStorage.setItem(tabKey, tab); } catch (e) {}
                }

                function savedTab() {
                    if (window.location.hash === '#konversi') return 'conversion';
                    try { return localStorage.getItem(tabKey); } catch (e) { return null; }
                }

                if (savedTab() === 'conversion' && window.bootstrap && bootstrap.Tab) {
                    bootstrap.Tab.getOrCreateInstance(document.querySelector('#conversion-tab')).show();
                }

                $('#satuanTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function (event) {
                    storeTab(event.target.id === 'conversion-tab' ? 'conversion' : 'master');
                    // Lebar kolom dihitung ulang karena tabel dibuat saat tab-nya masih tersembunyi
                    $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                });

                $('#filter-status').on('change', function () {
                    table.ajax.reload();
                });

                $('#filter-material').on('change', function () {
                    conversionTable.ajax.reload();
                });

                // Simpan satuan memuat ulang tabel satuan; simpan konversi juga memuat ulang tabel konversi
                handleAction(datatable, null, function (res) {
                    applyStats(res.data);

                    if (res.data && res.data.refresh_conversions) {
                        conversionTable.ajax.reload(null, false);
                    }
                });

                $('.main-content').on('click', '.delete-conversion', function (event) {
                    event.preventDefault();
                    const button = this;
                    const name = $(button).data('name');

                    Swal.fire({
                        title: 'Hapus konversi?',
                        text: 'Aturan konversi ' + (name ? 'untuk ' + name + ' ' : '') + 'akan dihapus.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (!result.isConfirmed) return;

                        $.ajax({
                            url: button.href,
                            method: 'DELETE',
                            data: { _token: @json(csrf_token()) },
                            success: function (response) {
                                showToast(response.status || 'success', response.message || 'Konversi bahan baku berhasil dihapus');
                                applyStats(response.data);
                                conversionTable.ajax.reload(null, false);
                            },
                            error: function (xhr) {
                                showToast('error', xhr.responseJSON?.message || 'Gagal menghapus konversi bahan baku');
                            }
                        });
                    });
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            .sat-header-actions {
                display: flex;
                flex-wrap: wrap;
                gap: .5rem;
            }

            .ob-page .sat-tabs {
                margin-bottom: 1rem;
            }

            .ob-page .sat-tabs li {
                list-style: none;
            }

            .ob-cell-stack {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: .2rem;
                white-space: nowrap;
            }

            .sat-strong {
                font-weight: 600;
                color: var(--ob-text);
                white-space: nowrap;
            }

            .ob-page .sat-formula {
                display: inline-flex;
                align-items: center;
                min-height: 26px;
                padding: .15rem .65rem;
                border-radius: 999px;
                background: var(--ob-primary-soft);
                color: var(--ob-primary);
                font-weight: 700;
                font-variant-numeric: tabular-nums;
                white-space: nowrap;
            }

            .ob-page .sat-notes {
                display: inline-block;
                max-width: 260px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                color: var(--ob-text-2);
            }

            .ob-page .sat-formula-preview {
                display: flex;
                align-items: center;
                gap: .5rem;
                font-weight: 600;
            }
        </style>
    @endpush
@endsection

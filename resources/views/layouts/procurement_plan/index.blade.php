@extends('layouts.app')
@section('content')
    @include('layouts.reports.partials.ui-styles')

    <div class="main-content ob-page">
        @if (session('success'))
            <div class="ob-notice pp-notice" role="status">
                <i class="fas fa-check-circle" aria-hidden="true"></i> {{ session('success') }}
            </div>
        @endif

        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Warehouse</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-clipboard-check"></i></span>
                    Procurement Plan
                </h1>
                <p class="ob-header__desc">Kumpulkan item Request Order yang sudah disetujui, lalu siapkan Purchase Order per supplier.</p>
            </div>
            @can('create warehouse/procurement-plan')
                <a href="{{ route('warehouse/procurement-plan/create') }}" class="btn ob-btn ob-btn-primary">
                    <i class="fas fa-plus" aria-hidden="true"></i> Tambah Plan
                </a>
            @endcan
        </div>

        {{-- Ringkasan (diperbarui dari respons hapus) --}}
        <div class="ob-stats">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-clipboard-check"></i></span>
                <div>
                    <p class="ob-stat__label">Total Plan</p>
                    <p class="ob-stat__value" id="stats-total">{{ number_format($stats['total'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-pencil-alt"></i></span>
                <div>
                    <p class="ob-stat__label">Draft</p>
                    <p class="ob-stat__value" id="stats-draft">{{ number_format($stats['draft'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--success" aria-hidden="true"><i class="fas fa-file-invoice"></i></span>
                <div>
                    <p class="ob-stat__label">Sudah Jadi PO</p>
                    <p class="ob-stat__value" id="stats-converted">{{ number_format($stats['converted_to_po'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--danger" aria-hidden="true"><i class="fas fa-layer-group"></i></span>
                <div>
                    <p class="ob-stat__label">Item Siap Direncanakan</p>
                    <p class="ob-stat__value" id="stats-sources">{{ number_format($stats['available_sources'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- Tabel --}}
        <div class="ob-card">
            <div class="ob-tabs-bar">
                <div>
                    <h2 class="ob-section-title mb-1"><i class="fas fa-list" aria-hidden="true"></i> Daftar Plan</h2>
                    <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Hanya draft yang bisa dihapus.</span>
                </div>
                <div class="ob-list-filters">
                    <div class="ob-field ob-list-filter">
                        <label for="filter-status"><i class="fas fa-flag" aria-hidden="true"></i> Status</label>
                        <select id="filter-status" class="form-select">
                            <option value="">Semua status</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->id }}">{{ $status->label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ob-field ob-list-filter">
                        <label for="filter-location"><i class="fas fa-warehouse" aria-hidden="true"></i> Lokasi</label>
                        <select id="filter-location" class="form-select">
                            <option value="">Semua lokasi</option>
                            @foreach ($inventories as $inventory)
                                <option value="{{ $inventory->id }}">{{ $inventory->name }}</option>
                            @endforeach
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
                const table = window.LaravelDataTables['procurement-plan-table'];

                function applyProcurementPlanStats(data) {
                    if (!data) return;

                    const format = (value) => Number(value || 0).toLocaleString('id-ID');
                    $('#stats-total').text(format(data.total));
                    $('#stats-draft').text(format(data.draft));
                    $('#stats-converted').text(format(data.converted_to_po));
                    $('#stats-sources').text(format(data.available_sources));
                }

                $('#filter-status, #filter-location').on('change', function () {
                    table.ajax.reload();
                });

                // Hapus draft: konfirmasi dulu, lalu muat ulang tabel dan kartu ringkasan
                $('.main-content').on('click', '.delete-procurement-plan', function (event) {
                    event.preventDefault();
                    const button = this;

                    Swal.fire({
                        title: 'Hapus draft?',
                        text: 'Plan ' + $(button).data('number') + ' akan dihapus dari daftar.',
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
                                showToast(response.status || 'success', response.message || 'Procurement plan draft berhasil dihapus.');
                                applyProcurementPlanStats(response.data);
                                table.ajax.reload(null, false);
                            },
                            error: function (xhr) {
                                const errors = xhr.responseJSON?.errors;
                                const message = errors ? Object.values(errors).flat()[0] : xhr.responseJSON?.message;
                                showToast('error', message || 'Gagal menghapus procurement plan');
                            }
                        });
                    });
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            .ob-cell-stack {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: .2rem;
            }

            .pp-strong {
                font-weight: 600;
                color: var(--ob-text);
                white-space: nowrap;
            }

            .pp-num {
                font-weight: 600;
                color: var(--ob-text);
                font-variant-numeric: tabular-nums;
                white-space: nowrap;
            }

            .pp-nowrap {
                white-space: nowrap;
            }

            .pp-notice {
                margin-bottom: 1rem;
            }
        </style>
    @endpush
@endsection

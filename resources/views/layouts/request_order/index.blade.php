@extends('layouts.app')
@section('content')
    @include('layouts.reports.partials.ui-styles')

    <div class="main-content ob-page">
        @if (session('success'))
            <div class="ob-notice ro-notice" role="status">
                <i class="fas fa-check-circle" aria-hidden="true"></i> {{ session('success') }}
            </div>
        @endif

        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Warehouse</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-clipboard-list"></i></span>
                    Request Order
                </h1>
                <p class="ob-header__desc">Permintaan bahan baku dari outlet atau kitchen ke gudang, mulai dari draft sampai disetujui.</p>
            </div>
            @can('create warehouse/request-order')
                <a href="{{ route('warehouse/request-order/create') }}" class="btn ob-btn ob-btn-primary">
                    <i class="fas fa-plus" aria-hidden="true"></i> Tambah Request
                </a>
            @endcan
        </div>

        {{-- Ringkasan (diperbarui dari respons ajukan/hapus) --}}
        <div class="ob-stats">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-clipboard-list"></i></span>
                <div>
                    <p class="ob-stat__label">Total Request</p>
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
                <span class="ob-stat__icon ob-stat__icon--danger" aria-hidden="true"><i class="fas fa-hourglass-half"></i></span>
                <div>
                    <p class="ob-stat__label">Menunggu Review</p>
                    <p class="ob-stat__value" id="stats-submitted">{{ number_format($stats['submitted'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--success" aria-hidden="true"><i class="fas fa-check-circle"></i></span>
                <div>
                    <p class="ob-stat__label">Disetujui</p>
                    <p class="ob-stat__value" id="stats-approved">{{ number_format($stats['approved'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>

        {{-- Tabel --}}
        <div class="ob-card">
            <div class="ob-tabs-bar">
                <div>
                    <h2 class="ob-section-title mb-1"><i class="fas fa-list" aria-hidden="true"></i> Daftar Request</h2>
                    <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Hanya draft yang bisa diedit, diajukan, atau dihapus.</span>
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
                        <label for="filter-requester"><i class="fas fa-store" aria-hidden="true"></i> Peminta</label>
                        <select id="filter-requester" class="form-select">
                            <option value="">Semua lokasi</option>
                            @foreach ($inventories as $inventory)
                                <option value="{{ $inventory->id }}">{{ $inventory->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="ob-field ob-list-filter">
                        <label for="filter-fulfillment"><i class="fas fa-warehouse" aria-hidden="true"></i> Pemenuh</label>
                        <select id="filter-fulfillment" class="form-select">
                            <option value="">Semua lokasi</option>
                            <option value="none">Belum ditentukan</option>
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
                const datatable = 'request-order-table';
                const table = window.LaravelDataTables[datatable];

                function applyRequestOrderStats(data) {
                    if (!data) return;

                    const format = (value) => Number(value || 0).toLocaleString('id-ID');
                    $('#stats-total').text(format(data.total));
                    $('#stats-draft').text(format(data.draft));
                    $('#stats-submitted').text(format(data.submitted));
                    $('#stats-approved').text(format(data.approved));
                }

                $('#filter-status, #filter-requester, #filter-fulfillment').on('change', function () {
                    table.ajax.reload();
                });

                // Ajukan & hapus draft: konfirmasi dulu, lalu muat ulang tabel dan kartu ringkasan
                function confirmAction(button, options) {
                    Swal.fire({
                        title: options.title,
                        text: options.text,
                        icon: options.icon,
                        showCancelButton: true,
                        confirmButtonText: options.confirm,
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (!result.isConfirmed) return;

                        $.ajax({
                            url: button.href,
                            method: options.method,
                            data: { _token: @json(csrf_token()) },
                            success: function (response) {
                                showToast(response.status || 'success', response.message || options.success);
                                applyRequestOrderStats(response.data);
                                table.ajax.reload(null, false);
                            },
                            error: function (xhr) {
                                const errors = xhr.responseJSON?.errors;
                                const message = errors ? Object.values(errors).flat()[0] : xhr.responseJSON?.message;
                                showToast('error', message || options.failed);
                            }
                        });
                    });
                }

                $('.main-content').on('click', '.submit-request-order', function (event) {
                    event.preventDefault();
                    confirmAction(this, {
                        title: 'Ajukan request?',
                        text: 'Request ' + $(this).data('number') + ' akan masuk tahap review dan tidak bisa diedit lagi.',
                        icon: 'question',
                        confirm: 'Ya, ajukan',
                        method: 'POST',
                        success: 'Request order berhasil diajukan.',
                        failed: 'Gagal mengajukan request order'
                    });
                });

                $('.main-content').on('click', '.delete-request-order', function (event) {
                    event.preventDefault();
                    confirmAction(this, {
                        title: 'Hapus draft?',
                        text: 'Draft ' + $(this).data('number') + ' akan dihapus dari daftar.',
                        icon: 'warning',
                        confirm: 'Ya, hapus',
                        method: 'DELETE',
                        success: 'Request order draft berhasil dihapus.',
                        failed: 'Gagal menghapus request order'
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

            .ro-strong {
                font-weight: 600;
                color: var(--ob-text);
                white-space: nowrap;
            }

            /* Nomor request tidak terlipat di tengah pada layar sempit */
            .ro-nowrap {
                white-space: nowrap;
            }

            .ro-date {
                white-space: nowrap;
            }

            .ro-overdue {
                font-weight: 600;
                color: var(--ob-danger);
                white-space: nowrap;
            }

            .ro-notice {
                margin-bottom: 1rem;
            }
        </style>
    @endpush
@endsection

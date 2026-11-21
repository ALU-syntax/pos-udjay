@extends('layouts.app')

@php
    $number = fn ($value) => number_format((int) $value, 0, ',', '.');
@endphp

@section('content')
    @include('layouts.b2b.partials.assets')
    @include('layouts.b2b.partials.page-styles')
    @include('layouts.b2b.partials.list-filters')

    <div class="main-content ob-page">
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Kasir B2B</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-file-invoice"></i></span>
                    Invoice B2B
                </h1>
                <p class="ob-header__desc">Ruang kerja penagihan. Invoice dibuat otomatis saat pesanan dikonfirmasi; catat pelunasan tempo dari tombol <i class="fas fa-hand-holding-usd" aria-hidden="true"></i> di setiap baris.</p>
            </div>
        </div>

        <div class="ob-stats">
            <button type="button" class="ob-card ob-stat b2b-stat-filter" data-filter="status" data-value="open" aria-pressed="false">
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-file-invoice-dollar"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Belum lunas</p>
                    <p class="ob-stat__value">Rp {{ $number($stats['unpaid_amount']) }}</p>
                    <p class="b2b-stat__sub">{{ $number($stats['unpaid_count']) }} invoice</p>
                </div>
            </button>
            <button type="button" class="ob-card ob-stat b2b-stat-filter" data-filter="status" data-value="overdue" aria-pressed="false">
                <span class="ob-stat__icon ob-stat__icon--danger" aria-hidden="true"><i class="fas fa-exclamation-circle"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Lewat jatuh tempo</p>
                    <p class="ob-stat__value {{ $stats['overdue_count'] ? 'text-danger' : '' }}">Rp {{ $number($stats['overdue_amount']) }}</p>
                    <p class="b2b-stat__sub">{{ $number($stats['overdue_count']) }} invoice</p>
                </div>
            </button>
            <button type="button" class="ob-card ob-stat b2b-stat-filter" data-filter="status" data-value="due_soon" aria-pressed="false">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-hourglass-half"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Jatuh tempo ≤ 7 hari</p>
                    <p class="ob-stat__value">Rp {{ $number($stats['due_soon_amount']) }}</p>
                    <p class="b2b-stat__sub">{{ $number($stats['due_soon_count']) }} invoice</p>
                </div>
            </button>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--success" aria-hidden="true"><i class="fas fa-hand-holding-usd"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Diterima bulan ini</p>
                    <p class="ob-stat__value">Rp {{ $number($stats['received_amount']) }}</p>
                    <p class="b2b-stat__sub">{{ $number($stats['received_count']) }} pembayaran</p>
                </div>
            </div>
        </div>

        <div class="ob-card">
            <div class="b2b-card__head">
                <div>
                    <h2 class="b2b-card__title"><i class="fas fa-list" aria-hidden="true"></i> Daftar Invoice</h2>
                    <p class="b2b-card__desc">Urut jatuh tempo terlama dulu. Klik kartu di atas untuk menyaring cepat; Export Excel mengikuti filter.</p>
                </div>
                <div class="b2b-actions">
                    <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" id="filter-reset" hidden>
                        <i class="fas fa-undo" aria-hidden="true"></i> Reset filter
                    </button>
                    <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" id="export-excel" data-url="{{ route('b2b/invoices/export') }}"
                        title="Unduh daftar sesuai filter: sheet Rekap + satu sheet per invoice">
                        <i class="fas fa-file-excel" aria-hidden="true"></i> Export Excel
                    </button>
                </div>
            </div>

            <div class="b2b-filter-row">
                <div class="ob-field">
                    <label for="status-filter"><i class="fas fa-filter" aria-hidden="true"></i> Status</label>
                    <select id="status-filter" class="form-select" data-default="open">
                        <option value="">Semua invoice</option>
                        <option value="open" selected>Belum lunas</option>
                        <option value="overdue">Lewat jatuh tempo</option>
                        <option value="due_soon">Jatuh tempo ≤ 7 hari</option>
                        <option value="paid">Lunas</option>
                        <option value="void">Dibatalkan</option>
                    </select>
                </div>
                <div class="ob-field">
                    <label for="customer-filter"><i class="fas fa-building" aria-hidden="true"></i> Customer</label>
                    <select id="customer-filter" class="form-select" data-select2-url="{{ route('b2b/selectors/customers') }}" data-placeholder="Semua customer">
                        <option value=""></option>
                    </select>
                </div>
                <div class="ob-field">
                    <label for="outlet-filter"><i class="fas fa-store" aria-hidden="true"></i> Outlet</label>
                    <select id="outlet-filter" class="form-select">
                        <option value="">Semua outlet</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ob-field b2b-form">
                    <label for="due-filter"><i class="far fa-calendar" aria-hidden="true"></i> Jatuh tempo</label>
                    <div class="b2b-range">
                        <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                        <input id="due-filter" type="text" class="form-control" readonly placeholder="Semua tanggal" autocomplete="off">
                        <button type="button" class="b2b-range__clear" id="due-clear" aria-label="Hapus filter jatuh tempo" hidden><i class="fas fa-times" aria-hidden="true"></i></button>
                    </div>
                    <input type="hidden" id="due-from">
                    <input type="hidden" id="due-to">
                </div>
            </div>

            <div class="ob-table-wrap">
                {!! $dataTable->table(['class' => 'table w-100 b2b-list-table']) !!}
            </div>
        </div>
    </div>

    @include('layouts.b2b.invoices.partials.payment-modal')
    @include('layouts.b2b.reminders.partials.send-dialog')

    @push('css')
        <style>
            .ob-page .ob-stat__value { font-size: 1.15rem; }
            /* Sisa tagihan + bilah terbayar */
            .b2b-bill { display: flex; flex-direction: column; align-items: flex-end; gap: .25rem; min-width: 120px; }
            .b2b-bill__amount { font-weight: 700; color: var(--ob-text); font-variant-numeric: tabular-nums; white-space: nowrap; }
            .b2b-bill__bar { width: 100%; max-width: 120px; height: 5px; overflow: hidden; border-radius: 999px; background: var(--ob-border); }
            .b2b-bill__bar > span { display: block; height: 100%; border-radius: inherit; background: var(--ob-success); }
            .b2b-bill .ob-cell-meta { white-space: nowrap; }
            /* Keterangan jatuh tempo */
            .b2b-due { font-size: .74rem; font-weight: 600; }
            .b2b-due--late { color: var(--ob-danger); }
            .b2b-due--soon { color: #b45309; }
        </style>
    @endpush

    @push('js')
        {!! $dataTable->scripts() !!}
        <script>
            $(function () {
                B2b.listFilters({
                    table: 'b2b-invoice-table',
                    selects: '#status-filter, #customer-filter, #outlet-filter',
                    range: { input: '#due-filter', from: '#due-from', to: '#due-to', clear: '#due-clear', ranges: 'due' },
                });

                // Export mengikuti filter yang sedang dipakai (sama dengan isi tabel).
                $('#export-excel').on('click', function () {
                    const total = window.LaravelDataTables['b2b-invoice-table'].page.info().recordsDisplay;
                    if (!total) return showToast('error', 'Tidak ada invoice untuk diekspor.');
                    if (total > {{ \App\Exports\B2b\InvoiceRecapExport::MAX_INVOICES }}) {
                        return showToast('error', `Ada ${total} invoice. Maksimal {{ \App\Exports\B2b\InvoiceRecapExport::MAX_INVOICES }} per export, persempit filter dulu.`);
                    }
                    const params = new URLSearchParams({
                        status: $('#status-filter').val() || '',
                        customer_id: $('#customer-filter').val() || '',
                        outlet_id: $('#outlet-filter').val() || '',
                        due_from: $('#due-from').val() || '',
                        due_to: $('#due-to').val() || '',
                    });
                    window.location.href = `${this.dataset.url}?${params}`;
                });
            });
        </script>
    @endpush
@endsection

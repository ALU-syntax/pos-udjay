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
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-shopping-bag"></i></span>
                    Pesanan B2B
                </h1>
                <p class="ob-header__desc">Pesanan dari Kasir B2B di outlet yang bisa Anda akses. Penagihan tempo dicatat di menu Invoice.</p>
            </div>
            @can('read b2b/cashier')
                <a href="{{ route('b2b/cashier') }}" class="btn ob-btn ob-btn-primary">
                    <i class="fas fa-cash-register" aria-hidden="true"></i> Buka Kasir B2B
                </a>
            @endcan
        </div>

        <div class="ob-stats">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-calendar-check"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Pesanan bulan ini</p>
                    <p class="ob-stat__value">{{ $number($stats['month_count']) }}</p>
                    <p class="b2b-stat__sub">Rp {{ $number($stats['month_amount']) }}</p>
                </div>
            </div>
            <button type="button" class="ob-card ob-stat b2b-stat-filter" data-filter="status" data-value="confirmed" aria-pressed="false">
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-box-open"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Perlu diselesaikan</p>
                    <p class="ob-stat__value">{{ $number($stats['confirmed']) }}</p>
                    <p class="b2b-stat__sub">Barang belum diserahkan</p>
                </div>
            </button>
            <button type="button" class="ob-card ob-stat b2b-stat-filter" data-filter="payment" data-value="unpaid" aria-pressed="false">
                <span class="ob-stat__icon ob-stat__icon--success" aria-hidden="true"><i class="fas fa-file-invoice-dollar"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Tempo belum lunas</p>
                    <p class="ob-stat__value">{{ $number($stats['unpaid_count']) }}</p>
                    <p class="b2b-stat__sub">Sisa Rp {{ $number($stats['unpaid_amount']) }}</p>
                </div>
            </button>
            <button type="button" class="ob-card ob-stat b2b-stat-filter" data-filter="payment" data-value="overdue" aria-pressed="false">
                <span class="ob-stat__icon ob-stat__icon--danger" aria-hidden="true"><i class="fas fa-exclamation-circle"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Lewat jatuh tempo</p>
                    <p class="ob-stat__value {{ $stats['overdue_count'] ? 'text-danger' : '' }}">{{ $number($stats['overdue_count']) }}</p>
                    <p class="b2b-stat__sub">Sisa Rp {{ $number($stats['overdue_amount']) }}</p>
                </div>
            </button>
        </div>

        <div class="ob-card">
            <div class="b2b-card__head">
                <div>
                    <h2 class="b2b-card__title"><i class="fas fa-list" aria-hidden="true"></i> Daftar Pesanan</h2>
                    <p class="b2b-card__desc">Klik kartu di atas untuk menyaring cepat. Klik lagi untuk melepas.</p>
                </div>
                <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" id="filter-reset" hidden>
                    <i class="fas fa-times" aria-hidden="true"></i> Hapus filter
                </button>
            </div>

            <div class="b2b-filter-row">
                <div class="ob-field">
                    <label for="outlet-filter"><i class="fas fa-store" aria-hidden="true"></i> Outlet</label>
                    <select id="outlet-filter" class="form-select">
                        <option value="">Semua outlet</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ob-field">
                    <label for="status-filter"><i class="fas fa-filter" aria-hidden="true"></i> Status pesanan</label>
                    <select id="status-filter" class="form-select">
                        <option value="">Semua status</option>
                        <option value="confirmed">Dikonfirmasi</option>
                        <option value="completed">Selesai</option>
                        <option value="cancelled">Dibatalkan</option>
                    </select>
                </div>
                <div class="ob-field">
                    <label for="payment-filter"><i class="fas fa-wallet" aria-hidden="true"></i> Pembayaran</label>
                    <select id="payment-filter" class="form-select">
                        <option value="">Semua pembayaran</option>
                        <option value="unpaid">Tempo belum lunas</option>
                        <option value="overdue">Lewat jatuh tempo</option>
                        <option value="paid">Lunas</option>
                        <option value="immediate">Bayar langsung</option>
                    </select>
                </div>
                <div class="ob-field b2b-form">
                    <label for="period-filter"><i class="far fa-calendar" aria-hidden="true"></i> Tanggal pesanan</label>
                    <div class="b2b-range">
                        <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                        <input id="period-filter" type="text" class="form-control" readonly placeholder="Semua tanggal" autocomplete="off">
                        <button type="button" class="b2b-range__clear" id="period-clear" aria-label="Hapus filter tanggal" hidden><i class="fas fa-times" aria-hidden="true"></i></button>
                    </div>
                    <input type="hidden" id="date-from">
                    <input type="hidden" id="date-to">
                </div>
            </div>

            <div class="ob-table-wrap">
                {!! $dataTable->table(['class' => 'table w-100 b2b-list-table']) !!}
            </div>
        </div>
    </div>

    @push('js')
        {!! $dataTable->scripts() !!}
        <script>
            $(function () {
                B2b.listFilters({
                    table: 'b2b-order-table',
                    selects: '#outlet-filter, #status-filter, #payment-filter',
                    range: { input: '#period-filter', from: '#date-from', to: '#date-to', clear: '#period-clear', ranges: 'past' },
                });
            });
        </script>
    @endpush
@endsection

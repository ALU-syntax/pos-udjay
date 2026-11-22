{{--
    Kartu piutang satu customer (halaman di dalam menu Laporan B2B). Ikut filter periode + outlet di URL, sama dengan laporan.
    Ringkasan & invoice belum lunas = per hari ini; kartu piutang (mutasi tagihan + saldo berjalan) = periode terpilih.
--}}
@extends('layouts.app')

@php
    $rupiah = fn (int $value) => ($value < 0 ? '−' : '').'Rp '.number_format(abs($value), 0, ',', '.');
    $count = fn (int $value) => number_format($value, 0, ',', '.');
    $orderUrl = auth()->user()->can('read b2b/orders') ? fn ($id) => $id ? route('b2b/orders/show', $id) : null : fn () => null;
    $periodQuery = array_diff_key($filter->toQuery(), ['customer_id' => true]);
    $ledger = $card['ledger'];
    $typeLabels = ['invoice' => 'Invoice', 'payment' => 'Pembayaran', 'void' => 'Invoice dibatalkan', 'refund' => 'Refund'];
    $endsToday = $filter->to->isSameDay(today());
@endphp

@section('content')
    @include('layouts.b2b.partials.assets')
    @include('layouts.b2b.partials.page-styles')
    @include('layouts.b2b.partials.list-filters')
    @include('layouts.b2b.reports.partials.styles')

    <div class="main-content ob-page b2b-report b2b-report-customer">
        <div class="ob-header">
            <div class="min-w-0">
                <div class="ob-header__eyebrow">
                    <a href="{{ route('b2b/reports', $periodQuery) }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Laporan B2B</a> · Kartu piutang
                </div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-address-card"></i></span>
                    {{ $customer->name }}
                    @if ($customer->trashed())
                        <span class="badge badge-muted">Dihapus</span>
                    @endif
                </h1>
                <p class="ob-header__desc b2b-report-customer__meta">
                    <span>{{ $customer->code }}</span>
                    <span>{{ $customer->typeLabel() }}</span>
                    @if ($customer->contact_name)<span><i class="fas fa-user" aria-hidden="true"></i> {{ $customer->contact_name }}</span>@endif
                    @if ($customer->phone)<span><i class="fas fa-phone" aria-hidden="true"></i> {{ $customer->phone }}</span>@endif
                    @if ($customer->email)<span><i class="fas fa-envelope" aria-hidden="true"></i> {{ $customer->email }}</span>@endif
                    @if ($contract)
                        <span class="b2b-report-customer__contract"><i class="fas fa-file-signature" aria-hidden="true"></i> Kontrak {{ $contract->contract_number }} s.d. {{ $contract->end_date->translatedFormat('d M Y') }}</span>
                    @else
                        <span class="b2b-report-customer__contract is-none"><i class="fas fa-file-signature" aria-hidden="true"></i> Tanpa kontrak berjalan</span>
                    @endif
                </p>
            </div>
            @can('read b2b/customers')
                <div class="b2b-actions">
                    <a href="{{ route('b2b/customers/show', $customer->id) }}" class="btn ob-btn ob-btn-outline" target="_blank" rel="noopener">
                        <i class="fas fa-building" aria-hidden="true"></i> Profil customer
                    </a>
                </div>
            @endcan
        </div>

        <div class="ob-card b2b-report-filter">
            <div class="b2b-filter-row b2b-report-filter__row b2b-report-customer__filter">
                <div class="ob-field b2b-form">
                    <label for="card-period"><i class="far fa-calendar" aria-hidden="true"></i> Periode kartu piutang</label>
                    <div class="b2b-range">
                        <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                        <input id="card-period" type="text" class="form-control" readonly autocomplete="off">
                    </div>
                </div>
                <div class="ob-field">
                    <label for="card-outlet"><i class="fas fa-store" aria-hidden="true"></i> Outlet</label>
                    <select id="card-outlet" class="form-select">
                        <option value="">Semua outlet</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected($filter->outletId === $outlet->id)>{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <section class="ob-stats b2b-report-kpis b2b-report-customer__kpis">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-file-invoice-dollar"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Sisa piutang <span class="b2b-report-asof">per hari ini</span></p>
                    <p class="ob-stat__value">{{ $rupiah($card['receivable']['amount']) }}</p>
                    <p class="b2b-stat__sub">{{ $count($card['receivable']['invoices']) }} invoice belum lunas</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--danger" aria-hidden="true"><i class="fas fa-exclamation-circle"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Lewat jatuh tempo <span class="b2b-report-asof">per hari ini</span></p>
                    <p class="ob-stat__value {{ $card['overdue']['invoices'] ? 'text-danger' : '' }}">{{ $rupiah($card['overdue']['amount']) }}</p>
                    <p class="b2b-stat__sub">
                        {{ $count($card['overdue']['invoices']) }} invoice{{ $card['overdue']['invoices'] ? ' · terlama '.$count($card['oldest_days_overdue']).' hari' : '' }}
                    </p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-shopping-bag"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Penjualan</p>
                    <p class="ob-stat__value">{{ $rupiah($card['sales']['amount']) }}</p>
                    <p class="b2b-stat__sub">{{ $count($card['sales']['orders']) }} pesanan di periode ini</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--success" aria-hidden="true"><i class="fas fa-hand-holding-usd"></i></span>
                <div class="min-w-0">
                    <p class="ob-stat__label">Kas masuk bersih</p>
                    <p class="ob-stat__value">{{ $rupiah($card['cash']['net']) }}</p>
                    <p class="b2b-stat__sub">
                        {{ $card['cash']['refunded'] ? 'Masuk '.$rupiah($card['cash']['received']).' · refund '.$rupiah($card['cash']['refunded']) : 'Di periode ini' }}
                    </p>
                </div>
            </div>
        </section>

        <section class="ob-card b2b-report-card">
            <div class="b2b-card__head">
                <div class="min-w-0">
                    <h2 class="b2b-card__title"><i class="fas fa-file-invoice" aria-hidden="true"></i> Invoice belum lunas <span class="b2b-report-asof">per hari ini</span></h2>
                    <p class="b2b-card__desc">Jatuh tempo terlama dulu. Tidak mengikuti periode.</p>
                </div>
            </div>
            <div class="b2b-card__body">
                @if ($card['open_invoices'])
                    <div class="b2b-report-table-wrap">
                        <table class="b2b-report-table">
                            <thead>
                                <tr>
                                    <th scope="col">Invoice</th>
                                    <th scope="col">Jatuh tempo</th>
                                    <th scope="col" class="text-end">Total</th>
                                    <th scope="col" class="text-end">Dibayar</th>
                                    <th scope="col" class="text-end">Sisa</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($card['open_invoices'] as $invoice)
                                    <tr>
                                        <td>
                                            @if ($url = $orderUrl($invoice['order_id']))
                                                <a href="{{ $url }}" target="_blank" rel="noopener" class="b2b-report-customer__num">{{ $invoice['invoice_number'] }}</a>
                                            @else
                                                <strong class="b2b-report-customer__num">{{ $invoice['invoice_number'] }}</strong>
                                            @endif
                                            <span class="ob-cell-meta">Terbit {{ $invoice['issue_date'] }} · {{ $invoice['outlet'] ?? '-' }}</span>
                                        </td>
                                        <td class="text-nowrap">
                                            {{ $invoice['due_date'] }}
                                            @if ($invoice['days_overdue'] > 0)
                                                <span class="ob-cell-meta is-danger">Lewat {{ $count($invoice['days_overdue']) }} hari</span>
                                            @else
                                                <span class="ob-cell-meta">{{ $invoice['days_overdue'] === 0 ? 'Jatuh tempo hari ini' : $count(-$invoice['days_overdue']).' hari lagi' }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ $rupiah($invoice['total']) }}</td>
                                        <td class="text-end">{{ $invoice['paid'] ? $rupiah($invoice['paid']) : '-' }}</td>
                                        <td class="text-end"><strong>{{ $rupiah($invoice['outstanding']) }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            @if (count($card['open_invoices']) > 1)
                                <tfoot>
                                    <tr>
                                        <td colspan="4">Total sisa piutang</td>
                                        <td class="text-end">{{ $rupiah($card['receivable']['amount']) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                @else
                    <div class="b2b-report-empty b2b-report-empty--short">Tidak ada invoice yang belum lunas.</div>
                @endif
            </div>
        </section>

        <section class="ob-card b2b-report-card">
            <div class="b2b-card__head">
                <div class="min-w-0">
                    <h2 class="b2b-card__title"><i class="fas fa-book" aria-hidden="true"></i> Kartu piutang</h2>
                    <p class="b2b-card__desc">
                        Mutasi tagihan {{ $ledger['from'] }} – {{ $ledger['to'] }}. Tagihan menambah saldo, pembayaran mengurangi.
                        @if ($endsToday) Saldo akhir = sisa piutang per hari ini. @endif
                    </p>
                </div>
            </div>
            <div class="b2b-card__body">
                <div class="b2b-report-table-wrap">
                    <table class="b2b-report-table b2b-report-ledger">
                        <thead>
                            <tr>
                                <th scope="col">Tanggal</th>
                                <th scope="col">Keterangan</th>
                                <th scope="col" class="text-end">Tagihan (+)</th>
                                <th scope="col" class="text-end">Pembayaran (−)</th>
                                <th scope="col" class="text-end">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="b2b-report-ledger__opening">
                                <td class="text-nowrap">{{ $ledger['from'] }}</td>
                                <td colspan="3">Saldo awal</td>
                                <td class="text-end">{{ $rupiah($ledger['opening']) }}</td>
                            </tr>
                            @forelse ($ledger['rows'] as $row)
                                <tr>
                                    <td class="text-nowrap">{{ $row['date'] }}</td>
                                    <td>
                                        <span class="b2b-report-ledger__type is-{{ $row['type'] }}">{{ $typeLabels[$row['type']] }}</span>
                                        @if ($url = $orderUrl($row['order_id']))
                                            <a href="{{ $url }}" target="_blank" rel="noopener">{{ $row['number'] }}</a>
                                        @else
                                            {{ $row['number'] }}
                                        @endif
                                        @if ($row['note'])<span class="ob-cell-meta">{{ $row['note'] }}</span>@endif
                                    </td>
                                    <td class="text-end">{{ $row['debit'] ? $rupiah($row['debit']) : '' }}</td>
                                    <td class="text-end">{{ $row['credit'] ? $rupiah($row['credit']) : '' }}</td>
                                    <td class="text-end b2b-report-ledger__balance">{{ $rupiah($row['balance']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="b2b-report-ledger__none">Tidak ada mutasi di periode ini.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2">Total mutasi</td>
                                <td class="text-end">{{ $rupiah($ledger['debit']) }}</td>
                                <td class="text-end">{{ $rupiah($ledger['credit']) }}</td>
                                <td></td>
                            </tr>
                            <tr class="b2b-report-ledger__closing">
                                <td class="text-nowrap">{{ $ledger['to'] }}</td>
                                <td colspan="3">Saldo akhir</td>
                                <td class="text-end">{{ $rupiah($ledger['closing']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </section>
    </div>

    @push('css')
        <style>
            .b2b-report-customer .ob-header__eyebrow a { color: var(--ob-primary); font-weight: 700; }
            .b2b-report-customer__meta { display: flex; flex-wrap: wrap; gap: .25rem 1rem; }
            .b2b-report-customer__meta i { margin-right: .2rem; font-size: .75rem; color: var(--ob-muted); }
            .b2b-report-customer__contract { color: var(--ob-success, #2e9e3e); font-weight: 600; }
            .b2b-report-customer__contract.is-none { color: var(--ob-muted); font-weight: 400; }
            /* Hanya dua isian: di layar lebar kolom ketiga dibiarkan kosong; di bawahnya ikut aturan filter laporan (2 → 1 kolom). */
            @media (min-width: 1200px) { .b2b-report-customer__filter { grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr) minmax(0, 1.2fr); } }
            .ob-page .b2b-report-customer__kpis { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            @media (max-width: 1199.98px) { .ob-page .b2b-report-customer__kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            .b2b-report-customer__num { font-weight: 700; }
            .b2b-report-table a { font-weight: 600; color: var(--ob-primary); }
            .b2b-report-table .ob-cell-meta.is-danger { color: var(--ob-danger); font-weight: 600; }
            .b2b-report-ledger__opening td, .b2b-report-ledger__closing td { font-weight: 700; color: var(--ob-text); background: var(--ob-surface-2); }
            .b2b-report-ledger__balance { font-weight: 600; color: var(--ob-text); }
            .b2b-report-ledger__none { padding: 1.25rem .6rem !important; text-align: center; color: var(--ob-muted); }
            .b2b-report-ledger__type { display: inline-block; min-width: 0; margin-right: .35rem; padding: .05rem .45rem; border-radius: 999px; font-size: .7rem; font-weight: 700; background: var(--ob-border); color: var(--ob-text-2); }
            .b2b-report-ledger__type.is-invoice { background: var(--ob-primary-soft, #e8f1fd); color: var(--ob-primary); }
            .b2b-report-ledger__type.is-payment { background: var(--ob-success-soft, #e7f7ea); color: var(--ob-success, #2e9e3e); }
            .b2b-report-ledger__type.is-refund { background: var(--ob-danger-soft); color: var(--ob-danger); }
        </style>
    @endpush

    @push('js')
        <script>
            $(function () {
                const query = @json($filter->toQuery());
                delete query.customer_id;
                const go = patch => {
                    const params = new URLSearchParams(Object.entries(Object.assign({}, query, patch)).filter(([, value]) => value));
                    window.location.search = params.toString();
                };

                const $period = $('#card-period');
                $period.val(B2b.rangeLabel(moment(query.from), moment(query.to)));
                B2b.rangePicker($period, {
                    ranges: 'report',
                    opens: 'right',
                    onApply: (start, end) => go({ from: start.format('YYYY-MM-DD'), to: end.format('YYYY-MM-DD') }),
                });
                const picker = $period.data('daterangepicker');
                if (picker) {
                    picker.setStartDate(moment(query.from));
                    picker.setEndDate(moment(query.to));
                }
                $('#card-outlet').on('change', function () { go({ outlet_id: this.value }); });
            });
        </script>
    @endpush
@endsection

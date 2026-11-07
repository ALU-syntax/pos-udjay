@extends('layouts.app')

@section('content')
    @include('layouts.reports.partials.ui-styles')

    @php
        $outlets = App\Models\Outlets::query()->whereIn('id', auth()->user()->outletIds())->orderBy('name')->get();
    @endphp
    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Order Table</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-receipt"></i></span>
                    Monitor Penukaran Voucher
                </h1>
                <p class="ob-header__desc">Riwayat audit penukaran voucher oleh pelanggan Order Table.</p>
            </div>
        </div>

        {{-- Filter --}}
        <div class="ob-card">
            <div class="ob-toolbar">
                <div class="ob-field">
                    <label for="outlet-filter"><i class="fas fa-store" aria-hidden="true"></i> Outlet</label>
                    <select id="outlet-filter" class="form-control">
                        <option value="">Semua outlet</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ob-field">
                    <label for="voucher-filter"><i class="fas fa-ticket-alt" aria-hidden="true"></i> Voucher</label>
                    <select id="voucher-filter" class="form-control">
                        <option value="">Semua voucher</option>
                        @foreach (($vouchers ?? []) as $voucher)
                            <option value="{{ $voucher->id }}">{{ $voucher->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ob-field">
                    <label for="date-range-filter"><i class="far fa-calendar-alt" aria-hidden="true"></i> Rentang Tanggal</label>
                    <div class="ob-daterange">
                        <input id="date-range-filter" type="text" class="form-control" placeholder="Semua tanggal" autocomplete="off" readonly>
                        <button type="button" id="date-range-clear" class="ob-daterange__btn d-none" title="Hapus filter tanggal" aria-label="Hapus filter tanggal">
                            <i class="fas fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                    {{-- Nilai yang dikirim ke server (YYYY-MM-DD); kosong berarti semua tanggal --}}
                    <input id="date-from-filter" type="hidden">
                    <input id="date-to-filter" type="hidden">
                </div>
                <div class="ob-field">
                    <label for="order-filter"><i class="fas fa-search" aria-hidden="true"></i> Nomor order</label>
                    <input id="order-filter" type="search" class="form-control" placeholder="Cari nomor order" autocomplete="off">
                </div>
            </div>
        </div>

        {{-- Tabel --}}
        <div class="ob-card">
            <div class="ob-tabs-bar">
                <div>
                    <h2 class="ob-section-title mb-1"><i class="fas fa-list" aria-hidden="true"></i> Riwayat Penukaran</h2>
                    <span class="ob-hint"><i class="fas fa-lock" aria-hidden="true"></i> Hanya dapat dibaca. Perangkat dan IP pelanggan disamarkan.</span>
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
                const table = window.LaravelDataTables['voucher-redemption-table'];
                const reload = () => table.ajax.reload();
                let timer;

                $('#outlet-filter, #voucher-filter').select2({ width: '100%', minimumResultsForSearch: 8 }).on('change', reload);

                // Rentang tanggal: tampilan DD/MM/YYYY, nilai ke server lewat input tersembunyi
                const range = $('#date-range-filter');
                const from = $('#date-from-filter');
                const to = $('#date-to-filter');
                const clear = $('#date-range-clear');

                function setRange(start, end) {
                    range.val(start ? start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY') : '');
                    from.val(start ? start.format('YYYY-MM-DD') : '');
                    to.val(end ? end.format('YYYY-MM-DD') : '');
                    clear.toggleClass('d-none', !start);
                    reload();
                }

                range.daterangepicker({
                    autoUpdateInput: false,
                    linkedCalendars: false,
                    maxDate: moment().endOf('day'),
                    drops: 'auto',
                    buttonClasses: 'btn btn-sm',
                    applyButtonClasses: 'btn-primary',
                    cancelButtonClasses: 'btn-light',
                    ranges: {
                        'Hari ini': [moment(), moment()],
                        'Kemarin': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        '7 hari terakhir': [moment().subtract(6, 'days'), moment()],
                        '30 hari terakhir': [moment().subtract(29, 'days'), moment()],
                        'Bulan ini': [moment().startOf('month'), moment().endOf('month')],
                        'Bulan lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    },
                    locale: {
                        format: 'DD/MM/YYYY',
                        applyLabel: 'Terapkan',
                        cancelLabel: 'Batal',
                        customRangeLabel: 'Pilih tanggal',
                        daysOfWeek: ['Mg', 'Sn', 'Sl', 'Rb', 'Km', 'Jm', 'Sb'],
                        monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                        firstDay: 1,
                    },
                });
                range.on('apply.daterangepicker', (event, picker) => setRange(picker.startDate, picker.endDate));
                clear.on('click', () => setRange(null, null));
                $('#order-filter').on('input', function () {
                    clearTimeout(timer);
                    timer = setTimeout(reload, 350);
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            /* Input teks di toolbar setinggi select2 (input rentang tanggal diatur .ob-daterange) */
            .ob-toolbar > .ob-field > input.form-control {
                min-height: 42px;
                border: 1px solid var(--ob-border-strong);
                border-radius: var(--ob-radius-sm);
                background-color: var(--ob-surface);
                color: var(--ob-text);
                font-size: .875rem !important;
                box-shadow: none;
                transition: border-color .15s ease, box-shadow .15s ease;
            }

            .ob-toolbar > .ob-field > input.form-control:focus {
                border-color: var(--ob-primary);
                box-shadow: 0 0 0 3px rgba(21, 114, 232, .15);
            }

            /* Sel dua baris: nilai utama + keterangan */
            .ob-cell-stack {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: .2rem;
                white-space: nowrap;
                font-variant-numeric: tabular-nums;
            }

            .ob-voucher-code {
                padding: .25rem .6rem;
                border: 1px dashed var(--ob-primary);
                border-radius: 6px;
                background: var(--ob-primary-soft);
                color: var(--ob-primary);
                font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                font-size: .82rem;
                font-weight: 700;
                letter-spacing: .04em;
                white-space: nowrap;
            }

            .ob-page table.dataTable tbody td.ob-col-time {
                font-size: .8rem;
            }

            .ob-page table.dataTable tbody td.ob-col-amount {
                white-space: nowrap;
                font-weight: 600;
                font-variant-numeric: tabular-nums;
            }
        </style>
    @endpush
@endsection

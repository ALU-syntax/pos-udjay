@extends('layouts.app')

@section('content')
    @include('layouts.reports.partials.ui-styles')

    @php
        $labels = App\Support\OrderTable\OrderLabels::class;
    @endphp
    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Order Table</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-exchange-alt"></i></span>
                    Monitor Bridge POS
                </h1>
                <p class="ob-header__desc">Pantau pencatatan order lunas ke transaksi POS. Re-bridge tetap dinonaktifkan sampai kontrak Node final.</p>
            </div>
        </div>

        @unless ($nodeEnabled)
            <div class="ob-notice" role="note">
                <i class="fas fa-plug" aria-hidden="true"></i>
                <span>Tombol <strong>Re-bridge</strong> dinonaktifkan sampai integrasi layanan Node diaktifkan.</span>
            </div>
        @endunless

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
                    <label for="bridge-status-filter"><i class="fas fa-exchange-alt" aria-hidden="true"></i> Status Bridge</label>
                    <select id="bridge-status-filter" class="form-control">
                        <option value="">Semua status</option>
                        @foreach ($labels::options($labels::BRIDGE) as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @include('layouts.order-table.partials.date-range-filter', ['label' => 'Tanggal Order'])
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
                    <h2 class="ob-section-title mb-1"><i class="fas fa-list" aria-hidden="true"></i> Daftar Bridge</h2>
                    <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Hanya order yang sudah lunas. Re-bridge hanya tersedia untuk bridge yang gagal.</span>
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
                const table = window.LaravelDataTables['bridge-monitor-table'];
                const reload = () => table.ajax.reload();
                let timer;

                $('#outlet-filter, #bridge-status-filter').select2({ width: '100%', minimumResultsForSearch: 8 }).on('change', reload);
                initDateRangeFilter(reload);
                $('#order-filter').on('input', function () {
                    clearTimeout(timer);
                    timer = setTimeout(reload, 350);
                });

                $('.main-content').on('click', '.action-rebridge', function (event) {
                    event.preventDefault();
                    const button = this;
                    Swal.fire({ title: 'Minta re-bridge?', text: 'Permintaan diteruskan ke layanan order. Tindakan ini dicatat pada audit log.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, re-bridge', cancelButtonText: 'Batal' })
                        .then(r => { if (!r.isConfirmed) return; $.post(button.href, { _token: @json(csrf_token()) }).done(res => { showToast(res.status || 'success', res.message); table.ajax.reload(null, false); }).fail(xhr => showToast('error', xhr.responseJSON?.message || 'Gagal mengirim permintaan')); });
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
                gap: .25rem;
                white-space: nowrap;
                font-variant-numeric: tabular-nums;
            }

            .ob-order-no {
                font-weight: 700;
                color: var(--ob-text);
            }

            .ob-page table.dataTable tbody td.ob-col-time {
                font-size: .8rem;
            }

            .ob-page table.dataTable tbody td.ob-col-amount {
                white-space: nowrap;
                font-weight: 600;
                font-variant-numeric: tabular-nums;
            }

            .ob-page table.dataTable tbody td.ob-col-device {
                white-space: nowrap;
                font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                font-size: .75rem;
                color: var(--ob-muted);
            }

            .ob-page .ob-icon-btn:disabled {
                opacity: .5;
                cursor: not-allowed;
            }
        </style>
    @endpush
@endsection

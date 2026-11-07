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
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-users"></i></span>
                    Monitor Sesi Meja
                </h1>
                <p class="ob-header__desc">Pantau sesi perangkat pelanggan di setiap meja.</p>
            </div>
        </div>

        @unless ($nodeEnabled)
            <div class="ob-notice" role="note">
                <i class="fas fa-plug" aria-hidden="true"></i>
                <span>Aksi <strong>Tutup Sesi</strong> dinonaktifkan sampai integrasi layanan Node diaktifkan.</span>
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
                    <label for="status-filter"><i class="fas fa-toggle-on" aria-hidden="true"></i> Status</label>
                    <select id="status-filter" class="form-control">
                        <option value="">Semua status</option>
                        @foreach ($labels::options($labels::SESSION) as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @include('layouts.order-table.partials.date-range-filter', ['label' => 'Tanggal Dibuka'])
            </div>
        </div>

        {{-- Tabel --}}
        <div class="ob-card">
            <div class="ob-tabs-bar">
                <div>
                    <h2 class="ob-section-title mb-1"><i class="fas fa-list" aria-hidden="true"></i> Daftar Sesi</h2>
                    <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Sesi tidak dapat dihapus. Penutupan sesi dikirim ke layanan order.</span>
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
                const table = window.LaravelDataTables['session-monitor-table'];
                const reload = () => table.ajax.reload();

                $('#outlet-filter, #status-filter').select2({ width: '100%', minimumResultsForSearch: 8 }).on('change', reload);
                initDateRangeFilter(reload);

                $('.main-content').on('click', '.action-close-session', function (event) {
                    event.preventDefault();
                    const button = this;
                    Swal.fire({ title: 'Tutup sesi?', input: 'text', inputLabel: 'Alasan (opsional)', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, tutup sesi', cancelButtonText: 'Batal' })
                        .then(r => { if (!r.isConfirmed) return; $.post(button.href, { _token: @json(csrf_token()), reason: r.value }).done(res => { showToast(res.status || 'success', res.message); table.ajax.reload(null, false); }).fail(xhr => showToast('error', xhr.responseJSON?.message || 'Gagal mengirim permintaan')); });
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            /* Sel dua baris: nilai utama + keterangan */
            .ob-cell-stack {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: .25rem;
                white-space: nowrap;
                font-variant-numeric: tabular-nums;
            }

            .ob-page table.dataTable tbody td.ob-col-time {
                font-size: .8rem;
            }

            .ob-page table.dataTable tbody td.ob-col-device {
                white-space: nowrap;
                font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                font-size: .75rem;
                color: var(--ob-muted);
            }
        </style>
    @endpush
@endsection

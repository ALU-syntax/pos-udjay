@extends('layouts.app')

@section('content')
    @include('layouts.reports.partials.ui-styles')

    @php
        $outlets = App\Models\Outlets::query()
            ->whereIn('id', auth()->user()->outletIds())
            ->orderBy('name')
            ->get();
    @endphp
    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Order Table</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-credit-card"></i></span>
                    Metode Pembayaran
                </h1>
                <p class="ob-header__desc">Kelola metode pembayaran dan mapping master POS untuk setiap outlet.</p>
            </div>
            @can('create order-table/payment-methods')
                <a href="{{ route('order-table/payment-methods/create') }}" class="btn ob-btn ob-btn-primary action">
                    <i class="fas fa-plus" aria-hidden="true"></i> Tambah Metode
                </a>
            @endcan
        </div>

        <div class="ob-notice" role="note">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <span>
                Perubahan status langsung menentukan metode yang dapat dipilih pelanggan Order Table.
                @unless (config('order-table.pay_at_cashier_enabled'))
                    <strong>Bayar di Kasir</strong> belum diizinkan di environment ini, jadi metode tersebut tidak dapat diaktifkan.
                @endunless
            </span>
        </div>

        {{-- Tabel --}}
        <div class="ob-card">
            <div class="ob-tabs-bar">
                <div>
                    <h2 class="ob-section-title mb-1"><i class="fas fa-list" aria-hidden="true"></i> Konfigurasi Pembayaran</h2>
                    <span class="ob-hint"><i class="fas fa-info-circle" aria-hidden="true"></i> Metode pembayaran wajib dikonfigurasi per outlet.</span>
                </div>
                <div class="ob-field ob-list-filter">
                    <label for="outlet-filter"><i class="fas fa-store" aria-hidden="true"></i> Outlet</label>
                    <select id="outlet-filter" class="form-control">
                        <option value="">Semua outlet</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                        @endforeach
                    </select>
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
                const tableId = 'payment-method-table';

                $('#outlet-filter').select2({ width: '100%', minimumResultsForSearch: 8 })
                    .on('change', () => window.LaravelDataTables[tableId].ajax.reload());

                handleAction(tableId, function () {
                    const form = $('#form_action');

                    function updateCodeFields() {
                        const code = form.find('[name="code"]').val();
                        form.find('[data-code-field]').addClass('d-none').find('input').prop('disabled', true);
                        form.find('[data-code-field="' + code + '"]').removeClass('d-none').find('input').prop('disabled', false);
                        form.find('.cashier-mapping-warning').toggleClass('d-none', code !== 'pay_at_cashier');
                        if (code === 'pay_at_cashier' && !@json(config('order-table.pay_at_cashier_enabled'))) {
                            form.find('[name="enabled"]').val('0');
                            form.find('[name="enabled"] option[value="1"]').prop('disabled', true);
                        } else {
                            form.find('[name="enabled"] option[value="1"]').prop('disabled', false);
                        }
                    }

                    form.on('change', '[name="code"]', updateCodeFields);
                    updateCodeFields();
                });

                $('.main-content').on('click', '.toggle-payment-method', function (event) {
                    event.preventDefault();
                    const button = this;

                    Swal.fire({
                        title: 'Ubah status metode?',
                        text: 'Perubahan ini menentukan metode yang dapat dipilih pelanggan.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, ubah status',
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (!result.isConfirmed) return;

                        $.ajax({
                            url: button.href,
                            method: 'POST',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function (response) {
                                showToast(response.status || 'success', response.message || 'Status metode diperbarui');
                                window.LaravelDataTables[tableId].ajax.reload(null, false);
                            },
                            error: function (xhr) {
                                showToast('error', xhr.responseJSON?.message || 'Gagal memperbarui metode pembayaran');
                            }
                        });
                    });
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
                gap: .2rem;
                white-space: nowrap;
            }

            .ob-method-code {
                padding: .1rem .45rem;
                border: 1px solid var(--ob-border-strong);
                border-radius: 6px;
                background: var(--ob-surface-2);
                color: var(--ob-text-2);
                font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
                font-size: .72rem;
            }
        </style>
    @endpush
@endsection

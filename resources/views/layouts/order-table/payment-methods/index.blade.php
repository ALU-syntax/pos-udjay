@extends('layouts.app')

@section('content')
    @php
        $outlets = App\Models\Outlets::query()
            ->whereIn('id', auth()->user()->outletIds())
            ->orderBy('name')
            ->get();
    @endphp
    <div class="main-content order-table-page">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h2 class="h4 mb-1 font-weight-bold"><i class="fa fa-credit-card me-2"></i>Metode Pembayaran Order Table</h2>
                <p class="text-muted mb-0">Kelola metode pembayaran dan mapping master POS untuk setiap outlet.</p>
            </div>
            @can('create order-table/payment-methods')
                <a href="{{ route('order-table/payment-methods/create') }}" class="btn btn-primary btn-round action">
                    <i class="fa fa-plus me-2"></i>Tambah Metode
                </a>
            @endcan
        </div>

        <div class="alert alert-warning border-0 shadow-sm">
            <i class="fa fa-flask me-2"></i>
            Mapping <strong>Bayar di Kasir</strong> masih khusus development. Konfirmasi mapping kategori dan POS sebelum production.
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="mb-1">Konfigurasi Pembayaran</h5>
                    <small class="text-muted">Metode pembayaran wajib dikonfigurasi per outlet.</small>
                </div>
                <div class="order-table-filter">
                    <label for="outlet-filter" class="form-label small mb-1">Filter outlet</label>
                    <select id="outlet-filter" class="form-select form-select-sm">
                        <option value="">Semua outlet</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0']) !!}
                </div>
            </div>
        </div>
    </div>

    @push('js')
        {!! $dataTable->scripts() !!}
        <script>
            $(function () {
                const tableId = 'payment-method-table';

                $('#outlet-filter').on('change', function () {
                    window.LaravelDataTables[tableId].ajax.reload();
                });

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
            .order-table-page .card { border-radius: 10px; }
            .order-table-filter { min-width: 240px; }
            .order-table-actions { display: inline-flex; justify-content: center; gap: 5px; }
            .order-table-actions .btn { width: 34px; height: 34px; padding: 7px; }
        </style>
    @endpush
@endsection

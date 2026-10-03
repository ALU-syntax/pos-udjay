@extends('layouts.app')

@section('content')
    <div class="main-content order-table-page">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div><h2 class="h4 mb-1 font-weight-bold"><i class="fa fa-ticket me-2"></i>Voucher Order Table</h2><p class="text-muted mb-0">Kelola voucher outlet dan pantau status efektifnya.</p></div>
            @can('create order-table/vouchers')
                <a href="{{ route('order-table/vouchers/create') }}" class="btn btn-primary btn-round action"><i class="fa fa-plus me-2"></i>Tambah Voucher</a>
            @endcan
        </div>
        @if ($canManageGlobal)
            <div class="alert alert-warning border-0 shadow-sm"><i class="fa fa-triangle-exclamation me-2"></i>Perubahan voucher global berdampak pada seluruh outlet aktif.</div>
        @endif
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div><h5 class="mb-1">Daftar Voucher</h5><small class="text-muted">Status efektif memperhitungkan status, tanggal, dan kuota.</small></div>
                <div class="order-table-filter"><label for="outlet-filter" class="form-label small mb-1">Filter outlet</label><select id="outlet-filter" class="form-select form-select-sm"><option value="">Semua outlet</option>@if ($canManageGlobal)<option value="global">Global</option>@endif @foreach ($outlets as $outlet)<option value="{{ $outlet->id }}">{{ $outlet->name }}</option>@endforeach</select></div>
            </div>
            <div class="card-body"><div class="table-responsive">{!! $dataTable->table(['class' => 'table table-hover align-middle mb-0']) !!}</div></div>
        </div>
    </div>
    @push('js')
        {!! $dataTable->scripts() !!}
        <script>
            $(function () {
                const tableId = 'voucher-table';
                const selectorUrls = { category: @json(route('order-table/selectors/categories')), product: @json(route('order-table/selectors/products')) };
                $('#outlet-filter').on('change', () => window.LaravelDataTables[tableId].ajax.reload());
                handleAction(tableId, function () {
                    const form = $('#form_action');
                    const target = form.find('#scope_ids');
                    function updateType() {
                        const percent = form.find('#type').val() === 'percent';
                        form.find('.value-prefix').text(percent ? '%' : 'Rp');
                        form.find('.max-discount-field').toggleClass('d-none', !percent).find('input').prop('disabled', !percent);
                    }
                    function initTarget(clear) {
                        const scope = form.find('#scope').val();
                        const outlet = form.find('#outlet_id').val();
                        const global = outlet === '';
                        form.find('#scope option[value="category"], #scope option[value="product"]').prop('disabled', global);
                        if (global && scope !== 'all') {
                            form.find('#scope').val('all');
                        }
                        const effectiveScope = form.find('#scope').val();
                        if (target.hasClass('select2-hidden-accessible')) target.select2('destroy');
                        if (clear) target.empty();
                        form.find('.scope-target-field').toggleClass('d-none', effectiveScope === 'all');
                        target.prop('disabled', effectiveScope === 'all');
                        if (effectiveScope === 'all') return;
                        target.select2({ dropdownParent: $('#modal_action'), width: '100%', placeholder: 'Pilih target', ajax: { url: selectorUrls[effectiveScope], dataType: 'json', delay: 250, data: params => ({ q: params.term, outlet_id: outlet }), processResults: response => ({ results: response.data || [] }) } });
                    }
                    function updateGlobalWarning() { form.find('.global-impact-warning').toggleClass('d-none', form.find('#outlet_id').val() !== ''); }
                    form.on('input', '#code', function () { this.value = this.value.toUpperCase(); });
                    form.on('change', '#type', updateType);
                    form.on('change', '#scope, #outlet_id', function () { initTarget(true); updateGlobalWarning(); });
                    updateType(); initTarget(false); updateGlobalWarning();
                });
                $('.main-content').on('click', '.toggle-voucher', function (event) {
                    event.preventDefault(); const button = this;
                    Swal.fire({ title: 'Ubah status voucher?', text: 'Perubahan langsung memengaruhi voucher yang dapat digunakan pelanggan.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, ubah status', cancelButtonText: 'Batal' }).then(function (result) {
                        if (!result.isConfirmed) return;
                        $.post(button.href, { _token: @json(csrf_token()) }).done(function (response) { showToast(response.status || 'success', response.message || 'Status voucher diperbarui'); window.LaravelDataTables[tableId].ajax.reload(null, false); }).fail(xhr => showToast('error', xhr.responseJSON?.message || 'Gagal memperbarui voucher'));
                    });
                });
            });
        </script>
    @endpush
    @push('css')<style>.order-table-page .card{border-radius:10px}.order-table-filter{min-width:240px}.order-table-actions{display:inline-flex;gap:5px}.order-table-actions .btn{width:34px;height:34px;padding:7px}</style>@endpush
@endsection
